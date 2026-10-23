<?php
/**
 * Новые товары 4SIS на latitudo.ru из фида поставщика — скрытыми (ACTIVE=N), по образцу уже заведённых.
 *
 *   php new_goods_4sis.php                         — сухой прогон: план в plan.csv, в базе ничего не меняется
 *   php new_goods_4sis.php apply [--limit=N] [--only=УТ-…,УТ-…] — завести (для пробы — N первых или только эти коды)
 *   php new_goods_4sis.php rollback=<created.json> — удалить заведённые этим скриптом товары
 *
 * Берём из фида товары, чьего кода 1С (<offer id>) нет ни у одного товара ИБ 19.
 * Как у товаров 4SIS, заведённых в феврале 2026:
 *   - название из фида, XML_ID = код 1С, символьный код — транслит названия (настройки инфоблока);
 *   - описание: полное со страницы 4sis.ru (desc.json от desc4sis.py), иначе из фида — в фиде оно
 *     обрезано до 500 знаков, режем по последнему законченному предложению;
 *   - фото: все картинки фида в MORE_PHOTO, первая — главное фото и анонс;
 *   - «Характеристики» (CML2_ATTRIBUTES) из <param> фида, первой строкой «Артикул производителя»;
 *   - свойства обмена 1С (SR, CARTONS, VPUTI, …), бренд, наличие «на складе в Белгороде», полный фид;
 *   - цена BASE — без акции (oldprice, если больше price), каталог: штука, вес и габариты из фида;
 *   - раздел — по виду товара в названии, иначе по категории фида;
 *   - свойства фильтра (цвет, материал, плетение, подлокотники, форма) — как у такого же товара
 *     другого цвета (та же модель и вид), иначе по правилам заполнения садовой мебели.
 * Связи (С этим товаром покупают, вариации цветов) не ставим.
 * --site=<site_goods.json> — заводить не из фида, а с карточек 4sis.ru (товары, которых нет в фиде, но 4SIS их
 *   продаёт): название, «Розничный прайс-лист», описание, фото, характеристики как есть, артикул; XML_ID = SITE-<адрес>.
 * --names=<файл код 1С<TAB>название> — своё название вместо фидового: у 4SIS бывает, что у товара другого
 *   цвета/размера в фиде скопировано название соседнего (артикул другой), правильное — с 4sis.ru.
 */

$args = array_slice($argv, 1);
$opt = ['root' => getenv('HOME') . '/www/latitudo.ru', 'feed' => getenv('HOME') . '/4sis-sync/feed.xml',
	'desc' => getenv('HOME') . '/4sis-sync/desc.json', 'limit' => '0', 'only' => '', 'names' => '', 'site' => ''];
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

const ND_IBLOCK_ID = 19;
const ND_BRAND_ID = 28619;
const ND_PRICE_GROUP = 1;
// раздел по категории фида (как лежит большинство уже заведённых товаров этой категории)
const ND_CAT_SECTION = [
	'Журнальные столики' => 480, 'Аксессуары' => 481, 'Модули' => 482, 'Столы обеденные' => 483, 'Диваны' => 484,
	'Кресла' => 485, 'Лаунж зоны' => 486, 'Подвесные кресла' => 487, 'Стулья' => 488, 'Обеденные группы' => 489,
	'Шезлонги' => 490, 'Зонты, утяжелители' => 491, '19 текстиль под заказ' => 481, 'ковры' => 481,
];

$_SERVER['DOCUMENT_ROOT'] = realpath($opt['root']);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
@set_time_limit(0);
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');

$outDir = getenv('HOME') . '/4sis-sync/' . date('Y-m-d_His') . '_new_' . $mode;
mkdir($outDir, 0700, true);

if ($mode === 'rollback') {
	$ids = json_decode((string)file_get_contents($rollbackFile), true);
	if (!is_array($ids)) {
		fwrite(STDERR, "Не читается $rollbackFile\n");
		exit(1);
	}
	$n = 0;
	foreach ($ids as $id) {
		$e = CIBlockElement::GetList([], ['ID' => (int)$id, 'IBLOCK_ID' => ND_IBLOCK_ID], false, false, ['ID', 'DATE_CREATE'])->Fetch();
		if ($e && CIBlockElement::Delete((int)$id)) {
			$n++;
		}
	}
	echo "Удалено товаров: $n\n";
	exit(0);
}

// --- фид
$doc = simplexml_load_file($opt['feed']);
if (!$doc || count($doc->shop->offers->offer) < 500) {
	fwrite(STDERR, "Фид не разобрался или в нём мало товаров\n");
	exit(1);
}
$cats = [];
foreach ($doc->shop->categories->category as $c) {
	$cats[(string)$c['id']] = (string)$c;
}
$names = [];
if ($opt['names'] !== '') {
	foreach (file($opt['names'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
		$c = explode("	", $line, 2);
		if (count($c) === 2) {
			$names[trim($c[0])] = trim($c[1]);
		}
	}
}
$desc = is_file($opt['desc']) ? json_decode(file_get_contents($opt['desc']), true) : [];

// --- что уже есть
$usedXml = [];
$usedCode = [];
$usedName = [];
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID], false, false, ['ID', 'XML_ID', 'CODE', 'NAME']);
while ($r = $rs->Fetch()) {
	$usedXml[$r['XML_ID']] = (int)$r['ID'];
	$usedCode[$r['CODE']] = true;
	$usedName[ndNorm($r['NAME'])] = (int)$r['ID'];
}
// уже заведённые 4SIS: образцы свойств фильтра по «модели и виду» и соответствие «Цвет» → BASE_COLOUR
$filterCodes = ['BASE_COLOUR', 'ND_MATERIAL', 'ND_PLETENIE', 'PODLOKOTNIKI', 'FORMA_STOLA', 'ND_FORMA'];
$siblings = [];
$colourVotes = [];
$rs = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => ND_IBLOCK_ID, 'PROPERTY_BRAND' => ND_BRAND_ID], false, false, ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'ACTIVE']);
while ($r = $rs->Fetch()) {
	$p = ['_ID' => (int)$r['ID'], '_SECTION' => (int)$r['IBLOCK_SECTION_ID']];
	foreach ($filterCodes as $code) {
		$vals = [];
		$pr = CIBlockElement::GetProperty(ND_IBLOCK_ID, $r['ID'], [], ['CODE' => $code]);
		while ($x = $pr->Fetch()) {
			if ($x['VALUE']) {
				$vals[] = (int)$x['VALUE'];
			}
		}
		$p[$code] = $vals;
	}
	$pr = CIBlockElement::GetProperty(ND_IBLOCK_ID, $r['ID'], [], ['CODE' => 'CML2_ATTRIBUTES']);
	while ($x = $pr->Fetch()) {
		if (trim($x['DESCRIPTION']) === 'Цвет' && $p['BASE_COLOUR']) {
			$c = mb_strtolower(trim($x['VALUE']));
			$set = $p['BASE_COLOUR'];
			sort($set);
			$colourVotes[$c][implode(',', $set)] = ($colourVotes[$c][implode(',', $set)] ?? 0) + 1;
		}
	}
	$k = ndModelKey($r['NAME']);
	if (!isset($siblings[$k]) || $r['ACTIVE'] === 'Y') {
		$siblings[$k] = $p;
	}
}
$colourMap = [];
foreach ($colourVotes as $c => $v) {
	arsort($v);
	$colourMap[$c] = array_map('intval', explode(',', key($v)));
}

// --- план
$plan = [];
$rows = [];
// Источник: фид (по умолчанию) или карточки сайта 4sis.ru (--site=<site_goods.json от site_goods_4sis.py>) —
// товары, которых нет в фиде, но 4SIS их продаёт. Дальше всё одинаково.
$sources = [];
if ($opt['site'] !== '') {
	foreach (json_decode((string)file_get_contents($opt['site']), true) ?: [] as $g) {
		if (mb_stripos($g['stock'] ?? '', 'нет в наличии') !== false || empty($g['price']) || empty($g['name'])) {
			continue;
		}
		$props = [];
		foreach ($g['props'] as $pr) {
			$props[$pr[0]] = $pr[1];
		}
		// размеры и вес для каталога: «570×620×775мм», «5,5 кг»
		$dims = preg_split('/[×x*х]/u', preg_replace('/[^\d×x*х.,]/u', '', $props['Габариты товара (ДШВ)'] ?? ''));
		$kg = (float)str_replace(',', '.', preg_replace('/[^\d,.]/u', '', $props['Вес (Нетто)'] ?? ''));
		$crumbs = mb_strtolower(implode(' ', $g['crumbs'] ?? []));
		$sources[] = [
			'xml' => 'SITE-' . substr(basename(rtrim((string)parse_url($g['url'], PHP_URL_PATH), '/')), 0, 200),
			'name' => $g['name'],
			'cat' => preg_match('/журнальн|кофейн/u', $crumbs) ? 'Журнальные столики' : '',
			'par' => ['Длина' => $dims[0] ?? '', 'Ширина' => $dims[1] ?? '', 'Высота' => $dims[2] ?? '', 'Вес' => $kg ?: '',
				'Цвет' => $props['Цвет плетения'] ?? ($props['Цвет'] ?? ($props['Цвет столешницы'] ?? '')),
				'МатериалПлетения' => $props['Материал плетения'] ?? ''],
			'attrs' => $props,
			'text' => (string)$g['desc'],
			'textFrom' => '4sis.ru',
			'pics' => $g['photos'],
			'regular' => (float)$g['price'],
			'sale' => (float)($g['sale'] ?: $g['price']),
			'vendor' => (string)$g['article'],
		];
	}
} else {
	foreach ($doc->shop->offers->offer as $o) {
		$par = [];
		foreach ($o->param as $p) {
			$par[(string)$p['name']] = trim((string)$p);
		}
		$pics = [];
		foreach ($o->picture as $p) {
			$pics[] = trim((string)$p);
		}
		$sources[] = [
			'xml' => (string)$o['id'],
			'name' => trim((string)$o->name),
			'cat' => $cats[(string)$o->categoryId] ?? '',
			'par' => $par,
			'attrs' => null,
			'text' => trim(str_replace("\r", '', (string)$o->description)),
			'textFrom' => 'фид',
			'pics' => $pics,
			'regular' => max((float)$o->price, (float)$o->oldprice),
			'sale' => (float)$o->price,
			'vendor' => trim((string)$o->vendorCode),
		];
	}
}

// Артикулы производителя, которые у нас уже есть: 4SIS переименовывает товары («интерьерный стол» → «стол»,
// «052» → «15052»), и по одному названию переименованный товар заводился вторым экземпляром (23.10.2026 — 21 дубль).
$usedVendor = [];
$rs = $DB->Query("SELECT v.VALUE FROM b_iblock_element_property v INNER JOIN b_iblock_property p ON p.ID = v.IBLOCK_PROPERTY_ID "
	. "AND p.IBLOCK_ID = " . ND_IBLOCK_ID . " AND p.CODE = 'CML2_ATTRIBUTES' WHERE v.DESCRIPTION = 'Артикул производителя'");
while ($r = $rs->Fetch()) {
	$usedVendor[ndVendorKey($r['VALUE'])] = true;
}
unset($usedVendor['']);

foreach ($sources as $src) {
	$xml = $src['xml'];
	$name = $names[$xml] ?? $src['name'];
	// товар с таким названием уже есть под другим кодом (в фиде у 4SIS бывают два кода на один товар)
	if (isset($usedXml[$xml]) || $name === '' || isset($usedName[ndNorm($name)])) {
		continue;
	}
	// тот же артикул производителя уже есть — переименованный у 4SIS товар (своё название из --names не проверяем)
	if (!isset($names[$xml]) && isset($usedVendor[ndVendorKey($src['vendor'])])) {
		continue;
	}
	$par = $src['par'];
	$cat = $src['cat'];
	$section = ndSection($name, $cat);
	$sib = $siblings[ndModelKey($name)] ?? null;
	if ($sib && $sib['_SECTION'] !== $section) {
		$sib = null;
	}

	// свойства фильтра
	$f = array_fill_keys($filterCodes, []);
	$lname = mb_strtolower($name);
	$weave = mb_strtolower($par['МатериалПлетения'] ?? '');
	$isRotang = mb_strpos($lname, 'ротанг') !== false || mb_strpos($weave, 'ротанг') !== false;
	$isWoven = $isRotang || preg_match('/роуп|плетен|плетён/u', $lname) || $weave !== '';
	$colour = mb_strtolower($par['Цвет'] ?? '');
	$f['BASE_COLOUR'] = $colourMap[$colour] ?? ($colour !== '' ? ndColourWords($colour) : array_slice(ndColourWords(ndColourPart($lname)), 0, 1));
	if ($sib) {
		foreach (['ND_MATERIAL', 'ND_PLETENIE', 'PODLOKOTNIKI', 'FORMA_STOLA', 'ND_FORMA'] as $code) {
			$f[$code] = $sib[$code];
		}
	} else {
		if (in_array($section, [483, 484, 485], true)) {
			$f['ND_MATERIAL'] = [$isRotang ? 1007 : 1008];
		}
		if (in_array($section, [483, 484, 485, 488], true)) {
			$f['ND_PLETENIE'] = [$isWoven ? 1015 : 1016];
		}
		if ($section === 485) {
			$f['PODLOKOTNIKI'] = [1001];
		} elseif ($section === 488) {
			$f['PODLOKOTNIKI'] = [mb_strpos($lname, 'барный') !== false ? 1002 : 1001];
		}
		if ($section === 483) {
			$f['FORMA_STOLA'] = [ndTableShape($lname)];
		}
		if ($section === 484 && mb_strpos($lname, 'углов') !== false) {
			$f['ND_FORMA'] = [1012];
		}
	}
	// у коричневой садовой мебели всегда и «Венге» (так проставлено 22.09.2026)
	if (in_array(138, $f['BASE_COLOUR'], true) && !in_array(1011, $f['BASE_COLOUR'], true)) {
		$f['BASE_COLOUR'][] = 1011;
	}

	// описание
	if (isset($desc[$xml]['text']) && $desc[$xml]['text'] !== '') {
		$text = $desc[$xml]['text'];
		$descFrom = '4sis.ru';
	} else {
		$text = $src['text'];
		$descFrom = $src['textFrom'];
		if ($descFrom === 'фид' && mb_strlen($text) >= 490 && preg_match('/^(.*[.!?»])[^.!?»]*$/us', $text, $m)) {
			$text = $m[1];
			$descFrom = 'фид, обрезан по предложению';
		}
	}
	$text = preg_replace(["/\n\n(?=- )/u", "/\n{3,}/u"], ["\n", "\n\n"], trim($text));

	$pics = $src['pics'];
	$regular = $src['regular'];

	$code = ndCode($name, $usedCode);
	$usedCode[$code] = true;
	$plan[] = compact('xml', 'name', 'code', 'section', 'par', 'f', 'text', 'pics', 'regular') + ['vendor' => $src['vendor'], 'attrs' => $src['attrs']];
	$rows[] = [$xml, $name, $section, $cat, $regular, $src['sale'], count($pics), $descFrom, mb_strlen($text),
		ndEnumNames($f['BASE_COLOUR']), ndEnumNames($f['ND_MATERIAL']), ndEnumNames($f['ND_PLETENIE']),
		ndEnumNames($f['PODLOKOTNIKI']), ndEnumNames($f['FORMA_STOLA']), ndEnumNames($f['ND_FORMA']),
		$sib ? $sib['_ID'] : '', $par['Цвет'] ?? '', $code];
}

$fh = fopen($outDir . '/plan.csv', 'w');
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, ['Код 1С', 'Название', 'Раздел', 'Категория фида', 'Цена', 'Цена 4sis с акцией', 'Фото', 'Описание откуда', 'Описание, знаков',
	'Цвет (фильтр)', 'Материал', 'Плетение', 'Подлокотники', 'Форма стола', 'Форма дивана', 'Свойства как у ID', 'Цвет в фиде', 'Символьный код'], ';');
foreach ($rows as $r) {
	fputcsv($fh, $r, ';');
}
fclose($fh);
$bySection = array_count_values(array_column($plan, 'section'));
echo 'Новых товаров: ' . count($plan) . "\nПо разделам: " . json_encode($bySection) . "\n";
echo 'Без цвета для фильтра: ' . count(array_filter($plan, function ($p) { return !$p['f']['BASE_COLOUR']; })) . "\n";
echo "План: $outDir/plan.csv\n";
if ($mode !== 'apply') {
	echo "Сухой прогон — в базе ничего не изменено. Завести: добавьте apply\n";
	exit(0);
}

// --- запись
if ($opt['only'] !== '') {
	$only = array_flip(explode(',', $opt['only']));
	$plan = array_values(array_filter($plan, function ($p) use ($only) { return isset($only[$p['xml']]); }));
}
if ((int)$opt['limit'] > 0) {
	$plan = array_slice($plan, 0, (int)$opt['limit']);
}
$created = [];
$el = new CIBlockElement();
$tmp = $outDir . '/img';
mkdir($tmp, 0700);
foreach ($plan as $p) {
	$files = [];
	foreach ($p['pics'] as $i => $url) {
		$path = $tmp . '/' . md5($url) . '.jpg';
		$bin = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 60]]));
		if ($bin !== false && strlen($bin) > 1000) {
			file_put_contents($path, $bin);
			$files[] = $path;
		}
	}
	$attrs = [['VALUE' => $p['vendor'], 'DESCRIPTION' => 'Артикул производителя']];
	foreach ($p['attrs'] ?? ndAttributes($p['par']) as $k => $v) {
		$attrs[] = ['VALUE' => $v, 'DESCRIPTION' => $k];
	}
	$par = $p['par'];
	$props = [
		'BRAND' => ND_BRAND_ID,
		'PR_DOCUM' => ND_BRAND_ID,
		'CML2_ATTRIBUTES' => $attrs,
		'SR' => $par['СР'] ?? '',
		'CARTONS' => $par['Cartons'] ?? '',
		'VPUTI' => $par['ВПути'] ?? '',
		'KOLICHESTVOPOSTUPLENIYA' => $par['КоличествоПоступления'] ?? '',
		'DATAPOSTUPLENIYA' => $par['ДатаПоступления'] ?? '',
		'GABARITY_UPAKOVKI_DSHV' => $par['ГУ'] ?? '',
		'CARTONS_DIMENSIONSSHVG' => $par['cartons_dimensionsШВГ'] ?? '',
		'IN_STOCK' => 79,
		'LIST_PRISE' => [999],
		'ND_KOMPLEKT' => 1010,
		'ND_BRAND_WEIGHT' => 3,
		'MORE_PHOTO' => array_map(function ($f) { return ['VALUE' => CFile::MakeFileArray($f)]; }, $files),
	];
	foreach ($p['f'] as $code => $vals) {
		if ($vals) {
			$props[$code] = $vals;
		}
	}
	$fields = [
		'IBLOCK_ID' => ND_IBLOCK_ID,
		'ACTIVE' => 'N',
		'NAME' => $p['name'],
		'CODE' => $p['code'],
		'XML_ID' => $p['xml'],
		'IBLOCK_SECTION_ID' => $p['section'],
		'DETAIL_TEXT' => $p['text'],
		'DETAIL_TEXT_TYPE' => 'text',
		'PROPERTY_VALUES' => $props,
	];
	if ($files) {
		$fields['DETAIL_PICTURE'] = CFile::MakeFileArray($files[0]);
		$fields['PREVIEW_PICTURE'] = CFile::MakeFileArray($files[0]);
	}
	$id = $el->Add($fields, false, true, true);
	if (!$id) {
		fwrite(STDERR, "{$p['xml']} {$p['name']}: {$el->LAST_ERROR}\n");
		continue;
	}
	$created[] = (int)$id;
	file_put_contents($outDir . '/created.json', json_encode($created));
	CIBlockElement::SetPropertyValuesEx($id, ND_IBLOCK_ID, ['CML2_ARTICLE' => $id]);
	ndShortLink((int)$id);
	$num = function ($k) use ($par) { return isset($par[$k]) && is_numeric(str_replace(',', '.', $par[$k])) ? (float)str_replace(',', '.', $par[$k]) : 0; };
	\Bitrix\Catalog\Model\Product::add([
		'ID' => $id, 'QUANTITY' => 1000, 'QUANTITY_TRACE' => 'N', 'CAN_BUY_ZERO' => 'Y', 'MEASURE' => 5, 'TYPE' => 1,
		'WEIGHT' => $num('Вес') * 1000, 'LENGTH' => $num('Длина'), 'WIDTH' => $num('Ширина'), 'HEIGHT' => $num('Высота'),
		'VAT_INCLUDED' => 'N',
	]);
	\Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $id, 'CATALOG_GROUP_ID' => ND_PRICE_GROUP, 'PRICE' => $p['regular'], 'CURRENCY' => 'RUB']);
	echo "$id {$p['name']} — фото " . count($files) . "\n";
	foreach ($files as $f) {
		@unlink($f);
	}
}
CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
echo 'Заведено: ' . count($created) . "\nОткат (удалить заведённые): php " . __FILE__ . " rollback=$outDir/created.json\n";


// Короткая ссылка, как local/tools/nd_short_uri_fill.php (тот берёт только активные товары)
function ndShortLink(int $id): void
{
	$config = $_SERVER['DOCUMENT_ROOT'] . '/local/short-link/config.php';
	if (!is_file($config)) {
		return;
	}
	require_once $config;
	$row = CIBlockElement::GetList([], ['ID' => $id, 'IBLOCK_ID' => ND_IBLOCK_ID], false, false, ['ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL'])->GetNext();
	$uri = $row ? $row['~DETAIL_PAGE_URL'] : '';
	$code = $uri !== '' ? ltrim((string)CBXShortUri::GetShortUri($uri), '/') : '';
	if ($code !== '') {
		CIBlockElement::SetPropertyValuesEx($id, ND_IBLOCK_ID, [ND_SHORT_LINK_PROP => ND_SHORT_LINK_HOST . '/' . $code]);
	}
}

// Артикул для сравнения: без регистра, лишних пробелов и пометки « OLD»; «б/а» (без артикула) — пусто
function ndVendorKey(string $v): string
{
	$v = trim(preg_replace('/\s+/u', ' ', preg_replace('/\s+OLD\s*$/u', '', mb_strtolower($v))));
	return $v === 'б/а' ? '' : $v;
}

function ndNorm(string $s): string
{
	$s = mb_strtolower(str_replace(['ё', 'Ё'], 'е', $s));
	$s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
	return trim(preg_replace('/\s+/', ' ', $s));
}

// «Модель и вид»: до первой запятой, без «цвет …» — у вариантов одного товара разного цвета совпадает
function ndModelKey(string $name): string
{
	$s = mb_strtolower(str_replace(['ё', '"', '«', '»'], ['е', '', '', ''], $name));
	$s = explode(',', $s)[0];
	$s = preg_replace('/\s+цвет.*$/u', '', $s);
	return trim(preg_replace('/\s+/', ' ', $s));
}

// Цвет из названия: что после «цвет столешницы» / «цвет» / последнего «роуп», иначе всё название (берём первый цвет)
function ndColourPart(string $n): string
{
	foreach (['/цвет столешницы(.*)$/u', '/цвет(.*)$/u', '/.*роуп\s(.*)$/u'] as $re) {
		if (preg_match($re, $n, $m)) {
			return $m[1];
		}
	}
	return $n;
}

// Слова цвета → BASE_COLOUR (у столов — цвет столешницы, у плетёной мебели — цвет роупа/ротанга)
function ndColourWords(string $t): array
{
	$words = [
		'/черн|антрацит/u' => 130, '/бел/u' => 132, '/сер|графит|мрамор|гранит/u' => 131, '/беж|соломен|песоч|натурал/u' => 133,
		'/коричн|кричн|дуб|тик|орех|шоколад/u' => 138, '/бирюз|син|голуб/u' => 136, '/салат|зел|олив/u' => 137,
		'/оранж/u' => 139, '/желт/u' => 135, '/красн/u' => 134,
	];
	$out = [];
	foreach ($words as $re => $id) {
		if (preg_match($re, $t, $m, PREG_OFFSET_CAPTURE)) {
			$out[$m[0][1]] = $id;
		}
	}
	ksort($out);
	return array_values(array_slice($out, 0, 2, true));
}

function ndSection(string $name, string $cat): int
{
	$n = mb_strtolower(str_replace(['"', '«', '»'], '', $name));
	$rules = [
		'/^\s*(защитный\s+)?чехол|^\s*подушк|декоративн\w* подушк|оттоманк/u' => 481,
		'/зонт|утяжелит/u' => 491,
		'/подвесн/u' => 487,
		'/кушетк|лежак|кроват/u' => 506,
		'/\bпуф|кашпо|ковер|ковёр/u' => 481,
		'/обеденн\w* групп/u' => 489,
		'/лаунж/u' => 486,
		'/комплект/u' => 503,
		'/диван модульн/u' => 484,
		'/модул/u' => 482,
		'/шезлонг/u' => 490,
		'/журнальн|кофейн|интерьерн/u' => 480,
		'/стол/u' => 0, // стол: обеденный или журнальный — по категории фида
		'/диван/u' => 484,
		'/кресл/u' => 485,
		'/стул/u' => 488,
	];
	foreach ($rules as $re => $s) {
		if (preg_match($re, $n)) {
			return $s ?: ($cat === 'Журнальные столики' ? 480 : 483);
		}
	}
	return ND_CAT_SECTION[$cat] ?? 503;
}

function ndTableShape(string $n): int
{
	if (preg_match('/кругл|\bd\s?\d|ø/u', $n)) {
		return 1003;
	}
	if (mb_strpos($n, 'овальн') !== false) {
		return 1004;
	}
	if (preg_match('/(\d+)\s*[x×х]\s*(\d+)/u', $n, $m) && $m[1] === $m[2]) {
		return 1006;
	}
	if (mb_strpos($n, 'квадрат') !== false) {
		return 1006;
	}
	return 1005;
}

// Параметры фида → строки «Характеристик», как у товаров, заведённых в феврале 2026
function ndAttributes(array $p): array
{
	$out = [];
	$add = function ($label, $v) use (&$out) {
		$v = trim((string)$v);
		if ($v !== '' && $v !== '0') {
			$out[$label] = $v;
		}
	};
	$kg = function ($v) {
		$v = str_replace(',', '.', trim((string)$v));
		return is_numeric($v) && (float)$v > 0 ? (string)round((float)$v * 1000) : '';
	};
	$add('Длина, мм', $p['Длина'] ?? '');
	$add('Ширина, мм', $p['Ширина'] ?? '');
	$add('Высота, мм', $p['Высота'] ?? '');
	$add('Вес', $kg($p['Вес'] ?? ''));
	$add('ВесБр', $kg($p['ВесБр'] ?? ''));
	$add('Объем', $p['Объем'] ?? '');
	$add('Материал каркаса', $p['МатериалКаркаса'] ?? '');
	$add('Материал плетения', $p['МатериалПлетения'] ?? '');
	$add('Цвет', $p['Цвет'] ?? '');
	$add('Цвет каркаса', $p['ЦветКаркаса'] ?? '');
	$add('Цвет подушки', $p['ЦветПодушки'] ?? '');
	$add('Материал подушки', $p['МатериалПодушки'] ?? '');
	$add('Габариты товара (ШВД)', $p['ШВД'] ?? '');
	$add('Гарантия', $p['Гарантия'] ?? '');
	$add('Глубина сидения', $p['ГлубинаСидения'] ?? '');
	$add('Высота сидения', $p['ВысотаСидения'] ?? '');
	$add('Коллекция', $p['Коллекция'] ?? '');
	return $out;
}

function ndCode(string $name, array $used): string
{
	$base = CUtil::translit($name, 'ru', ['max_len' => 100, 'change_case' => 'L', 'replace_space' => '-',
		'replace_other' => '-', 'delete_repeat_replace' => true]);
	$base = trim($base, '-');
	$code = $base;
	for ($i = 2; isset($used[$code]); $i++) {
		$code = $base . '-' . $i;
	}
	return $code;
}

function ndEnumNames(array $ids): string
{
	static $names = null;
	if ($names === null) {
		$names = [];
		$rs = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID]);
		while ($e = $rs->Fetch()) {
			$names[(int)$e['ID']] = $e['VALUE'];
		}
	}
	return implode(' / ', array_map(function ($id) use ($names) { return $names[$id] ?? $id; }, $ids));
}
