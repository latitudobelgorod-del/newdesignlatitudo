<?php
/**
 * SEO: вес раздела «Террасная доска» (Ирина, 14 сентября 2026).
 *
 * По запросам «террасная доска…» Яндекс выбирал релевантной не раздел, а
 * подраздел полнотелой доски: на сам раздел почти не было ссылок из
 * содержимого сайта (только хлебные крошки с одним и тем же текстом).
 *
 * Здесь две вещи:
 *
 * 1. Ссылка «Вся террасная доска ДПК» — с карточек товаров раздела и его
 *    подразделов (main6_newdesign) и из блока «Материалы» проектов портфолио
 *    (news/projects_newdesign), если среди материалов есть такая доска.
 *    Разделы и тексты ссылок — в ND_SEO_SECTION_LINKS: добавить раздел =
 *    дописать строку.
 *
 * 2. Цена «от … ₽/м²» в title и description раздела. В SEO-шаблоне раздела
 *    стоит метка #ND_PRICE_M2_FROM_<ID раздела>#, её в готовой странице
 *    заменяет ndPriceFromTokens() на « от 2 100 ₽/м²». Считать цену в самом
 *    SEO-шаблоне нельзя: Битрикс сохраняет вычисленный title в базе и не
 *    пересчитывает его при смене цен.
 *
 *    Цена — минимальная среди позиций раздела и подразделов в полном
 *    товарном фиде (latitudo_full.xml). С 16 сентября 2026 в фиде цена за
 *    штуку (см. latitudo_market_feed.php), поэтому множитель основной
 *    единицы, который раньше давал фид, считаем здесь сами — по BASE_KOEF и
 *    UNIT_KOEF, той же формулой. Число в заголовке от этого не изменилось.
 *    Фид собирается раз в сутки — значит, и цена в заголовке обновляется раз
 *    в сутки. Цены по городам одинаковые (проверено по всем фидам), поэтому
 *    берём Москву. Нет фида или позиций — метка заменяется на « за м²»,
 *    без цифры.
 */

const ND_SEO_SECTION_LINKS = [
    98 => ['URL' => '/catalog/terrasnaya-doska-iz-dpk/', 'TEXT' => 'Вся террасная доска ДПК'],
];

/**
 * Ссылка на общий раздел для товара: по цепочке разделов от раздела товара
 * вверх. $arResult['SECTION']['PATH'] у нашей карточки пустой, поэтому
 * цепочку берём сами (один запрос; результат карточки и так в кеше компонента).
 *
 * @return array{URL:string, TEXT:string}|null
 */
function ndSeoSectionLinkForSection($sectionId): ?array
{
    $sectionId = (int)$sectionId;
    if ($sectionId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return null;
    }
    static $memo = [];
    if (!array_key_exists($sectionId, $memo)) {
        $memo[$sectionId] = null;
        $chain = CIBlockSection::GetNavChain(false, $sectionId, ['ID']);
        while ($section = $chain->Fetch()) {
            $id = (int)$section['ID'];
            if (isset(ND_SEO_SECTION_LINKS[$id])) {
                $memo[$sectionId] = ND_SEO_SECTION_LINKS[$id];
                break;
            }
        }
    }
    return $memo[$sectionId];
}

/**
 * Ссылка на общий раздел для набора товаров (материалы проекта): первый
 * раздел из ND_SEO_SECTION_LINKS, в котором есть хотя бы один активный товар.
 *
 * @return array{URL:string, TEXT:string}|null
 */
function ndSeoSectionLinkForGoods($ids): ?array
{
    $ids = array_values(array_filter(array_map('intval', (array)$ids)));
    if (!$ids || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return null;
    }
    foreach (ND_SEO_SECTION_LINKS as $sectionId => $link) {
        $count = (int)CIBlockElement::GetList([], [
            'ID' => $ids,
            'ACTIVE' => 'Y',
            'SECTION_ID' => $sectionId,
            'INCLUDE_SUBSECTIONS' => 'Y',
        ], []);
        if ($count > 0) {
            return $link;
        }
    }
    return null;
}

/**
 * Множитель основной единицы для позиций фида: свойства BASE_KOEF (ID единицы
 * в DESCRIPTION) и UNIT_KOEF самой позиции, а если у неё они не заведены —
 * товара-родителя ($parentOf; у модуля это атрибут group_id предложения).
 * Нет основной единицы — 1, цена остаётся за штуку.
 *
 * До 16 сентября 2026 ровно это делал обработчик фида, теперь цена в фиде
 * за штуку — и множитель нужен здесь.
 *
 * @param array<int,int> $parentOf ID позиции => ID товара
 * @return array<int,float> ID позиции => множитель
 */
function ndBaseUnitKoef(array $parentOf): array
{
    if (!$parentOf || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $ids = array_values(array_unique(array_merge(array_keys($parentOf), array_values($parentOf))));
    $units = [];
    foreach ([20, 19] as $iblockId) {
        $values = [];
        \CIBlockElement::GetPropertyValuesArray($values, $iblockId, ['ID' => $ids],
            ['CODE' => ['BASE_KOEF', 'UNIT_KOEF']]);
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
            $units[(int)$id] = ['BASE' => (int)trim((string)$base), 'KOEFS' => $koefs];
        }
    }

    $out = [];
    foreach ($parentOf as $id => $productId) {
        $own = $units[$id] ?? null;
        $src = ($own && $own['BASE'] > 0 && $own['KOEFS']) ? $own : ($units[$productId] ?? null);
        $out[$id] = ($src && $src['BASE'] > 0) ? (float)($src['KOEFS'][$src['BASE']] ?? 1.0) : 1.0;
    }
    return $out;
}

/**
 * Минимальная цена позиций раздела (с подразделами) из полного фида,
 * приведённая к основной единице, кеш 6 ч. Ключ кеша включает время сборки
 * фида — после ночной пересборки цена новая.
 */
function ndSectionMinPrice(int $sectionId): ?float
{
    $file = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/catalog_export/latitudo_full.xml';
    if (!is_file($file)) {
        return null;
    }
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    $key = 'nd_min_price_' . $sectionId . '_' . filemtime($file);
    if ($cache->initCache(6 * 3600, $key, '/nd/section_min_price')) {
        $price = $cache->getVars();
        return $price > 0 ? (float)$price : null;
    }
    $cache->startDataCache();

    $xml = (string)file_get_contents($file);
    $parents = [];
    preg_match_all('~<category id="(\d+)"(?: parentId="(\d+)")?~', $xml, $m, PREG_SET_ORDER);
    foreach ($m as $c) {
        $parents[(int)$c[1]] = isset($c[2]) ? (int)$c[2] : 0;
    }
    $inTree = function (int $id) use ($parents, $sectionId): bool {
        for ($i = 0; $id && $i < 20; $i++) {
            if ($id === $sectionId) {
                return true;
            }
            $id = $parents[$id] ?? 0;
        }
        return false;
    };
    // Цена за штуку и ID товара-родителя (group_id) — по ним ниже множитель.
    $prices   = [];
    $parentOf = [];
    preg_match_all(
        '~<offer id="(\d+)"([^>]*)>.*?<price>([\d.]+)</price>.*?<categoryId>(\d+)</categoryId>~s',
        $xml, $m, PREG_SET_ORDER
    );
    foreach ($m as $o) {
        $price = (float)$o[3];
        if ($price <= 0 || !$inTree((int)$o[4])) {
            continue;
        }
        $id = (int)$o[1];
        $prices[$id]   = $price;
        $parentOf[$id] = preg_match('~\\bgroup_id="(\d+)"~', $o[2], $g) ? (int)$g[1] : $id;
    }
    unset($xml);

    $min = 0.0;
    foreach (ndBaseUnitKoef($parentOf) as $id => $koef) {
        $price = round($prices[$id] * $koef, 2);
        if ($min == 0.0 || $price < $min) {
            $min = $price;
        }
    }

    $cache->endDataCache($min);
    return $min > 0 ? $min : null;
}

/**
 * OnEndBufferContent: #ND_PRICE_M2_FROM_<ID>#  →  « от 2 100 ₽/м²» / « за м²».
 * Порядок 10040 — до ndOpenGraphFallback (10050), чтобы og:title получил
 * уже готовый заголовок.
 */
function ndPriceFromTokens(&$content)
{
    if (!is_string($content) || strpos($content, '#ND_PRICE_M2_FROM_') === false) {
        return;
    }
    $content = preg_replace_callback('~#ND_PRICE_M2_FROM_(\d+)#~', function ($m) {
        $price = ndSectionMinPrice((int)$m[1]);
        if (!$price) {
            return ' за м²';
        }
        return ' от ' . number_format(floor($price), 0, ',', "\u{00A0}") . "\u{00A0}₽/м²";
    }, $content);
}
