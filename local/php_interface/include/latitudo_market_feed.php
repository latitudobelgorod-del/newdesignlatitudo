<?php
/**
 * Полные товарные фиды в модуле «Маркет для продавцов» (yandex.market):
 * цена — в основной единице товара, как на карточке.
 *
 * Модуль берёт цену из каталога как есть, то есть за штуку: доска 2265 ₽.
 * А покупатель на карточке, в микроразметке и в нашем фиде видит 5000 ₽/м²
 * (основная единица из свойства BASE_KOEF, множитель — из UNIT_KOEF).
 * Такое расхождение Яндекс называет «фактические цены отличаются от
 * заявленных» — на easydecking.ru из-за этого блокировали источник.
 *
 * Поэтому перед записью предложения (событие модуля onExportOfferExtendData)
 * домножаем price и oldprice. Касается ТОЛЬКО прайс-листов, чьё название
 * начинается с «Полный товарный фид» (Ирина, 10 сентября 2026): дилерские
 * и прежние «для вебмастера» выгружаются как раньше.
 *
 * Формула та же, что в ndShownPrice() шаблонов карточки и в
 * local/feed/generate.php — менять в четырёх местах сразу.
 *
 * Подключается из local/init.php лениво, внутри обработчика.
 */

class LatitudoMarketFeed
{
    const NAME_PREFIX = 'Полный товарный фид';
    const IBLOCK_PRODUCTS = 19;
    const IBLOCK_OFFERS = 20;

    /** @var array<int, bool> ID прайс-листа => полный ли он */
    protected static $setups = [];

    public static function isFullSetup($setupId): bool
    {
        $setupId = (int)$setupId;
        if (!$setupId) {
            return false;
        }
        if (!isset(static::$setups[$setupId])) {
            $row = \Bitrix\Main\Application::getConnection()
                ->query('SELECT NAME FROM yamarket_export_setup WHERE ID = ' . $setupId)
                ->fetch();
            static::$setups[$setupId] = $row && mb_strpos((string)$row['NAME'], static::NAME_PREFIX) === 0;
        }
        return static::$setups[$setupId];
    }

    public static function onOfferExtendData(\Bitrix\Main\Event $event): void
    {
        $params  = $event->getParameters();
        $context = (array)($params['CONTEXT'] ?? []);
        if (!static::isFullSetup($context['SETUP_ID'] ?? 0)) {
            return;
        }

        $tagValues = $params['TAG_VALUE_LIST'] ?? [];
        $elements  = $params['ELEMENT_LIST'] ?? [];
        if (!$tagValues) {
            return;
        }

        // ID позиции => ID товара (у простого товара — он сам)
        $parentOf = [];
        foreach ($elements as $element) {
            $id = (int)($element['ID'] ?? 0);
            if ($id) {
                $parentOf[$id] = (int)($element['PARENT_ID'] ?? 0) ?: $id;
            }
        }
        $units = static::loadUnits(array_unique(array_merge(array_keys($parentOf), array_values($parentOf))));

        foreach ($tagValues as $elementId => $tagValue) {
            if (!is_object($tagValue) || !method_exists($tagValue, 'getTagValue')) {
                continue;
            }
            $elementId = (int)$elementId;
            $koef = static::koef($units[$elementId] ?? null, $units[$parentOf[$elementId] ?? 0] ?? null);
            if ($koef === 1.0) {
                continue;
            }
            foreach (['price', 'oldprice'] as $tag) {
                $value = $tagValue->getTagValue($tag);
                if ($value !== null && $value !== '' && is_numeric($value) && (float)$value > 0) {
                    $tagValue->setTagValue($tag, static::num(round((float)$value * $koef, 2)));
                }
            }
        }
    }

    /**
     * Множитель основной единицы: свойства позиции, а если у неё они не
     * заведены — свойства товара. Нет основной единицы — 1 (цена за штуку).
     */
    protected static function koef(?array $own, ?array $parent): float
    {
        $src = ($own && $own['BASE'] > 0 && $own['KOEFS']) ? $own : $parent;
        if (!$src || $src['BASE'] <= 0) {
            return 1.0;
        }
        return (float)($src['KOEFS'][$src['BASE']] ?? 1.0);
    }

    /**
     * BASE_KOEF и UNIT_KOEF пачкой для товаров и предложений.
     *
     * @return array<int, array{BASE:int, KOEFS:array<int,float>}>
     */
    protected static function loadUnits(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids || !\Bitrix\Main\Loader::includeModule('iblock')) {
            return [];
        }

        $out = [];
        foreach ([static::IBLOCK_OFFERS, static::IBLOCK_PRODUCTS] as $iblockId) {
            $values = [];
            \CIBlockElement::GetPropertyValuesArray(
                $values,
                $iblockId,
                ['ID' => $ids],
                ['CODE' => ['BASE_KOEF', 'UNIT_KOEF']]
            );
            foreach ($values as $id => $props) {
                $koefs = [];
                $vals  = (array)($props['UNIT_KOEF']['VALUE'] ?? []);
                $descs = (array)($props['UNIT_KOEF']['DESCRIPTION'] ?? []);
                foreach ($vals as $i => $v) {
                    $unit = (int)trim((string)($descs[$i] ?? ''));
                    $k    = (float)str_replace(',', '.', (string)$v);
                    if ($unit > 0 && $k > 0) {
                        $koefs[$unit] = $k;
                    }
                }
                $base = $props['BASE_KOEF']['DESCRIPTION'] ?? '';
                if (is_array($base)) {
                    $base = reset($base);
                }
                $out[(int)$id] = ['BASE' => (int)trim((string)$base), 'KOEFS' => $koefs];
            }
        }
        return $out;
    }

    /** 5000.00 → 5000, 6554.14 → 6554.14 */
    protected static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}
