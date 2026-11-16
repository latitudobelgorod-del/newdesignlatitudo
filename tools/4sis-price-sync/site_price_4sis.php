<?php
/**
 * Цены 4SIS по сайту 4sis.ru (Ирина, 16.11.2026): фид – только список и связка
 * по коду 1С, цена – с карточки 4sis.ru. Если на 4sis.ru цены нет («Под заказ от
 * 10 дней») – цену у нас убираем: товар показывает «Цена по запросу» и кнопку
 * «Узнать цену» (local/php_interface/include/latitudo_price_request.php).
 *
 * Почему не фид: у товаров «под заказ» 4SIS отдаёт в фиде случайные числа
 * («Эспрессо–150 S» коричневый – 57 800 вместо 218 700, «Гранада» – 22 225 вместо
 * 265 713), а ночная сверка с 23.10 их записывала.
 *
 *   php site_price_4sis.php <plan.tsv>                  – сухой прогон
 *   php site_price_4sis.php <plan.tsv> apply            – записать
 *   php site_price_4sis.php rollback=<backup.json>      – вернуть как было
 *
 * plan.tsv (готовит site_price_plan_4sis.py): ID<TAB>новая цена или NONE<TAB>пояснение.
 * Строки, где цена уже такая, пропускаются. Резервная копия – ~/4sis-sync/<дата>_siteprice/.
 * Предохранитель: убрать цену больше чем у --limit товаров (по умолчанию 40) –
 * отказ, если не передан --force (первый прогон 16.11 – руками, с --force).
 */

$args = array_slice($argv, 1);
$mode = 'dry';
$planFile = '';
$rollbackFile = '';
$limit = 40;
$force = false;
foreach ($args as $a) {
	if ($a === 'apply') {
		$mode = 'apply';
	} elseif (strpos($a, 'rollback=') === 0) {
		$mode = 'rollback';
		$rollbackFile = substr($a, 9);
	} elseif (preg_match('/^--limit=(\d+)$/', $a, $m)) {
		$limit = (int)$m[1];
	} elseif ($a === '--force') {
		$force = true;
	} elseif ($planFile === '' && is_file($a)) {
		$planFile = $a;
	} else {
		fwrite(STDERR, "Неизвестный параметр: $a\n");
		exit(1);
	}
}
if ($mode !== 'rollback' && $planFile === '') {
	fwrite(STDERR, "Нужен файл плана (ID<TAB>цена|NONE)\n");
	exit(1);
}

const ND_IBLOCK_ID = 19;
const ND_PRICE_GROUP = 1; // BASE

$_SERVER['DOCUMENT_ROOT'] = realpath(getenv('HOME') . '/www/latitudo.ru');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
@set_time_limit(0);
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');
\Bitrix\Main\Loader::includeModule('aspro.next');

$outDir = getenv('HOME') . '/4sis-sync/' . date('Y-m-d_His') . '_siteprice' . ($mode === 'apply' ? '' : '_' . $mode);

if ($mode === 'rollback') {
	$backup = json_decode((string)file_get_contents($rollbackFile), true);
	if (!is_array($backup)) {
		fwrite(STDERR, "Не читается $rollbackFile\n");
		exit(1);
	}
	$n = 0;
	foreach ($backup as $id => $old) {
		ndPut((int)$id, $old === null ? null : (float)$old) && $n++;
	}
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	echo "Возвращено: $n из " . count($backup) . "\n";
	exit(0);
}

// --- план
$plan = [];
foreach (file($planFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
	$r = explode("\t", $l);
	if (!ctype_digit($r[0] ?? '')) {
		continue;
	}
	$v = trim($r[1] ?? '');
	if ($v === 'NONE') {
		$plan[(int)$r[0]] = ['new' => null, 'why' => $r[2] ?? ''];
	} elseif (is_numeric($v) && (float)$v > 0) {
		$plan[(int)$r[0]] = ['new' => (float)$v, 'why' => $r[2] ?? ''];
	}
}

// текущие цены и названия
$cur = [];
$names = [];
foreach (array_chunk(array_keys($plan), 500) as $ids) {
	$rs = \Bitrix\Catalog\PriceTable::getList(['select' => ['PRODUCT_ID', 'PRICE'], 'filter' => ['@PRODUCT_ID' => $ids, '=CATALOG_GROUP_ID' => ND_PRICE_GROUP]]);
	while ($p = $rs->fetch()) {
		$cur[(int)$p['PRODUCT_ID']] = (float)$p['PRICE'];
	}
	$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID, 'ID' => $ids], false, false, ['ID', 'NAME', 'ACTIVE']);
	while ($e = $rs->Fetch()) {
		$names[(int)$e['ID']] = ($e['ACTIVE'] === 'Y' ? '' : '[выкл] ') . $e['NAME'];
	}
}

$todo = [];
$cnt = ['убрать' => 0, 'поставить' => 0, 'без изменений' => 0, 'нет товара' => 0];
foreach ($plan as $id => $p) {
	if (!isset($names[$id])) {
		$cnt['нет товара']++;
		continue;
	}
	$old = $cur[$id] ?? null;
	if ($p['new'] === null ? $old === null : ($old !== null && abs($old - $p['new']) < 1)) {
		$cnt['без изменений']++;
		continue;
	}
	$todo[$id] = $old;
	$cnt[$p['new'] === null ? 'убрать' : 'поставить']++;
	printf("%s\t%d\t%s → %s\t%s\t%s\n", $p['new'] === null ? 'убрать' : 'цена', $id,
		$old === null ? 'нет' : number_format($old, 0, '', ' '),
		$p['new'] === null ? 'по запросу' : number_format($p['new'], 0, '', ' '),
		$names[$id], $p['why']);
}
foreach ($cnt as $k => $v) {
	echo "$k: $v\n";
}
if ($mode !== 'apply') {
	echo "Сухой прогон – ничего не изменено. Записать: добавьте apply\n";
	exit(0);
}
if ($cnt['убрать'] > $limit && !$force) {
	echo "ОШИБКА: убрать цену у {$cnt['убрать']} товаров (больше $limit) – ничего не меняю, нужна проверка человеком\n";
	exit(2);
}
if (!$todo) {
	echo "Менять нечего\n";
	exit(0);
}

mkdir($outDir, 0700, true);
file_put_contents("$outDir/backup.json", json_encode($todo, JSON_PRETTY_PRINT));
$ok = 0;
$err = 0;
foreach ($todo as $id => $old) {
	ndPut($id, $plan[$id]['new']) ? $ok++ : $err++;
}
CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
echo "Записано: $ok, ошибок: $err\n";
echo "Откат: php " . __FILE__ . " rollback=$outDir/backup.json\n";
exit($err ? 1 : 0);


/** Поставить цену (null – убрать) и пересчитать то, что от неё зависит. */
function ndPut(int $id, ?float $price): bool
{
	$rs = \Bitrix\Catalog\PriceTable::getList(['select' => ['ID'], 'filter' => ['=PRODUCT_ID' => $id, '=CATALOG_GROUP_ID' => ND_PRICE_GROUP]]);
	$rows = [];
	while ($r = $rs->fetch()) {
		$rows[] = (int)$r['ID'];
	}
	$res = null;
	if ($price === null) {
		foreach ($rows as $pid) {
			$res = \Bitrix\Catalog\Model\Price::delete($pid);
			if (!$res->isSuccess()) {
				break;
			}
		}
	} elseif ($rows) {
		$res = \Bitrix\Catalog\Model\Price::update($rows[0], ['PRODUCT_ID' => $id, 'PRICE' => $price, 'CURRENCY' => 'RUB']);
	} else {
		$res = \Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $id, 'CATALOG_GROUP_ID' => ND_PRICE_GROUP, 'PRICE' => $price, 'CURRENCY' => 'RUB']);
	}
	if ($res && !$res->isSuccess()) {
		fwrite(STDERR, "Цена $id: " . implode('; ', $res->getErrorMessages()) . "\n");
		return false;
	}
	// MINIMUM_PRICE / MAXIMUM_PRICE (сортировка по цене) – их держит обработчик Аспро
	if (class_exists('CNextEvents')) {
		CNextEvents::DoIBlockAfterSave(0, ['PRODUCT_ID' => $id]);
	}
	if ($price === null) {
		// у товара без цены Аспро свойства не трогает – чистим сами, иначе сортировка по старой цене
		CIBlockElement::SetPropertyValuesEx($id, ND_IBLOCK_ID, ['MINIMUM_PRICE' => false, 'MAXIMUM_PRICE' => false]);
	}
	\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(ND_IBLOCK_ID, $id);
	return true;
}
