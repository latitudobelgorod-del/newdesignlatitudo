<?php
/**
 * Товары 4SIS на latitudo.ru: код 1С и «Артикул производителя» из фида поставщика.
 *
 *   php vendor_code_4sis.php                         — сухой прогон: только отчёт
 *   php vendor_code_4sis.php apply                   — записать (с резервной копией backup.json)
 *   php vendor_code_4sis.php rollback=<backup.json>  — вернуть код 1С и характеристики как было
 *
 * 1. Код 1С. Товар у нас без кода из фида (XML_ID = свой ID или старый код, которого в фиде нет),
 *    а в фиде есть товар с точно таким же названием → ставим XML_ID из фида. Тогда sync_4sis.php
 *    начнёт обновлять ему цену. Не трогаем, если одинаковое название у нескольких наших товаров
 *    или у нескольких товаров фида.
 * 2. Артикул производителя. <vendorCode> фида пишем строкой «Артикул производителя» в поле
 *    «Характеристики» (CML2_ATTRIBUTES, значение + описание) — первой строкой; из него карточка
 *    печатает таблицу характеристик. Остальные строки не меняются.
 *
 * Параметры: --root=<путь к сайту> (~/www/latitudo.ru), --feed=<url или файл>,
 *   --pairs=<файл ID<TAB>код 1С> — пары, проверенные глазами (у 4SIS переименован товар: добавлен
 *   размер, сменился номер ткани): ставим этот код 1С, даже если названия не совпадают.
 * Отчёт и резервная копия — в ~/4sis-sync/<дата-время>_vendor_<режим>/ — вне www.
 */

$args = array_slice($argv, 1);
$opt = ['root' => getenv('HOME') . '/www/latitudo.ru', 'feed' => 'https://4sis.ru/images/111/XML_OCT_EACH.xml', 'pairs' => ''];
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
const ND_BRAND_ID = 28619; // 4SiS в ИБ 12
const ND_ATTR = 'CML2_ATTRIBUTES';
const ND_ATTR_NAME = 'Артикул производителя';

$_SERVER['DOCUMENT_ROOT'] = realpath($opt['root']);
if (!$_SERVER['DOCUMENT_ROOT'] || !is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php')) {
	fwrite(STDERR, "Не найден сайт в {$opt['root']}\n");
	exit(1);
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
@set_time_limit(0);
\Bitrix\Main\Loader::includeModule('iblock');

$outDir = getenv('HOME') . '/4sis-sync/' . date('Y-m-d_His') . '_vendor_' . $mode;
mkdir($outDir, 0700, true);

if ($mode === 'rollback') {
	$backup = json_decode((string)file_get_contents($rollbackFile), true);
	if (!is_array($backup)) {
		fwrite(STDERR, "Не читается $rollbackFile\n");
		exit(1);
	}
	$el = new CIBlockElement();
	foreach ($backup as $id => $b) {
		if (isset($b['xml_id'])) {
			$el->Update((int)$id, ['XML_ID' => $b['xml_id']]);
		}
		if (isset($b['attrs'])) {
			ndSetAttrs((int)$id, $b['attrs']);
		}
	}
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	echo 'Возвращено товаров: ' . count($backup) . "\n";
	exit(0);
}

// --- фид
$xml = preg_match('#^https?://#', $opt['feed'])
	? (string)@file_get_contents($opt['feed'], false, stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'latitudo-4sis-sync']]))
	: file_get_contents($opt['feed']);
$doc = $xml ? @simplexml_load_string($xml) : false;
if (!$doc) {
	fwrite(STDERR, "Фид не загрузился или не разбирается: {$opt['feed']}\n");
	exit(1);
}
$feed = [];
$feedByName = [];
foreach ($doc->shop->offers->offer as $o) {
	$code = (string)$o['id'];
	$feed[$code] = ['name' => trim((string)$o->name), 'vendor' => trim((string)$o->vendorCode)];
	$feedByName[ndNorm((string)$o->name)][] = $code;
}
if (count($feed) < 500) {
	fwrite(STDERR, 'В фиде подозрительно мало товаров: ' . count($feed) . "\n");
	exit(1);
}

$pairs = [];
if ($opt['pairs'] !== '') {
	foreach (file($opt['pairs'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
		$c = preg_split('/\s+/', trim($line));
		if (count($c) >= 2 && (int)$c[0] > 0 && isset($feed[$c[1]])) {
			$pairs[(int)$c[0]] = $c[1];
		} else {
			fwrite(STDERR, "Пара не разобрана или кода нет в фиде: $line\n");
			exit(1);
		}
	}
}

// --- наши товары 4SIS
$ours = [];
$oursByName = [];
$usedXml = [];
$rs = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => ND_IBLOCK_ID], false, false, ['ID', 'NAME', 'XML_ID', 'ACTIVE', 'PROPERTY_BRAND']);
while ($r = $rs->Fetch()) {
	$usedXml[$r['XML_ID']] = (int)$r['ID'];
	if ((int)$r['PROPERTY_BRAND_VALUE'] !== ND_BRAND_ID) {
		continue;
	}
	$ours[(int)$r['ID']] = $r;
	$oursByName[ndNorm($r['NAME'])][] = (int)$r['ID'];
}

$rows = [];
$plan = [];
$stat = [];
foreach ($ours as $id => $r) {
	$code = $r['XML_ID'];
	$newXml = null;
	$note = '';
	if (isset($pairs[$id]) && !isset($feed[$code])) {
		if (isset($usedXml[$pairs[$id]])) {
			$note = 'код 1С из пары уже у другого товара';
		} else {
			$newXml = $pairs[$id];
			$code = $newXml;
		}
	} elseif (!isset($feed[$code])) {
		$k = ndNorm($r['NAME']);
		$cand = $feedByName[$k] ?? [];
		if (count($cand) === 1 && count($oursByName[$k]) === 1 && !isset($usedXml[$cand[0]])) {
			$newXml = $cand[0];
			$code = $newXml;
		} elseif ($cand) {
			$note = 'код 1С не ставим: одинаковое название у нескольких товаров';
		}
	}
	$attrs = ndGetAttrs($id);
	$newAttrs = null;
	if (isset($feed[$code]) && $feed[$code]['vendor'] !== '') {
		$vendor = $feed[$code]['vendor'];
		$have = null;
		foreach ($attrs as $i => $a) {
			if (trim($a['DESCRIPTION']) === ND_ATTR_NAME) {
				$have = $i;
			}
		}
		if ($have === null) {
			$newAttrs = array_merge([['VALUE' => $vendor, 'DESCRIPTION' => ND_ATTR_NAME]], $attrs);
		} elseif ($attrs[$have]['VALUE'] !== $vendor) {
			$newAttrs = $attrs;
			$newAttrs[$have]['VALUE'] = $vendor;
		}
	}
	$action = [];
	if ($newXml !== null) {
		$action[] = 'код 1С';
	}
	if ($newAttrs !== null) {
		$action[] = 'артикул';
	}
	$action = $action ? 'записать: ' . implode(' + ', $action) : (isset($feed[$code]) ? 'уже всё есть' : ($note ?: 'нет в фиде'));
	$stat[$action] = ($stat[$action] ?? 0) + 1;
	if ($newXml !== null || $newAttrs !== null) {
		$plan[$id] = ['xml_id' => $newXml, 'attrs' => $newAttrs, 'old_xml_id' => $r['XML_ID'], 'old_attrs' => $attrs];
	}
	$rows[] = [$action, $id, $r['ACTIVE'], $r['XML_ID'], (string)$newXml, $r['NAME'],
		isset($feed[$code]) ? $feed[$code]['vendor'] : '', isset($feed[$code]) ? $feed[$code]['name'] : ''];
}

$fh = fopen($outDir . '/report.csv', 'w');
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, ['Действие', 'ID', 'Активен', 'Код 1С сейчас', 'Новый код 1С', 'Название у нас', 'Артикул производителя', 'Название в фиде'], ';');
foreach ($rows as $row) {
	fputcsv($fh, $row, ';');
}
fclose($fh);

ksort($stat);
echo 'Фид: ' . count($feed) . ' товаров; у нас 4SIS: ' . count($ours) . "\n";
foreach ($stat as $k => $v) {
	echo str_pad((string)$v, 6, ' ', STR_PAD_LEFT) . "  $k\n";
}
echo "Отчёт: $outDir/report.csv\n";
if ($mode !== 'apply') {
	echo "Сухой прогон — в базе ничего не изменено. Записать: добавьте apply\n";
	exit(0);
}

$backup = [];
foreach ($plan as $id => $p) {
	$backup[$id] = [];
	if ($p['xml_id'] !== null) {
		$backup[$id]['xml_id'] = $p['old_xml_id'];
	}
	if ($p['attrs'] !== null) {
		$backup[$id]['attrs'] = $p['old_attrs'];
	}
}
file_put_contents($outDir . '/backup.json', json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Резервная копия: $outDir/backup.json\n";

$el = new CIBlockElement();
$ok = 0;
$err = 0;
foreach ($plan as $id => $p) {
	$res = true;
	if ($p['xml_id'] !== null && !$el->Update($id, ['XML_ID' => $p['xml_id']])) {
		fwrite(STDERR, "Код 1С $id: {$el->LAST_ERROR}\n");
		$res = false;
	}
	if ($res && $p['attrs'] !== null) {
		ndSetAttrs($id, $p['attrs']);
	}
	$res ? $ok++ : $err++;
}
CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
echo "Записано: $ok, ошибок: $err\n";
echo "Откат: php " . __FILE__ . " rollback=$outDir/backup.json\n";
exit($err ? 1 : 0);


function ndNorm(string $s): string
{
	$s = mb_strtolower(str_replace(['ё', 'Ё'], 'е', $s));
	$s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
	return trim(preg_replace('/\s+/', ' ', $s));
}

function ndGetAttrs(int $id): array
{
	$out = [];
	$rs = CIBlockElement::GetProperty(ND_IBLOCK_ID, $id, ['sort' => 'asc', 'value_id' => 'asc'], ['CODE' => ND_ATTR]);
	while ($p = $rs->Fetch()) {
		if ((string)$p['VALUE'] !== '') {
			$out[] = ['VALUE' => (string)$p['VALUE'], 'DESCRIPTION' => (string)$p['DESCRIPTION']];
		}
	}
	return $out;
}

function ndSetAttrs(int $id, array $attrs): void
{
	// пустой список нельзя передать как [] — свойство не очистится
	CIBlockElement::SetPropertyValuesEx($id, ND_IBLOCK_ID, [ND_ATTR => $attrs ?: false]);
}
