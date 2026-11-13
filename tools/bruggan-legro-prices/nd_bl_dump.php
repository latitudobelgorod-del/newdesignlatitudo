<?php
// Выгрузка номенклатуры бренда (php nd_bl_dump.php <ID бренда ИБ 12>: 1625 Bruggan, 23922 Legro): товары и ТП, цена BASE, единицы, UNIT_KOEF. Только чтение.
$_SERVER['DOCUMENT_ROOT'] = getenv('HOME') . '/www/latitudo.ru';
define('NO_KEEP_STATISTIC', true); define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock'); CModule::IncludeModule('catalog');
$meas = []; $m = CCatalogMeasure::getList(); while ($a = $m->Fetch()) $meas[(int)$a['ID']] = $a['SYMBOL'];
$secs = []; $r = CIBlockSection::GetList([], ['IBLOCK_ID' => 19], false, ['ID', 'NAME', 'GLOBAL_ACTIVE']); while ($a = $r->Fetch()) $secs[(int)$a['ID']] = $a;
function koefs($ib, $id) { $o = []; $p = CIBlockElement::GetProperty($ib, $id, [], ['CODE' => 'UNIT_KOEF']); while ($v = $p->Fetch()) if ($v['VALUE'] !== '' && $v['VALUE'] !== null) $o[(int)$v['DESCRIPTION']] = (float)str_replace(',', '.', $v['VALUE']); $b = CIBlockElement::GetProperty($ib, $id, [], ['CODE' => 'BASE_KOEF'])->Fetch(); return [$o, (int)($b['DESCRIPTION'] ?? 0)]; }
$rows = [];
$r = CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => 19, 'PROPERTY_BRAND' => (int)$argv[1]], false, false, ['ID', 'NAME', 'ACTIVE', 'IBLOCK_SECTION_ID', 'DETAIL_PAGE_URL']);
while ($e = $r->GetNext(false, false)) {
    $sec = $secs[(int)$e['IBLOCK_SECTION_ID']] ?? null;
    $url = 'https://latitudo.ru' . $e['DETAIL_PAGE_URL'];
    $items = [];
    $o = CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => 20, 'PROPERTY_CML2_LINK' => $e['ID']], false, false, ['ID', 'NAME', 'ACTIVE']);
    while ($x = $o->Fetch()) $items[] = [20, $x];
    if (!$items) $items[] = [19, ['ID' => $e['ID'], 'NAME' => $e['NAME'], 'ACTIVE' => $e['ACTIVE']]];
    foreach ($items as [$ib, $x]) {
        $cp = CCatalogProduct::GetByID($x['ID']); $pr = CPrice::GetBasePrice($x['ID']);
        [$k, $base] = koefs($ib, $x['ID']);
        $art = CIBlockElement::GetProperty($ib, $x['ID'], [], ['CODE' => $ib == 20 ? 'ARTICLE' : 'CML2_ARTICLE'])->Fetch();
        $rows[] = [
            'pid' => (int)$e['ID'], 'pname' => $e['NAME'], 'pactive' => $e['ACTIVE'],
            'sec' => $sec['NAME'] ?? '', 'secon' => $sec['GLOBAL_ACTIVE'] ?? 'N',
            'oid' => $ib == 20 ? (int)$x['ID'] : 0, 'name' => $x['NAME'], 'active' => $x['ACTIVE'],
            'art' => $art['VALUE'] ?? '', 'price' => $pr ? (float)$pr['PRICE'] : null, 'priceid' => $pr['ID'] ?? null,
            'measure' => $meas[(int)$cp['MEASURE']] ?? '', 'koef' => array_combine(array_map(fn($i) => $meas[$i] ?? $i, array_keys($k)), array_values($k)),
            'base' => $meas[$base] ?? '', 'url' => $url . ($ib == 20 ? '?oid=' . $x['ID'] : ''),
        ];
    }
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
