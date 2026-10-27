<?php
/**
 * Стикер «Акция» у товаров 4SIS, у которых на 4sis.ru сейчас акция (Ирина, 27.10.2026: «на latitudo цену ставим
 * розничную, но поставь метку Акция, чтобы менеджеры понимали»). Стикер видят и покупатели — так решила Ирина.
 *
 *   php promo_4sis.php <report.csv от sync_4sis.php> <файл акций с сайта> [apply]
 *   php promo_4sis.php rollback=<changes.json>
 *
 * Акция у 4SIS:
 *   - товар в фиде — <oldprice> больше <price> (колонки «4SIS price» / «4SIS oldprice» отчёта sync_4sis.php);
 *   - товара нет в фиде — на карточке 4sis.ru есть зачёркнутая цена (файл ID<TAB>цена<TAB>без акции от check_missing_4sis.py).
 * Стикер — значение «Акция» (XML_ID AKCIYA) свойства HIT «Наши предложения»; другие значения HIT не трогаем.
 * Снимаем только тот стикер, который поставил этот скрипт (список — ~/4sis-sync/promo_set.txt): «Акция»,
 * поставленную руками, не трогаем. Цена у нас не меняется — она по-прежнему розничная (без акции).
 */

$args = array_slice($argv, 1);
$mode = 'dry';
$files = [];
$rollbackFile = '';
foreach ($args as $a) {
	if ($a === 'apply') {
		$mode = 'apply';
	} elseif (strpos($a, 'rollback=') === 0) {
		$mode = 'rollback';
		$rollbackFile = substr($a, 9);
	} else {
		$files[] = $a;
	}
}

const ND_IBLOCK_ID = 19;
const ND_BRAND_ID = 28619;
$home = getenv('HOME');
$setFile = $home . '/4sis-sync/promo_set.txt';

$_SERVER['DOCUMENT_ROOT'] = $home . '/www/latitudo.ru';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');

$enum = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID, 'CODE' => 'HIT', 'XML_ID' => 'AKCIYA'])->Fetch();
if (!$enum) {
	fwrite(STDERR, "ОШИБКА: нет значения «Акция» (AKCIYA) у свойства HIT\n");
	exit(1);
}
$akc = (int)$enum['ID'];

// текущие значения HIT товара
function ndHit(int $id): array
{
	$v = [];
	$rs = CIBlockElement::GetProperty(ND_IBLOCK_ID, $id, [], ['CODE' => 'HIT']);
	while ($p = $rs->Fetch()) {
		if ($p['VALUE']) {
			$v[] = (int)$p['VALUE'];
		}
	}
	return $v;
}
function ndSetHit(int $id, array $v): void
{
	CIBlockElement::SetPropertyValuesEx($id, ND_IBLOCK_ID, ['HIT' => $v ? array_values(array_unique($v)) : false]);
	// Фильтр раздела берёт значения из фасетного индекса (у ИБ 19 он включён), а SetPropertyValuesEx его не
	// обновляет — без этого «Акция» есть на товаре, но в фильтре её нет (27.10.2026).
	\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(ND_IBLOCK_ID, $id);
}
$managed = is_file($setFile) ? array_flip(array_filter(array_map('intval', file($setFile)))) : [];

if ($mode === 'rollback') {
	$ch = json_decode((string)file_get_contents($rollbackFile), true) ?: [];
	foreach ($ch['added'] ?? [] as $id) {
		ndSetHit((int)$id, array_diff(ndHit((int)$id), [$akc]));
		unset($managed[(int)$id]);
	}
	foreach ($ch['removed'] ?? [] as $id) {
		ndSetHit((int)$id, array_merge(ndHit((int)$id), [$akc]));
		$managed[(int)$id] = 1;
	}
	file_put_contents($setFile, implode("\n", array_keys($managed)) . "\n");
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	echo 'Откат: снято ' . count($ch['added'] ?? []) . ', возвращено ' . count($ch['removed'] ?? []) . "\n";
	exit(0);
}

if (count($files) < 2 || !is_file($files[0])) {
	fwrite(STDERR, "Укажите report.csv и файл акций с сайта\n");
	exit(1);
}

// где у 4SIS акция: ID => [цена сейчас, без акции]
$want = [];
$fh = fopen($files[0], 'r');
fgets($fh); // шапка
while (($r = fgetcsv($fh, 0, ';')) !== false) {
	$price = (float)str_replace(',', '.', $r[8] ?? '');
	$old = (float)str_replace(',', '.', $r[9] ?? '');
	if ((int)($r[1] ?? 0) > 0 && $old > $price && $price > 0) {
		$want[(int)$r[1]] = [$price, $old];
	}
}
fclose($fh);
if (is_file($files[1])) {
	foreach (file($files[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
		[$id, $cur, $old] = array_pad(explode("\t", $line), 3, 0);
		if ((float)$old > (float)$cur && (float)$cur > 0) {
			$want[(int)$id] = [(float)$cur, (float)$old];
		}
	}
}

// все товары 4SIS
$added = $removed = [];
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID, 'PROPERTY_BRAND' => ND_BRAND_ID], false, false, ['ID', 'NAME']);
$names = [];
while ($e = $rs->Fetch()) {
	$id = (int)$e['ID'];
	$names[$id] = $e['NAME'];
	$hit = ndHit($id);
	$has = in_array($akc, $hit, true);
	if (isset($want[$id]) && !$has) {
		$added[] = $id;
		if ($mode === 'apply') {
			ndSetHit($id, array_merge($hit, [$akc]));
		}
		$managed[$id] = 1;
	} elseif (!isset($want[$id]) && $has && isset($managed[$id])) {
		$removed[] = $id;
		if ($mode === 'apply') {
			ndSetHit($id, array_diff($hit, [$akc]));
		}
		unset($managed[$id]);
	}
	// стоит «Акция», поставленная руками, — не берём её под управление и не снимаем
}

echo 'Акция у 4SIS: ' . count(array_intersect_key($want, $names)) . " товаров; стикер «Акция» поставить " . count($added) . ', снять ' . count($removed) . "\n";
foreach ($added as $id) {
	echo "  + $id {$names[$id]} — у 4SIS " . number_format($want[$id][0], 0, ',', ' ') . ' ₽ вместо ' . number_format($want[$id][1], 0, ',', ' ') . " ₽\n";
}
foreach ($removed as $id) {
	echo "  − $id {$names[$id]} — акция у 4SIS закончилась\n";
}
if ($mode !== 'apply') {
	echo "Сухой прогон — ничего не изменено. Поставить: добавьте apply\n";
	exit(0);
}
file_put_contents($setFile, implode("\n", array_keys($managed)) . "\n");
$outDir = $home . '/4sis-sync/' . date('Y-m-d_His') . '_promo';
mkdir($outDir, 0700, true);
file_put_contents($outDir . '/changes.json', json_encode(['added' => $added, 'removed' => $removed]));
if ($added || $removed) {
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
}
echo "Откат: php " . __FILE__ . " rollback=$outDir/changes.json\n";
