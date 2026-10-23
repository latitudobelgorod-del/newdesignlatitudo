<?php
/**
 * Цены 4SIS на latitudo.ru — по фиду поставщика.
 *
 *   php sync_4sis.php                         — сухой прогон: только отчёт, в базе ничего не меняется
 *   php sync_4sis.php apply                   — записать цены (и деактивировать товары из --deactivate)
 *   php sync_4sis.php rollback=<backup.json>  — вернуть цены и активность из резервной копии
 *
 * Параметры:
 *   --root=<путь к сайту>    по умолчанию ~/www/latitudo.ru
 *   --feed=<url или файл>    по умолчанию https://4sis.ru/images/111/XML_OCT_EACH.xml
 *   --deactivate=<файл>      ID товаров (по одному в строке), которые надо снять с сайта:
 *                            их нет ни в фиде, ни на 4sis.ru (список готовит сверка с сайтом 4sis)
 *   --site-prices=<файл>     ID<TAB>цена — для товаров, которых нет в фиде, но они есть на 4sis.ru:
 *                            цена без акции с их карточки (тот же build_deactivate.py)
 *
 * Что считается ценой: цена 4SIS без акции — <oldprice>, если он есть и больше <price>,
 * иначе <price>. Акционные цены на latitudo.ru не ставим.
 * Сопоставление — только по коду 1С: <offer id> фида = XML_ID товара (ИБ 19, бренд 4SiS).
 * По названию не сопоставляем: «похожие» названия у 4SIS — это другие модели и цвета.
 *
 * Отчёт и резервная копия пишутся в ~/4sis-sync/<дата-время>/ — вне www.
 */

$args = array_slice($argv, 1);
$opt = ['root' => getenv('HOME') . '/www/latitudo.ru', 'feed' => 'https://4sis.ru/images/111/XML_OCT_EACH.xml', 'deactivate' => '', 'site-prices' => ''];
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
const ND_BRAND_PROP = 'BRAND';
const ND_BRAND_ID = 28619; // 4SiS в ИБ 12 «Производители и марки»
const ND_PRICE_GROUP = 1;  // BASE

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
\Bitrix\Main\Loader::includeModule('catalog');

$outDir = getenv('HOME') . '/4sis-sync/' . date('Y-m-d_His') . ($mode === 'dry' ? '_dry' : '_' . $mode);
mkdir($outDir, 0700, true);

if ($mode === 'rollback') {
	$backup = json_decode((string)file_get_contents($rollbackFile), true);
	if (!is_array($backup)) {
		fwrite(STDERR, "Не читается $rollbackFile\n");
		exit(1);
	}
	$n = 0;
	foreach ($backup as $id => $b) {
		ndSetPrice((int)$id, $b['price']);
		if (!empty($b['deactivated'])) {
			ndSetActive((int)$id, $b['active']);
		}
		$n++;
	}
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	echo "Возвращено товаров: $n\n";
	exit(0);
}

// --- фид
$xml = preg_match('#^https?://#', $opt['feed']) ? ndFetch($opt['feed']) : file_get_contents($opt['feed']);
$feed = [];
$doc = $xml ? @simplexml_load_string($xml) : false;
if (!$doc) {
	fwrite(STDERR, "Фид не загрузился или не разбирается: {$opt['feed']}\n");
	exit(1);
}
foreach ($doc->shop->offers->offer as $o) {
	$price = (float)$o->price;
	$old = (float)$o->oldprice;
	$feed[(string)$o['id']] = [
		'name' => trim((string)$o->name),
		'price' => $price,
		'oldprice' => $old,
		'regular' => max($price, $old),
		'available' => (string)$o['available'],
	];
}
if (count($feed) < 500) {
	// Обрезанный фид не должен превратиться в массовую правку цен
	fwrite(STDERR, 'В фиде подозрительно мало товаров: ' . count($feed) . "\n");
	exit(1);
}

$deactivate = [];
if ($opt['deactivate'] !== '') {
	foreach (file($opt['deactivate'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
		if (preg_match('/^\s*(\d+)/', $line, $m)) {
			$deactivate[(int)$m[1]] = true;
		}
	}
}

$sitePrices = [];
if ($opt['site-prices'] !== '') {
	foreach (file($opt['site-prices'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
		$c = explode("	", $line);
		if (count($c) >= 2 && (int)$c[0] > 0 && (float)$c[1] > 0) {
			$sitePrices[(int)$c[0]] = (float)$c[1];
		}
	}
}

// --- наши товары 4SIS с ценой BASE
$ours = [];
$rs = CIBlockElement::GetList(
	['ID' => 'ASC'],
	['IBLOCK_ID' => ND_IBLOCK_ID, 'PROPERTY_' . ND_BRAND_PROP => ND_BRAND_ID],
	false, false,
	['ID', 'NAME', 'XML_ID', 'ACTIVE', 'DETAIL_PAGE_URL']
);
while ($r = $rs->GetNext()) {
	$ours[(int)$r['ID']] = $r;
}
$prices = [];
$rp = \Bitrix\Catalog\PriceTable::getList([
	'select' => ['ID', 'PRODUCT_ID', 'PRICE', 'CURRENCY'],
	'filter' => ['@PRODUCT_ID' => array_keys($ours) ?: [0], '=CATALOG_GROUP_ID' => ND_PRICE_GROUP],
]);
while ($p = $rp->fetch()) {
	$prices[(int)$p['PRODUCT_ID']] = $p;
}

// --- план
$rows = [];
$plan = [];
$stat = [];
foreach ($ours as $id => $r) {
	$cur = isset($prices[$id]) ? (float)$prices[$id]['PRICE'] : null;
	$f = $feed[$r['XML_ID']] ?? null;
	$new = null;
	if (isset($deactivate[$id])) {
		$action = $r['ACTIVE'] === 'Y' ? 'деактивировать' : 'уже не активен';
		if ($f || isset($sitePrices[$id])) {
			$action = 'ОШИБКА: есть у 4SIS, не деактивируем';
		}
	} elseif (!$f && isset($sitePrices[$id])) {
		$target = $sitePrices[$id];
		if ($cur !== null && abs($cur - $target) < 0.5) {
			$action = 'цена совпадает (по сайту 4sis)';
		} else {
			$new = $target;
			$action = ($cur === null ? 'поставить цену (не было)' : ($new > $cur ? 'поднять цену' : 'снизить цену')) . ' (по сайту 4sis)';
		}
	} elseif (!$f) {
		$action = 'нет в фиде — не трогаем';
	} elseif ($f['regular'] <= 0) {
		$action = 'в фиде без цены — не трогаем';
	} elseif ($cur !== null && abs($cur - $f['regular']) < 0.5) {
		$action = 'цена совпадает';
	} else {
		$new = $f['regular'];
		$action = $cur === null ? 'поставить цену (не было)' : ($new > $cur ? 'поднять цену' : 'снизить цену');
	}
	if (strpos($action, 'деактивировать') === 0 || $new !== null) {
		$plan[$id] = ['price' => $new, 'deactivate' => strpos($action, 'деактивировать') === 0];
	}
	$stat[$action] = ($stat[$action] ?? 0) + 1;
	$rows[] = [
		$action, $id, $r['ACTIVE'], $r['XML_ID'], $r['~NAME'],
		$cur === null ? '' : $cur, $new === null ? '' : $new,
		($cur && $new) ? round(($new - $cur) / $cur * 100, 1) : '',
		$f ? $f['price'] : '', $f && $f['oldprice'] ? $f['oldprice'] : '', $f ? $f['name'] : '',
		'https://latitudo.ru' . $r['DETAIL_PAGE_URL'],
	];
}
$missing = array_diff_key($deactivate, $ours);
foreach (array_keys($missing) as $id) {
	$stat['ОШИБКА: ID из списка не товар 4SIS'] = ($stat['ОШИБКА: ID из списка не товар 4SIS'] ?? 0) + 1;
	$rows[] = ['ОШИБКА: ID из списка не товар 4SIS', $id, '', '', '', '', '', '', '', '', '', ''];
}

$fh = fopen($outDir . '/report.csv', 'w');
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, ['Действие', 'ID', 'Активен', 'Код 1С', 'Название у нас', 'Цена сейчас', 'Новая цена', 'Изменение, %',
	'4SIS price', '4SIS oldprice', 'Название в фиде', 'Ссылка'], ';');
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

foreach ($stat as $k => $v) {
	if (strpos($k, 'ОШИБКА') === 0) {
		fwrite(STDERR, "Есть ошибки в списке на деактивацию — ничего не записываю.\n");
		exit(1);
	}
}
if ($mode !== 'apply') {
	echo "Сухой прогон — в базе ничего не изменено. Записать: добавьте apply\n";
	exit(0);
}

// --- запись: сначала резервная копия всех затрагиваемых товаров
$backup = [];
foreach ($plan as $id => $p) {
	$backup[$id] = [
		'price' => isset($prices[$id]) ? (float)$prices[$id]['PRICE'] : null,
		'active' => $ours[$id]['ACTIVE'],
		'deactivated' => $p['deactivate'],
	];
}
file_put_contents($outDir . '/backup.json', json_encode($backup, JSON_PRETTY_PRINT));
echo "Резервная копия: $outDir/backup.json\n";

$ok = 0;
$err = 0;
foreach ($plan as $id => $p) {
	$res = true;
	if ($p['price'] !== null) {
		$res = ndSetPrice($id, $p['price']);
	}
	if ($res && $p['deactivate']) {
		$res = ndSetActive($id, 'N');
	}
	$res ? $ok++ : $err++;
}
CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
echo "Записано: $ok, ошибок: $err\n";
echo "Откат: php " . __FILE__ . " rollback=$outDir/backup.json\n";
exit($err ? 1 : 0);


function ndSetPrice(int $id, $price): bool
{
	if ($price === null) {
		return true;
	}
	$row = \Bitrix\Catalog\PriceTable::getList([
		'select' => ['ID'],
		'filter' => ['=PRODUCT_ID' => $id, '=CATALOG_GROUP_ID' => ND_PRICE_GROUP],
	])->fetch();
	$fields = ['PRICE' => (float)$price, 'CURRENCY' => 'RUB'];
	$res = $row
		? \Bitrix\Catalog\Model\Price::update($row['ID'], $fields)
		: \Bitrix\Catalog\Model\Price::add($fields + ['PRODUCT_ID' => $id, 'CATALOG_GROUP_ID' => ND_PRICE_GROUP]);
	if (!$res->isSuccess()) {
		fwrite(STDERR, "Цена $id: " . implode('; ', $res->getErrorMessages()) . "\n");
		return false;
	}
	return true;
}

function ndSetActive(int $id, string $active): bool
{
	$el = new CIBlockElement();
	if (!$el->Update($id, ['ACTIVE' => $active])) {
		fwrite(STDERR, "Активность $id: {$el->LAST_ERROR}\n");
		return false;
	}
	return true;
}

function ndFetch(string $url): string
{
	$ctx = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'latitudo-4sis-sync']]);
	return (string)@file_get_contents($url, false, $ctx);
}
