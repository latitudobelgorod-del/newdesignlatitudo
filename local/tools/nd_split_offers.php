<?php
/**
 * Разбивка товара с торговыми предложениями на отдельные товары: каждое ТП
 * переезжает к своему новому товару и сохраняет свой ID (корзины, заказы,
 * остатки, цены, ссылки ?pid= остаются). Старый товар выключается.
 *
 * Новый товар: название и фото — из ТП, анонс, свойства и EDITOR1 — из старого
 * товара (диапазон высоты в тексте заменяется на диапазон модели), «Линейка» +
 * «Диапазон высоты, мм» — чтобы на карточке были кнопки соседних высот.
 *
 *   php local/tools/nd_split_offers.php <DOCUMENT_ROOT> <PRODUCT_ID> [--apply]
 *   php local/tools/nd_split_offers.php <DOCUMENT_ROOT> rollback <файл бэкапа>
 * Без --apply только показывает, что будет сделано. Бэкап — в домашнюю папку.
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/bitrix')) {
	exit("usage: php nd_split_offers.php <DOCUMENT_ROOT> <PRODUCT_ID|rollback> [--apply|файл]\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog');

const ND_IB = 19;
const ND_SKU_IB = 20;
// товар → линейка (XML_ID, название) и как из названия ТП достать диапазон и строку для замены в тексте
$plans = [
	7939 => [
		'LINE' => ['HILST_LIFT_LAG', 'HILST ЛИФТ для лаг'],
		're' => '~ЛИФТ H\d+ (\d+(?:,\d+)?)-(\d+(?:,\d+)?) мм~u',
		'text_from' => ['от 45 до 290 мм'],
	],
];
// не копируем: считаются заново или уникальны для товара
$skipProps = ['MINIMUM_PRICE', 'MAXIMUM_PRICE', 'CML2_ARTICLE', 'ND_SHORT_URI', 'LINE', 'HEIGHT_RANGE'];

function ndClearCaches(array $ids): void
{
	foreach ($ids as $id) {
		\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(ND_IB, $id);
	}
	CIBlock::clearIblockTagCache(ND_IB);
	CIBlock::clearIblockTagCache(ND_SKU_IB);
}

$mode = $argv[2] ?? '';
if ($mode === 'rollback') {
	$bk = json_decode(file_get_contents($argv[3]), true);
	$old = (int) $bk['PRODUCT_ID'];
	$el = new CIBlockElement;
	// сначала вернуть ТП: удаление товара удаляет и его торговые предложения
	foreach ($bk['OFFERS'] as $offerId => $newId) {
		CIBlockElement::SetPropertyValuesEx($offerId, ND_SKU_IB, ['CML2_LINK' => $old]);
		echo "ТП $offerId → $old\n";
	}
	$el->Update($old, ['ACTIVE' => $bk['ACTIVE']]) or print("ERR $old {$el->LAST_ERROR}\n");
	\Bitrix\Catalog\Product\Sku::updateAvailable($old, ND_IB);
	foreach ($bk['OFFERS'] as $offerId => $newId) {
		$left = CCatalogSKU::getOffersList([$newId], ND_IB);
		if (!empty($left[$newId])) {
			echo "у $newId остались ТП — не удаляю\n";
			continue;
		}
		CIBlockElement::Delete($newId) ? print("удалён $newId\n") : print("ERR удаление $newId\n");
	}
	ndClearCaches([$old]);
	exit("откат готов\n");
}

$old = (int) $mode;
if (!isset($plans[$old])) {
	exit("нет плана для товара $old\n");
}
$plan = $plans[$old];
$apply = in_array('--apply', $argv, true);

$src = CIBlockElement::GetByID($old)->Fetch();
if (!$src || (int) $src['IBLOCK_ID'] !== ND_IB) {
	exit("товар $old не найден\n");
}
$offers = CCatalogSKU::getOffersList([$old], ND_IB, [], ['ID', 'NAME', 'SORT', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']);
$offers = $offers[$old] ?? [];
if (!$offers) {
	exit("у товара $old нет торговых предложений\n");
}
echo "Товар $old «{$src['NAME']}» [{$src['ACTIVE']}], раздел {$src['IBLOCK_SECTION_ID']}, ТП: ".count($offers)."\n";

// свойства старого товара: код → [VALUE, DESCRIPTION]...
$props = [];
$r = CIBlockElement::GetProperty(ND_IB, $old, ['sort' => 'asc', 'id' => 'asc'], []);
while ($p = $r->Fetch()) {
	if (in_array($p['CODE'], $skipProps, true) || $p['VALUE'] === null || $p['VALUE'] === '' || $p['VALUE'] === false) {
		continue;
	}
	$props[$p['CODE']][] = ['VALUE' => $p['VALUE'], 'DESCRIPTION' => (string) $p['DESCRIPTION']];
}
echo "Копирую свойства: ".implode(', ', array_keys($props))."\n";

$cat = CCatalogProduct::GetByID($old);
$sections = [];
$g = CIBlockElement::GetElementGroups($old, true, ['ID']);
while ($s = $g->Fetch()) {
	$sections[] = (int) $s['ID'];
}

// линейка: значение списка LINE (заводим, если нет)
$lineEnum = 0;
$r = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => ND_IB, 'CODE' => 'LINE', 'XML_ID' => $plan['LINE'][0]]);
if ($e = $r->Fetch()) {
	$lineEnum = (int) $e['ID'];
}
echo "Линейка {$plan['LINE'][0]}: ".($lineEnum ?: 'будет заведена')."\n";

// кто ещё ссылается на старый товар (покажем, сами не меняем)
foreach (['EXPANDABLES', 'ASSOCIATED'] as $code) {
	$r = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IB, 'PROPERTY_'.$code => $old], false, false, ['ID', 'NAME', 'ACTIVE']);
	while ($x = $r->Fetch()) {
		echo "  ссылка $code: {$x['ID']} [{$x['ACTIVE']}] {$x['NAME']}\n";
	}
}

$rows = [];
foreach ($offers as $o) {
	if (!preg_match($plan['re'], $o['NAME'], $m)) {
		exit("не разобрала диапазон в «{$o['NAME']}»\n");
	}
	$range = $m[1].'-'.$m[2];
	$code = CUtil::translit(trim($o['NAME']), 'ru', ['max_len' => 100, 'change_case' => 'L', 'replace_space' => '-', 'replace_other' => '-', 'delete_repeat_replace' => true]);
	if (CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IB, '=CODE' => $code], false, ['nTopCount' => 1], ['ID'])->Fetch()) {
		exit("код $code уже занят\n");
	}
	$price = CPrice::GetBasePrice($o['ID']);
	$rows[] = ['OFFER' => $o, 'RANGE' => $range, 'FROM' => str_replace('-', ' до ', $range), 'CODE' => $code, 'PRICE' => (float) $price['PRICE']];
	echo "  ТП {$o['ID']} «{$o['NAME']}» → новый товар /$code/, высота $range, цена {$price['PRICE']}\n";
}
if (!$apply) {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

if (!$lineEnum) {
	$prop = CIBlockProperty::GetList([], ['IBLOCK_ID' => ND_IB, 'CODE' => 'LINE'])->Fetch();
	$lineEnum = (int) (new CIBlockPropertyEnum)->Add(['PROPERTY_ID' => $prop['ID'], 'VALUE' => $plan['LINE'][1], 'XML_ID' => $plan['LINE'][0], 'SORT' => 500]);
	echo "заведена линейка $lineEnum\n";
}

$backup = ['PRODUCT_ID' => $old, 'ACTIVE' => $src['ACTIVE'], 'OFFERS' => []];
$bkFile = (getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT'])).'/nd_split_offers_backup_'.$old.'_'.date('Ymd-His').'.json';
$el = new CIBlockElement;
$newIds = [];
foreach ($rows as $row) {
	$o = $row['OFFER'];
	$from = 'от '.$row['FROM'].' мм';
	$text = str_replace($plan['text_from'], $from, (string) $src['PREVIEW_TEXT']);
	$fields = [
		'IBLOCK_ID' => ND_IB,
		'IBLOCK_SECTION' => $sections,
		'IBLOCK_SECTION_ID' => (int) $src['IBLOCK_SECTION_ID'],
		'ACTIVE' => 'Y',
		'SORT' => (int) $src['SORT'] + (int) $o['SORT'],
		'NAME' => trim($o['NAME']),
		'CODE' => $row['CODE'],
		'PREVIEW_TEXT' => $text,
		'PREVIEW_TEXT_TYPE' => $src['PREVIEW_TEXT_TYPE'],
		'DETAIL_TEXT' => (string) $src['DETAIL_TEXT'],
		'DETAIL_TEXT_TYPE' => $src['DETAIL_TEXT_TYPE'],
		// копии файлов, а не общие ID: удаление одного элемента не должно сносить фото другого
		'PREVIEW_PICTURE' => CFile::MakeFileArray($o['PREVIEW_PICTURE'] ?: $src['PREVIEW_PICTURE']),
		'DETAIL_PICTURE' => CFile::MakeFileArray($o['DETAIL_PICTURE'] ?: $src['DETAIL_PICTURE']),
	];
	$newId = (int) $el->Add($fields);
	if (!$newId) {
		file_put_contents($bkFile, json_encode($backup, JSON_PRETTY_PRINT));
		exit("ERR добавление «{$fields['NAME']}»: {$el->LAST_ERROR}\nбэкап: $bkFile\n");
	}
	$backup['OFFERS'][$o['ID']] = $newId;
	file_put_contents($bkFile, json_encode($backup, JSON_PRETTY_PRINT));

	$set = $props;
	if (isset($set['EDITOR1'])) {
		foreach ($set['EDITOR1'] as &$v) {
			$v['VALUE'] = str_replace($plan['text_from'], $from, $v['VALUE']);
		}
		unset($v);
	}
	$set['MINIMUM_PRICE'] = $row['PRICE'];
	$set['MAXIMUM_PRICE'] = $row['PRICE'];
	$set['CML2_ARTICLE'] = $newId;
	$set['LINE'] = $lineEnum;
	$set['HEIGHT_RANGE'] = $row['RANGE'];
	CIBlockElement::SetPropertyValuesEx($newId, ND_IB, $set);

	$catFields = ['ID' => $newId, 'TYPE' => \Bitrix\Catalog\ProductTable::TYPE_SKU, 'QUANTITY' => 0];
	foreach (['VAT_ID', 'VAT_INCLUDED', 'MEASURE', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'SUBSCRIBE'] as $k) {
		if (isset($cat[$k]) && $cat[$k] !== '') {
			$catFields[$k] = $cat[$k];
		}
	}
	CCatalogProduct::Add($catFields);
	CIBlockElement::SetPropertyValuesEx($o['ID'], ND_SKU_IB, ['CML2_LINK' => $newId]);
	\Bitrix\Catalog\Product\Sku::updateAvailable($newId, ND_IB);
	$newIds[] = $newId;
	echo "создан $newId «{$fields['NAME']}», ТП {$o['ID']} перенесено\n";
}
$el->Update($old, ['ACTIVE' => 'N']) or print("ERR выключение $old: {$el->LAST_ERROR}\n");
ndClearCaches(array_merge([$old], $newIds));
echo "товар $old выключен\nбэкап: $bkFile\n";
