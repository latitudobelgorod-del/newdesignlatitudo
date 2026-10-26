<?php
/**
 * Раздел «Распродажа» каталога из фида metatool (26.10.2026).
 *
 *   php nd_sale_feed.php <DOCROOT>                    — сухой прогон: план в plan.csv, в базе ничего не меняется
 *   php nd_sale_feed.php <DOCROOT> apply              — завести раздел, товары, остатки, цены и скидки
 *   php nd_sale_feed.php <DOCROOT> rollback=<created.json> — удалить всё, что завёл apply
 *   --feed=<файл или адрес>  — фид (по умолчанию https://metatool.ru/api/feeds/latitudo-rasprodazha.json)
 *
 * Как договорились с Ириной:
 *   - раздел верхнего уровня ИБ 19, символьный код rasprodazha;
 *   - каждый товар фида — отдельный простой товар без торговых предложений: название, символьный код и
 *     XML_ID как в фиде (название с «[Распродажа]»);
 *   - единицы и цены как в фиде, без коэффициентов: штука, цена BASE = price_retail;
 *   - до price_sale цену опускает правило работы с корзиной (как у акций −15%): скидка в рублях
 *     с каждой штуки, одно правило на одинаковую сумму скидки, XML_ID nd_sale_…;
 *     нет price_retail — цена BASE = price_sale, без скидки;
 *   - остатки по складам: город склада → склад сайта (Белгород 1, Воронеж 2, Москва 3, Краснодар 4);
 *   - порядок в разделе — по общему остатку (больше — выше): сортировка раздела по умолчанию идёт
 *     по SORT, поэтому SORT = 1 000 000 − остаток×100.
 * Фото не ставим — их подбирает nd_sale_photos.php.
 */

$args = array_slice($argv, 1);
$root = array_shift($args);
$opt = ['feed' => 'https://metatool.ru/api/feeds/latitudo-rasprodazha.json'];
$mode = 'dry';
$rollbackFile = '';
foreach ($args as $a) {
	if ($a === 'apply') {
		$mode = 'apply';
	} elseif (strpos($a, 'rollback=') === 0) {
		$mode = 'rollback';
		$rollbackFile = substr($a, 9);
	} elseif (preg_match('/^--([\w-]+)=(.*)$/', $a, $m) && array_key_exists($m[1], $opt)) {
		$opt[$m[1]] = $m[2];
	} else {
		fwrite(STDERR, "Неизвестный параметр: $a\n");
		exit(1);
	}
}
if (!$root || !is_dir($root)) {
	fwrite(STDERR, "Укажите корень сайта первым параметром\n");
	exit(1);
}

const ND_IBLOCK_ID = 19;
const ND_PRICE_GROUP = 1;
const ND_SECTION_CODE = 'rasprodazha';
const ND_SECTION_NAME = 'Распродажа';
const ND_STORE_BY_CITY = ['Белгород' => 1, 'Воронеж' => 2, 'Москва' => 3, 'Краснодар' => 4];
// группы покупателей — как у действующих акций (правило 264)
const ND_USER_GROUPS = [1, 2, 3, 4, 5, 6, 7, 8, 9];

$_SERVER['DOCUMENT_ROOT'] = realpath($root);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
@set_time_limit(0);
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');
\Bitrix\Main\Loader::includeModule('sale');

$outDir = getenv('HOME') . '/sale-feed/' . date('Y-m-d_His') . '_' . $mode;
if (!getenv('HOME')) {
	$outDir = __DIR__ . '/out/' . date('Y-m-d_His') . '_' . $mode;
}
mkdir($outDir, 0700, true);

if ($mode === 'rollback') {
	$c = json_decode((string)file_get_contents($rollbackFile), true);
	if (!is_array($c)) {
		fwrite(STDERR, "Не читается $rollbackFile\n");
		exit(1);
	}
	foreach ($c['discounts'] ?? [] as $id) {
		CSaleDiscount::Delete((int)$id);
	}
	$n = 0;
	foreach ($c['elements'] ?? [] as $id) {
		if (CIBlockElement::Delete((int)$id)) {
			$n++;
		}
	}
	if (!empty($c['section'])) {
		CIBlockSection::Delete((int)$c['section']);
	}
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	echo 'Удалено товаров: ' . $n . ', правил скидок: ' . count($c['discounts'] ?? []) . ($c['section'] ? ', раздел ' . $c['section'] : '') . "\n";
	exit(0);
}

// --- фид
$raw = @file_get_contents($opt['feed'], false, stream_context_create(['http' => ['timeout' => 60]]));
$feed = $raw ? json_decode($raw, true) : null;
if (!$feed || empty($feed['items']) || ($feed['feed'] ?? '') !== 'rasprodazha') {
	fwrite(STDERR, "Фид не прочитался\n");
	exit(1);
}
copy('data://text/plain;base64,' . base64_encode($raw), $outDir . '/feed.json');

// --- что уже есть
$section = CIBlockSection::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID, '=CODE' => ND_SECTION_CODE], false, ['ID'])->Fetch();
$sectionId = $section ? (int)$section['ID'] : 0;
$usedXml = [];
$usedCode = [];
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID], false, false, ['ID', 'XML_ID', 'CODE']);
while ($r = $rs->Fetch()) {
	$usedXml[$r['XML_ID']] = (int)$r['ID'];
	$usedCode[$r['CODE']] = (int)$r['ID'];
}

// --- план
$plan = [];
$problems = [];
foreach ($feed['items'] as $it) {
	$xml = trim((string)$it['xml_id']);
	$name = trim((string)$it['name']);
	$code = trim((string)$it['code']);
	if ($xml === '' || $name === '' || $code === '') {
		$problems[] = "нет xml_id/названия/кода: " . json_encode($it, JSON_UNESCAPED_UNICODE);
		continue;
	}
	if (isset($usedXml[$xml])) {
		$problems[] = "уже есть (XML_ID $xml → ID {$usedXml[$xml]}): $name";
		continue;
	}
	if (isset($usedCode[$code])) {
		$problems[] = "символьный код занят (ID {$usedCode[$code]}): $code";
		continue;
	}
	$usedCode[$code] = -1;
	$retail = (float)($it['price_retail'] ?? 0);
	$sale = (float)($it['price_sale'] ?? 0);
	$base = $retail > 0 ? $retail : $sale;
	$off = ($retail > 0 && $sale > 0 && $sale < $retail) ? round($retail - $sale, 2) : 0.0;
	$stores = array_fill_keys(array_values(ND_STORE_BY_CITY), 0.0);
	foreach ($it['stock'] as $s) {
		$sid = ND_STORE_BY_CITY[trim((string)$s['city'])] ?? null;
		if ($sid === null) {
			$problems[] = "неизвестный город склада «{$s['city']}»: $name";
			continue;
		}
		$stores[$sid] += (float)$s['quantity'];
	}
	$total = array_sum($stores);
	$plan[] = [
		'xml' => $xml, 'name' => $name, 'code' => $code, 'sku' => (string)$it['sku'], 'unit' => (string)$it['unit'],
		'base' => $base, 'sale' => $sale, 'off' => $off, 'stores' => $stores, 'total' => $total,
		'sort' => max(1, 1000000 - (int)round($total * 100)),
	];
	if ($base <= 0) {
		$problems[] = "нет цены: $name";
	}
	if (mb_strtolower(trim((string)$it['unit'])) !== 'шт') {
		$problems[] = "единица «{$it['unit']}» не штука — ставим штуку: $name";
	}
}
usort($plan, function ($a, $b) { return $a['sort'] <=> $b['sort']; });

$fh = fopen($outDir . '/plan.csv', 'w');
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, ['XML_ID', 'Название', 'Символьный код', 'sku', 'Цена BASE', 'Скидка с шт.', 'Цена со скидкой',
	'Белгород', 'Воронеж', 'Москва', 'Краснодар', 'Всего', 'SORT'], ';');
foreach ($plan as $p) {
	fputcsv($fh, [$p['xml'], $p['name'], $p['code'], $p['sku'], $p['base'], $p['off'], round($p['base'] - $p['off'], 2),
		$p['stores'][1], $p['stores'][2], $p['stores'][3], $p['stores'][4], $p['total'], $p['sort']], ';');
}
fclose($fh);

$groups = [];
foreach ($plan as $p) {
	if ($p['off'] > 0) {
		$groups[sprintf('%.2f', $p['off'])][] = $p['xml'];
	}
}
echo 'Раздел: ' . ($sectionId ? "есть, ID $sectionId" : 'будет создан') . "\n";
echo 'Новых товаров: ' . count($plan) . ', правил скидок: ' . count($groups) . "\n";
foreach ($problems as $pr) {
	echo "  ! $pr\n";
}
echo "План: $outDir/plan.csv\n";
if ($mode !== 'apply') {
	echo "Сухой прогон — в базе ничего не изменено. Завести: добавьте apply\n";
	exit(0);
}

// --- запись
$created = ['section' => 0, 'elements' => [], 'discounts' => []];
$save = function () use (&$created, $outDir) {
	file_put_contents($outDir . '/created.json', json_encode($created));
};
if (!$sectionId) {
	$bs = new CIBlockSection();
	$sectionId = (int)$bs->Add(['IBLOCK_ID' => ND_IBLOCK_ID, 'ACTIVE' => 'Y', 'NAME' => ND_SECTION_NAME,
		'CODE' => ND_SECTION_CODE, 'SORT' => 1000]);
	if (!$sectionId) {
		fwrite(STDERR, "Раздел не создан: {$bs->LAST_ERROR}\n");
		exit(1);
	}
	$created['section'] = $sectionId;
	$save();
	echo "Раздел создан: ID $sectionId\n";
}

$idByXml = [];
$el = new CIBlockElement();
foreach ($plan as $p) {
	$id = (int)$el->Add([
		'IBLOCK_ID' => ND_IBLOCK_ID,
		'ACTIVE' => 'Y',
		'NAME' => $p['name'],
		'CODE' => $p['code'],
		'XML_ID' => $p['xml'],
		'SORT' => $p['sort'],
		'IBLOCK_SECTION_ID' => $sectionId,
	], false, true, false);
	if (!$id) {
		fwrite(STDERR, "{$p['xml']} {$p['name']}: {$el->LAST_ERROR}\n");
		continue;
	}
	$created['elements'][] = $id;
	$save();
	$idByXml[$p['xml']] = $id;
	CIBlockElement::SetPropertyValuesEx($id, ND_IBLOCK_ID, ['CML2_ARTICLE' => $id]);
	\Bitrix\Catalog\Model\Product::add([
		'ID' => $id, 'TYPE' => 1, 'MEASURE' => 5, 'QUANTITY' => $p['total'],
		'QUANTITY_TRACE' => 'D', 'CAN_BUY_ZERO' => 'D', 'VAT_INCLUDED' => 'N',
	]);
	\Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $id, 'CATALOG_GROUP_ID' => ND_PRICE_GROUP,
		'PRICE' => $p['base'], 'CURRENCY' => 'RUB']);
	foreach ($p['stores'] as $storeId => $amount) {
		\Bitrix\Catalog\StoreProductTable::add(['PRODUCT_ID' => $id, 'STORE_ID' => $storeId, 'AMOUNT' => $amount]);
	}
	echo "$id {$p['name']} — {$p['base']} ₽" . ($p['off'] > 0 ? " −{$p['off']}" : '') . ", остаток {$p['total']}\n";
}

foreach ($groups as $off => $xmls) {
	$ids = array_values(array_filter(array_map(function ($x) use ($idByXml) { return $idByXml[$x] ?? 0; }, $xmls)));
	if (!$ids) {
		continue;
	}
	$value = (float)$off;
	$did = CSaleDiscount::Add([
		'LID' => 's1',
		'NAME' => 'Распродажа: −' . $off . ' ₽ с шт. (' . count($ids) . ' тов.)',
		'XML_ID' => 'nd_sale_' . str_replace('.', '_', $off),
		'ACTIVE' => 'Y',
		'CURRENCY' => 'RUB',
		'PRIORITY' => 1,
		'SORT' => 100,
		'LAST_DISCOUNT' => 'N',
		'LAST_LEVEL_DISCOUNT' => 'N',
		'USE_COUPONS' => 'N',
		'USER_GROUPS' => ND_USER_GROUPS,
		'CONDITIONS' => ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND', 'True' => 'True'], 'CHILDREN' => []],
		'ACTIONS' => ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND'], 'CHILDREN' => [[
			'CLASS_ID' => 'ActSaleBsktGrp',
			'DATA' => ['Type' => 'Discount', 'Value' => $value, 'Unit' => 'CurEach', 'Max' => 0, 'All' => 'OR', 'True' => 'True'],
			'CHILDREN' => [['CLASS_ID' => 'CondIBElement', 'DATA' => ['logic' => 'Equal', 'value' => $ids]]],
		]]],
	]);
	if (!$did) {
		global $APPLICATION;
		$ex = $APPLICATION->GetException();
		fwrite(STDERR, "Правило −$off не создано: " . ($ex ? $ex->GetString() : '?') . "\n");
		continue;
	}
	$created['discounts'][] = (int)$did;
	$save();
	echo "Правило $did: −$off ₽ с шт. → " . implode(',', $ids) . "\n";
}
CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
echo 'Заведено товаров: ' . count($created['elements']) . ', правил: ' . count($created['discounts']) . "\n";
echo "Откат: php " . __FILE__ . ' ' . $root . " rollback=$outDir/created.json\n";
