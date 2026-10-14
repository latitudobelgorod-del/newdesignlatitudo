<?php
/**
 * Millargo: «С этим товаром покупают» (EXPANDABLES) — мебель той же коллекции
 * в той же расцветке (Ирина, 14.10.2026: к «Дивану ТИТУЛО Доминикана Моссл 100»
 * — кресла, столы, комплекты ТИТУЛО Доминикана Моссл 100).
 *
 * Из названия: тип («Кресло»), коллекция (слова ЗАГЛАВНЫМИ: ТИТУЛО, ГРАНД ПРИМО),
 * модель (размер, «диннер», «2»…), цвет ротанга/ясеня, ткань (Моссл 100, Ямайка беж).
 * Порядок подбора, до ND_MAX штук:
 *   1) та же коллекция, тот же цвет и та же ткань;
 *   2) та же коллекция, тот же цвет (другая ткань);
 *   3) у товара без цвета — та же ткань, потом вся коллекция.
 * Сам товар и его варианты (тот же тип+коллекция+модель) не ставятся. Только активные.
 *
 *   php nd_millargo_expandables.php <DOCROOT> [--apply]
 *   php nd_millargo_expandables.php <DOCROOT> rollback <файл бэкапа>
 * Без --apply — план в ~/nd_millargo_exp_plan.tsv. Бэкап и журнал — в домашнюю папку.
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix')) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

const ND_IB = 19;
const ND_BRAND = 28153;
const ND_MAX = 12;
$home = getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT']);

$mode = $argv[2] ?? '';
if ($mode === 'rollback') {
	$bk = json_decode(file_get_contents($argv[3]), true);
	foreach ($bk as $id => $vals) {
		CIBlockElement::SetPropertyValuesEx((int)$id, ND_IB, ['EXPANDABLES' => $vals ?: false]);
	}
	CIBlock::clearIblockTagCache(ND_IB);
	exit('откат: ' . count($bk) . " элементов\n");
}
$apply = $mode === '--apply';

// цвета: словарь ротанга/ясеня + цвета, что встречаются в названиях на сайте
$colors = ['доминикана', 'индийское дерево', 'черный металлик', 'лунный свет', 'ирбис', 'шоколад металлик',
	'американский орех', 'млечный путь', 'сицилия', 'африканский закат', 'грецкий орех', 'лесной орех', 'орех',
	'дуб мореный', 'дуб', 'венге', 'цитрус', 'серебро', 'светло-серый', 'темный', 'светлый',
	'термоясень черный', 'термоясень бесцветный', 'термоясень'];
usort($colors, static fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
$fabricRe = '~\b((?:калиста|моссл|зимми|даер)\s*\d+)\b~u';
$typePrio = ['комплект' => 1, 'лаундж' => 1, 'диван' => 2, 'угловой' => 2, 'кресло' => 3, 'стол' => 4, 'стул' => 5,
	'пуф' => 6, 'модуль' => 7, 'шезлонг' => 7, 'торшер' => 8];

$norm = static fn($s) => trim(preg_replace('~\s+~u', ' ', str_replace('ё', 'е', mb_strtolower((string)$s))));
$items = [];
$r = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => ND_IB, 'PROPERTY_BRAND' => ND_BRAND], false, false, ['ID', 'NAME', 'ACTIVE', 'SORT']);
while ($e = $r->Fetch()) {
	if (isset($items[$e['ID']])) {
		continue;
	}
	$words = preg_split('~\s+~u', trim($e['NAME']));
	$type = [];
	$coll = [];
	$i = 0;
	for (; $i < count($words); $i++) {
		$w = $words[$i];
		$isUpper = mb_strlen($w) >= 3 && preg_match('~^[А-ЯЁ-]+$~u', $w);
		if ($isUpper) {
			$coll[] = $w;
		} elseif ($coll) {
			break;
		} else {
			$type[] = $w;
		}
	}
	$rest = $norm(implode(' ', array_slice($words, $i)));
	$color = '';
	$pos = null;
	foreach ($colors as $c) {
		if (preg_match('~(^|\s)' . preg_quote($c, '~') . '(\s|$)~u', $rest, $m, PREG_OFFSET_CAPTURE)) {
			$p = $m[0][1];
			if ($pos === null || $p < $pos) {
				$pos = $p;
				$color = $c;
			}
		}
	}
	$before = $pos === null ? $rest : trim(mb_substr($rest, 0, mb_strlen(substr($rest, 0, $pos))));
	$after = $pos === null ? '' : trim(mb_substr($rest, mb_strlen(substr($rest, 0, $pos)) + mb_strlen($color) + 1));
	preg_match_all($fabricRe, $before, $fb);
	$fabric = trim(implode(' ', $fb[1]) . ' ' . $after);
	$fabric = trim(preg_replace('~\(.*?\)~u', '', $fabric)); // «(с упаковкой)»
	$model = trim(preg_replace(['~' . trim($fabricRe, '~u') . '~u', '~\b(термо)?ясень\b~u', '~\s+~u'], ['', '', ' '], $before));
	if ($pos === null) {
		$fabric = trim(implode(' ', $fb[1]));
	}
	$t = $norm(implode(' ', $type));
	$items[(int)$e['ID']] = [
		'id' => (int)$e['ID'], 'name' => $e['NAME'], 'active' => $e['ACTIVE'] === 'Y', 'sort' => (int)$e['SORT'],
		'type' => $t, 'coll' => $norm(implode(' ', $coll)), 'model' => $model, 'color' => $color, 'fabric' => $fabric,
		'key' => $t . '|' . $norm(implode(' ', $coll)) . '|' . $model,
		'prio' => $typePrio[explode(' ', $t)[0] ?? ''] ?? 9,
	];
}

$plan = [];
foreach ($items as $it) {
	if (!$it['active'] || $it['coll'] === '') {
		continue;
	}
	$pool = array_filter($items, static fn($x) => $x['active'] && $x['coll'] === $it['coll'] && $x['key'] !== $it['key']);
	$tiers = [];
	foreach ($pool as $x) {
		if ($it['color'] !== '') {
			if ($x['color'] !== $it['color']) {
				continue;
			}
			$tier = ($it['fabric'] !== '' && $x['fabric'] === $it['fabric']) ? 1 : 2;
		} else {
			$tier = ($it['fabric'] !== '' && $x['fabric'] === $it['fabric']) ? 1 : 3;
		}
		$tiers[] = [$tier, $x['prio'], $x['sort'], $x['id']];
	}
	sort($tiers);
	// у одного типа+модели берём один вариант (ближайший по ткани), чтобы не было трёх одинаковых кресел
	$picked = [];
	$usedKey = [];
	foreach ($tiers as [$tier, , , $xid]) {
		$k = $items[$xid]['key'];
		if (isset($usedKey[$k])) {
			continue;
		}
		$usedKey[$k] = true;
		$picked[] = $xid;
		if (count($picked) >= ND_MAX) {
			break;
		}
	}
	$plan[$it['id']] = $picked;
}

$tsv = "ID\tТовар\tколлекция|тип|модель|цвет|ткань\tС этим товаром покупают\n";
foreach ($plan as $id => $ids) {
	$it = $items[$id];
	$tsv .= "$id\t{$it['name']}\t{$it['coll']}|{$it['type']}|{$it['model']}|{$it['color']}|{$it['fabric']}\t"
		. implode('; ', array_map(static fn($x) => $items[$x]['name'], $ids)) . "\n";
}
file_put_contents("$home/nd_millargo_exp_plan.tsv", $tsv);
$filled = count(array_filter($plan));
echo 'товаров: ' . count($plan) . ", с подобранными связями: $filled, без связей: " . (count($plan) - $filled) . "\nплан: $home/nd_millargo_exp_plan.tsv\n";
if (!$apply) {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

$stamp = date('Ymd-His');
$backup = [];
$log = "ID\tбыло\tстало\n";
foreach ($items as $id => $it) {
	$old = [];
	$q = CIBlockElement::GetProperty(ND_IB, $id, [], ['CODE' => 'EXPANDABLES']);
	while ($p = $q->Fetch()) {
		if ($p['VALUE']) {
			$old[] = (int)$p['VALUE'];
		}
	}
	$backup[$id] = $old;
}
file_put_contents("$home/nd_millargo_exp_backup_$stamp.json", json_encode($backup));
foreach ($plan as $id => $ids) {
	if (!$ids && !$backup[$id]) {
		continue;
	}
	CIBlockElement::SetPropertyValuesEx($id, ND_IB, ['EXPANDABLES' => $ids ?: false]);
	$log .= "$id\t" . implode(',', $backup[$id]) . "\t" . implode(',', $ids) . "\n";
}
file_put_contents("$home/nd_millargo_exp_log_$stamp.tsv", $log);
CIBlock::clearIblockTagCache(ND_IB);
echo "записано. бэкап: $home/nd_millargo_exp_backup_$stamp.json, журнал: $home/nd_millargo_exp_log_$stamp.tsv\n";
