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
 * Второе — наполнение карточки товара (событие onExportOfferWriteData,
 * onOfferWriteData ниже; Ирина, 10 сентября 2026). Модуль выводит только
 * теги, заданные в настройках прайс-листа, и в полном фиде их было мало:
 * в среднем 1,8 фото, ни одной характеристики, у 1106 позиций пустое
 * описание. Поэтому уже собранный узел <offer> дополняем:
 *   - фото: доп. картинки предложения (MORE_PHOTO), галерея товара и
 *     последним кадром чертёж «Профиль доски» (PROFIL) — до 20 штук;
 *   - видео: «Товарное видео» предложения, иначе «Видео в попапе» товара;
 *   - характеристики <param>: у предложения — цвет, длина, вид доски; затем
 *     логистические параметры (длина, ширина, толщина, вес из каталога);
 *     затем поля карточки террасной доски (профиль, ширина, расход, …);
 *     затем «Характеристики» из 1С (название — в описании значения).
 *     Одноимённые не повторяем: у предложения его цвет («Венге») важнее
 *     списка всех цветов товара;
 *   - описание, если модуль его не вывел: детальный текст товара, иначе
 *     анонс, иначе короткий текст из характеристик;
 *   - гарантия производителя (manufacturer_warranty), если заполнена.
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

    // =====================================================================
    // Наполнение карточки: фото, видео, характеристики, описание
    // =====================================================================

    const PICTURES_MAX = 20;
    const DESCRIPTION_MAX = 3000;

    /** Свойства предложения, которые идут в <param> первыми. */
    const OFFER_PARAMS = ['COLOR_REF', 'DLINA', 'VID', 'THICK', 'WIDTH', 'MATERIAL', 'MODEL_OP'];

    /**
     * Поля карточки террасной доски — те, что выводятся в блоке
     * «Характеристики» на детальной, в том же порядке.
     */
    const PRODUCT_PARAMS = [
        'FORMAT', 'PROFIL_DOSKA_DPK', 'IT_8', 'TOLWINA_DOSKA_DPK', 'IT_6', 'DLINA_DOSKA_DPK',
        'IT_11_DOSKA_DPK', 'VES_DOSKA_DPK', 'KL_DOSKA_DPK', 'VWS_NOJKI_DOSKA_DPK',
        'VARIANT_COLORS_DOSKA_DPK', 'SURFACE_DOSKA_DPK', 'IT_1', 'MATERIAL_DOSKA_DPK',
        'COLOR_MAIN_EL', 'GARANTY', 'USAGE_DOSKA_DPK',
    ];

    /** Порядок тегов внутри <offer> после дополнения (остальные — в конец, как стояли). */
    const TAG_ORDER = [
        'url' => 10, 'price' => 20, 'oldprice' => 21, 'currencyId' => 30, 'categoryId' => 40,
        'picture' => 50, 'video' => 55, 'name' => 60, 'vendor' => 70, 'vendorCode' => 71,
        'description' => 80, 'sales_notes' => 85, 'manufacturer_warranty' => 86,
        'country_of_origin' => 87, 'barcode' => 88, 'param' => 90, 'weight' => 95, 'dimensions' => 96,
    ];

    /** @var array<string, array<string, string>> таблица справочника => XML_ID => название */
    protected static $directories = [];

    public static function onOfferWriteData(\Bitrix\Main\Event $event): void
    {
        $params  = $event->getParameters();
        $context = (array)($params['CONTEXT'] ?? []);
        if (!static::isFullSetup($context['SETUP_ID'] ?? 0)) {
            return;
        }

        $results  = $params['TAG_RESULT_LIST'] ?? [];
        $elements = $params['ELEMENT_LIST'] ?? [];
        if (!$results || !\Bitrix\Main\Loader::includeModule('iblock') || !\Bitrix\Main\Loader::includeModule('catalog')) {
            return;
        }

        $parentOf = [];
        foreach ($elements as $element) {
            $id = (int)($element['ID'] ?? 0);
            if ($id) {
                $parentOf[$id] = (int)($element['PARENT_ID'] ?? 0) ?: $id;
            }
        }
        if (!$parentOf) {
            return;
        }

        $data = static::loadCardData(array_keys($parentOf), array_values(array_unique($parentOf)));

        foreach ($results as $elementId => $result) {
            if (!is_object($result) || !method_exists($result, 'getExportElement')) {
                continue;
            }
            $offer = $result->getExportElement();
            if (!$offer instanceof \Yandex\Market\Export\Xml\Data\XmlElement) {
                continue;
            }
            $elementId = (int)$elementId;
            $productId = $parentOf[$elementId] ?? $elementId;
            try {
                static::enrichOffer($offer, $data['own'][$elementId] ?? [], $data['product'][$productId] ?? [], $data['catalog'][$elementId] ?? []);
            } catch (\Throwable $e) {
                // Карточку, которую не удалось дополнить, оставляем как её собрал модуль.
            }
        }
    }

    protected static function enrichOffer(\Yandex\Market\Export\Xml\Data\XmlElement $offer, array $own, array $product, array $catalog): void
    {
        $host = '';
        foreach ($offer->getChild('url') as $u) {
            $parts = parse_url((string)$u->getValue());
            if (!empty($parts['host'])) {
                $host = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];
            }
        }
        if ($host === '') {
            return;
        }

        // --- фото ------------------------------------------------------------
        $have = [];
        foreach ($offer->getChild('picture') as $pic) {
            $have[(string)$pic->getValue()] = true;
        }
        $extra = array_merge($own['MORE_PHOTO'] ?? [], $product['MORE_PHOTO'] ?? [], $product['PROFIL'] ?? []);
        foreach ($extra as $path) {
            if (count($have) >= static::PICTURES_MAX) {
                break;
            }
            $url = $host . $path;
            if (!isset($have[$url])) {
                $offer->addChild('picture', $url);
                $have[$url] = true;
            }
        }

        // --- видео -----------------------------------------------------------
        if (!$offer->getChild('video')) {
            $video = $own['PRODUCT_VIDEO'] ?? '' ?: ($product['POPUP_VIDEO'] ?? '');
            if ($video !== '') {
                $offer->addChild('video', $video);
            }
        }

        // --- характеристики --------------------------------------------------
        $names = [];
        foreach ($offer->getChild('param') as $p) {
            $names[mb_strtolower((string)$p->getAttribute('name'))] = true;
        }
        $add = function (string $name, $value, string $unit = '') use ($offer, &$names) {
            $value = trim(is_array($value) ? implode(', ', array_filter(array_map('trim', $value))) : (string)$value);
            // В «Характеристиках» из 1С бывают названия с двоеточием: «Ротанг:».
            $name  = trim(rtrim(trim($name), ':'));
            // «Цвет основного элемента» у товара дублирует цвет предложения.
            if ($name === 'Цвет основного элемента' && isset($names['цвет'])) {
                return;
            }
            if ($value === '' || $name === '' || isset($names[mb_strtolower($name)])) {
                return;
            }
            $node = $offer->addChild('param', mb_substr($value, 0, 500));
            $node->addAttribute('name', $name);
            if ($unit !== '') {
                $node->addAttribute('unit', $unit);
            }
            $names[mb_strtolower($name)] = true;
        };

        foreach ($own['PARAMS'] ?? [] as $p) {
            $add($p['NAME'], $p['VALUE'], $p['UNIT']);
        }
        // Логистические параметры — как плитки «Длина, мм / Ширина, мм /
        // Толщина, мм / Вес, кг» на карточке. В каталоге вес в граммах.
        foreach (['LENGTH' => 'Длина', 'WIDTH' => 'Ширина', 'HEIGHT' => 'Толщина'] as $f => $label) {
            if (!empty($catalog[$f]) && (float)$catalog[$f] > 0) {
                $add($label, static::num((float)$catalog[$f]), 'мм');
            }
        }
        if (!empty($catalog['WEIGHT']) && (float)$catalog['WEIGHT'] > 0) {
            $add('Вес', static::num((float)$catalog['WEIGHT'] / 1000, 3), 'кг');
        }
        foreach ($product['PARAMS'] ?? [] as $p) {
            $add($p['NAME'], $p['VALUE'], $p['UNIT']);
        }
        foreach ($product['ATTRIBUTES'] ?? [] as $p) {
            $add($p['NAME'], $p['VALUE'], $p['UNIT']);
        }

        // --- гарантия производителя -----------------------------------------
        if (!$offer->getChild('manufacturer_warranty') && ($product['WARRANTY'] ?? false)) {
            $offer->addChild('manufacturer_warranty', 'true');
        }

        // --- описание, если модуль его не вывел --------------------------------
        $hasDescription = false;
        foreach ($offer->getChild('description') as $d) {
            if (trim(strip_tags((string)$d->getValue())) !== '') {
                $hasDescription = true;
            }
        }
        if (!$hasDescription) {
            foreach ($offer->getChild('description') as $d) {
                $offer->removeChild($d);
            }
            $text = $product['TEXT'] ?? '';
            if ($text === '') {
                $parts = [];
                foreach ($offer->getChild('param') as $p) {
                    $parts[] = $p->getAttribute('name') . ': ' . $p->getValue()
                        . ($p->getAttribute('unit') ? ' ' . $p->getAttribute('unit') : '');
                }
                $name = '';
                foreach ($offer->getChild('name') as $n) {
                    $name = (string)$n->getValue();
                }
                if ($parts) {
                    $text = trim($name . '. ' . implode('; ', $parts) . '.');
                }
            }
            if ($text !== '') {
                $html = '<p>' . htmlspecialchars(mb_substr($text, 0, static::DESCRIPTION_MAX), ENT_QUOTES, 'UTF-8') . '</p>';
                $offer->addChild('description', new \Yandex\Market\Export\Xml\Data\CDataValue($html));
            }
        }

        static::sortChildren($offer);
    }

    /**
     * Порядок тегов внутри <offer>: новые фото встают к остальным фото, а не
     * в конец, характеристики — перед весом и габаритами. У XmlElement нет
     * вставки в середину, поэтому переставляем его список детей напрямую.
     * Сортировка устойчивая: теги одного вида сохраняют свой порядок.
     */
    protected static function sortChildren(\Yandex\Market\Export\Xml\Data\XmlElement $offer): void
    {
        $children = $offer->getChildren();
        $indexed = [];
        foreach ($children as $i => $child) {
            $indexed[] = [static::TAG_ORDER[$child->getName()] ?? 100, $i, $child];
        }
        usort($indexed, static fn($a, $b) => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);
        $prop = new \ReflectionProperty(\Yandex\Market\Export\Xml\Data\XmlElement::class, 'children');
        $prop->setAccessible(true);
        $prop->setValue($offer, array_column($indexed, 2));
    }

    /**
     * Всё, что нужно для дополнения, пачкой: свойства позиций (предложения и
     * простые товары), свойства товаров и габариты из каталога.
     */
    protected static function loadCardData(array $elementIds, array $productIds): array
    {
        $out = ['own' => [], 'product' => [], 'catalog' => []];

        // --- свойства предложений ---------------------------------------------
        $offerProps = static::propertyMeta(static::IBLOCK_OFFERS);
        $values = [];
        \CIBlockElement::GetPropertyValuesArray($values, static::IBLOCK_OFFERS, ['ID' => $elementIds],
            ['CODE' => array_merge(['MORE_PHOTO', 'PRODUCT_VIDEO'], static::OFFER_PARAMS)]);
        foreach ($values as $id => $props) {
            $out['own'][(int)$id] = [
                'MORE_PHOTO'    => static::filePaths($props['MORE_PHOTO']['VALUE'] ?? []),
                'PRODUCT_VIDEO' => trim((string)($props['PRODUCT_VIDEO']['VALUE'] ?? '')),
                'PARAMS'        => static::paramsFrom($props, static::OFFER_PARAMS, $offerProps),
            ];
        }

        // --- свойства товаров -------------------------------------------------
        $productProps = static::propertyMeta(static::IBLOCK_PRODUCTS);
        $values = [];
        \CIBlockElement::GetPropertyValuesArray($values, static::IBLOCK_PRODUCTS, ['ID' => $productIds],
            ['CODE' => array_merge(['MORE_PHOTO', 'PROFIL', 'POPUP_VIDEO', 'CML2_ATTRIBUTES'], static::PRODUCT_PARAMS)]);
        foreach ($values as $id => $props) {
            $attributes = [];
            $vals  = (array)($props['CML2_ATTRIBUTES']['VALUE'] ?? []);
            $descs = (array)($props['CML2_ATTRIBUTES']['DESCRIPTION'] ?? []);
            foreach ($vals as $i => $v) {
                [$name, $unit] = static::splitUnit((string)($descs[$i] ?? ''));
                if ($name !== '' && trim((string)$v) !== '') {
                    $attributes[] = ['NAME' => $name, 'VALUE' => trim((string)$v), 'UNIT' => $unit];
                }
            }
            $out['product'][(int)$id] = [
                'MORE_PHOTO'  => static::filePaths($props['MORE_PHOTO']['VALUE'] ?? []),
                'PROFIL'      => static::filePaths($props['PROFIL']['VALUE'] ?? []),
                'POPUP_VIDEO' => trim((string)($props['POPUP_VIDEO']['VALUE'] ?? '')),
                'PARAMS'      => static::paramsFrom($props, static::PRODUCT_PARAMS, $productProps),
                'ATTRIBUTES'  => $attributes,
                'WARRANTY'    => trim((string)($props['GARANTY']['VALUE'] ?? '')) !== '',
                'TEXT'        => '',
            ];
        }

        // Тексты товаров — для позиций, которым модуль не вывел описание.
        if ($productIds) {
            $rs = \CIBlockElement::GetList([], ['IBLOCK_ID' => static::IBLOCK_PRODUCTS, 'ID' => $productIds], false, false,
                ['ID', 'PREVIEW_TEXT', 'DETAIL_TEXT']);
            while ($row = $rs->Fetch()) {
                $id = (int)$row['ID'];
                if (!isset($out['product'][$id])) {
                    continue;
                }
                $text = static::plain($row['DETAIL_TEXT']);
                if ($text === '') {
                    $text = static::plain($row['PREVIEW_TEXT']);
                }
                $out['product'][$id]['TEXT'] = $text;
            }
        }

        // --- габариты и вес из каталога ---------------------------------------
        $rs = \Bitrix\Catalog\ProductTable::getList([
            'filter' => ['@ID' => $elementIds],
            'select' => ['ID', 'LENGTH', 'WIDTH', 'HEIGHT', 'WEIGHT'],
        ]);
        while ($row = $rs->fetch()) {
            $out['catalog'][(int)$row['ID']] = $row;
        }

        return $out;
    }

    /** Характеристики из свойств по списку кодов: название свойства → name/unit. */
    protected static function paramsFrom(array $props, array $codes, array $meta): array
    {
        $out = [];
        foreach ($codes as $code) {
            if (!isset($props[$code], $meta[$code])) {
                continue;
            }
            $value = $props[$code]['VALUE'] ?? '';
            if ($meta[$code]['USER_TYPE'] === 'directory') {
                $value = array_map(static fn($x) => static::directoryName($meta[$code]['TABLE'], (string)$x), (array)$value);
            }
            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map(static fn($x) => trim(strip_tags((string)$x)), $value)));
            }
            $value = trim(strip_tags((string)$value));
            if ($value === '') {
                continue;
            }
            [$name, $unit] = static::splitUnit($meta[$code]['NAME']);
            $out[] = ['NAME' => $name, 'VALUE' => $value, 'UNIT' => $unit];
        }
        return $out;
    }

    /**
     * «Ширина, мм» → [«Ширина», «мм»]; «Вес 1 пог. метра, кг (справочно)» →
     * [«Вес 1 пог. метра (справочно)», «кг»]. Единица — короткий хвост после
     * последней запятой; всё прочее оставляем в названии как есть.
     */
    protected static function splitUnit(string $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        if (preg_match('/^(.+?),\s*([^,()]{1,12}?)\s*(\(.*\))?$/u', $name, $m) && !preg_match('/\d/u', $m[2])) {
            return [trim($m[1] . (isset($m[3]) && $m[3] !== '' ? ' ' . $m[3] : '')), trim($m[2])];
        }
        return [$name, ''];
    }

    /** @return array<string, array{NAME:string, USER_TYPE:string, TABLE:string}> */
    protected static function propertyMeta(int $iblockId): array
    {
        static $cache = [];
        if (!isset($cache[$iblockId])) {
            $cache[$iblockId] = [];
            $rs = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId]);
            while ($p = $rs->Fetch()) {
                $settings = is_array($p['USER_TYPE_SETTINGS']) ? $p['USER_TYPE_SETTINGS'] : (@unserialize((string)$p['USER_TYPE_SETTINGS']) ?: []);
                $cache[$iblockId][$p['CODE']] = [
                    'NAME'      => (string)$p['NAME'],
                    'USER_TYPE' => (string)$p['USER_TYPE'],
                    'TABLE'     => (string)($settings['TABLE_NAME'] ?? ''),
                ];
            }
        }
        return $cache[$iblockId];
    }

    /** Название значения справочника (HL-блок) по его XML_ID. */
    protected static function directoryName(string $table, string $xmlId): string
    {
        if ($table === '' || $xmlId === '' || !preg_match('/^[a-z0-9_]+$/i', $table)) {
            return '';
        }
        if (!isset(static::$directories[$table])) {
            static::$directories[$table] = [];
            try {
                $rs = \Bitrix\Main\Application::getConnection()->query("SELECT UF_XML_ID, UF_NAME FROM `{$table}`");
                while ($row = $rs->fetch()) {
                    static::$directories[$table][(string)$row['UF_XML_ID']] = (string)$row['UF_NAME'];
                }
            } catch (\Throwable $e) {
                // нет таблицы — значения просто не выведем
            }
        }
        return static::$directories[$table][$xmlId] ?? '';
    }

    /** Пути файлов (b_file) для множественного свойства-файла; только существующие на диске. */
    protected static function filePaths($ids): array
    {
        $out = [];
        foreach ((array)$ids as $fid) {
            $fid = (int)$fid;
            if (!$fid) {
                continue;
            }
            $path = \CFile::GetPath($fid);
            if ($path && is_file($_SERVER['DOCUMENT_ROOT'] . $path)) {
                $out[] = $path;
            }
        }
        return $out;
    }

    /** Текст без разметки и лишних пробелов. */
    protected static function plain($html): string
    {
        $s = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], ' ', (string)$html)), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /** 5000.00 → 5000, 6554.14 → 6554.14; для веса — три знака (8.127 кг) */
    protected static function num(float $v, int $decimals = 2): string
    {
        return rtrim(rtrim(number_format($v, $decimals, '.', ''), '0'), '.');
    }
}
