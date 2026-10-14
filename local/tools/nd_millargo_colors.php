<?php
/**
 * Millargo: переключатель цветов ротанга/ясеня в карточке — как у террасной доски
 * (Ирина, 14.10.2026). Каждый цвет у Millargo уже отдельный элемент, поэтому
 * ничего не копируем, а связываем существующие:
 *   ROTANG_COLOR / ASH_COLOR — свойство-список, собственный цвет элемента
 *       (значения, XML_ID, сортировка — лист «Справочники»);
 *   COLOR_MAIN_EL — свотч из справочника цветов (HL 9), строки millargo_<xml>;
 *   ASSOCIATED   — «Вариации цветов»: по одному элементу на цвет из файла.
 *       У цвета с несколькими тканями в списке стоит основной (меньший SORT,
 *       затем ID), а в собственном списке элемента — он сам: свой кружок
 *       выделен на карточке любой ткани.
 *
 * План (группы и цвета) — JSON, собранный из millargo_cveta_rotanga_i_yasenya.xlsx.
 *   php nd_millargo_colors.php <DOCROOT> <plan.json> <swatch_dir> <коллекция|all> [--apply]
 *   php nd_millargo_colors.php <DOCROOT> rollback <файл бэкапа>
 * Без --apply — только показывает. Бэкап и журнал — в домашнюю папку.
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
CModule::IncludeModule('highloadblock');

const ND_IB = 19;
const ND_BRAND = 28153; // Millargo
const ND_HL = 9;        // справочник цветов COLOR_MAIN_EL
const ND_PROPS = ['ASSOCIATED', 'COLOR_MAIN_EL', 'ROTANG_COLOR', 'ASH_COLOR'];
$home = getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT']);
$hlClass = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(
	\Bitrix\Highloadblock\HighloadBlockTable::getById(ND_HL)->fetch()
)->getDataClass();

function ndVals(int $id): array
{
	$o = [];
	foreach (ND_PROPS as $code) {
		$v = [];
		$r = CIBlockElement::GetProperty(ND_IB, $id, [], ['CODE' => $code]);
		while ($p = $r->Fetch()) {
			if ($p['VALUE'] !== null && $p['VALUE'] !== '' && $p['VALUE'] !== false) {
				$v[] = $p['PROPERTY_TYPE'] === 'L' ? (int)$p['VALUE'] : $p['VALUE'];
			}
		}
		$o[$code] = $v;
	}
	return $o;
}

$mode = $argv[2] ?? '';
if ($mode === 'rollback') {
	$bk = json_decode(file_get_contents($argv[3]), true);
	foreach ($bk['elements'] as $id => $vals) {
		$set = [];
		foreach ($vals as $code => $v) {
			if ($code === 'ROTANG_COLOR' || $code === 'ASH_COLOR') {
				if (!CIBlockProperty::GetList([], ['IBLOCK_ID' => ND_IB, 'CODE' => $code])->Fetch()) {
					continue;
				}
			}
			$set[$code] = $v ?: false;
		}
		CIBlockElement::SetPropertyValuesEx((int)$id, ND_IB, $set);
		echo "откат $id\n";
	}
	foreach ((array)($bk['created_hl'] ?? []) as $hlId) {
		$hlClass::delete($hlId);
		echo "удалена строка справочника $hlId\n";
	}
	foreach ((array)($bk['created_props'] ?? []) as $pid) {
		CIBlockProperty::Delete($pid);
		echo "удалено свойство $pid\n";
	}
	CIBlock::clearIblockTagCache(ND_IB);
	exit("откат готов\n");
}

$plan = json_decode(file_get_contents($argv[2] ?? ''), true);
$swDir = rtrim($argv[3] ?? '', '/');
$only = $argv[4] ?? '';
$apply = in_array('--apply', $argv, true);
if (!$plan || !is_dir($swDir) || $only === '') {
	exit("нужны план, папка свотчей и коллекция (или all)\n");
}
$groups = array_values(array_filter($plan['groups'], static function ($g) use ($only) {
	return $only === 'all' || mb_strtoupper($g['collection']) === mb_strtoupper($only);
}));
echo 'групп: ' . count($groups) . ($apply ? '' : ' (прогон без записи)') . "\n";

$stamp = date('Ymd-His');
$bkFile = "$home/nd_millargo_backup_$stamp.json";
$logFile = "$home/nd_millargo_log_$stamp.tsv";
$backup = ['elements' => [], 'created_props' => [], 'created_hl' => []];
$log = [];
$saveBk = static function () use (&$backup, $bkFile) {
	file_put_contents($bkFile, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
};

// бэкап — по всем товарам Millargo, а не только по затронутым
if ($apply) {
	$r = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IB, 'PROPERTY_BRAND' => ND_BRAND], false, false, ['ID']);
	while ($e = $r->Fetch()) {
		$backup['elements'][(int)$e['ID']] = ndVals((int)$e['ID']);
	}
	$saveBk();
	echo 'бэкап ' . count($backup['elements']) . " элементов: $bkFile\n";
}

// 1. свойства-списки
$enum = [];
foreach (['ROTANG_COLOR' => 'Цвет ротанга', 'ASH_COLOR' => 'Цвет массива ясеня'] as $code => $title) {
	$prop = CIBlockProperty::GetList([], ['IBLOCK_ID' => ND_IB, 'CODE' => $code])->Fetch();
	if (!$prop) {
		echo "свойство $code «$title» — будет создано\n";
		if (!$apply) {
			continue;
		}
		$values = [];
		foreach ($plan['dict'][$code] as $i => $d) {
			$values['n' . $i] = ['VALUE' => $d['name'], 'XML_ID' => $d['xml'], 'SORT' => (int)$d['sort']];
		}
		// как BASE_COLOUR у доски: список, участвует в фильтре; одно значение — собственный цвет элемента
		$pid = (new CIBlockProperty)->Add([
			'IBLOCK_ID' => ND_IB, 'NAME' => $title, 'CODE' => $code, 'ACTIVE' => 'Y',
			'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'L', 'MULTIPLE' => 'N', 'FILTRABLE' => 'Y',
			'SORT' => 600, 'VALUES' => $values,
		]);
		if (!$pid) {
			exit("ERR свойство $code\n");
		}
		$backup['created_props'][] = (int)$pid;
		$saveBk();
		$log[] = "свойство\t$code\t\tсоздано ID $pid";
		$prop = ['ID' => $pid];
	}
	$r = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $prop['ID']]);
	while ($x = $r->Fetch()) {
		$enum[$code][$x['XML_ID']] = (int)$x['ID'];
	}
}

// 2. свотчи в справочнике цветов
$sw = [];
foreach ($plan['dict'] as $code => $list) {
	foreach ($list as $d) {
		$hx = 'millargo_' . $d['xml'];
		$row = $hlClass::getList(['filter' => ['=UF_XML_ID' => $hx], 'select' => ['ID']])->fetch();
		if (!$row) {
			$file = "$swDir/{$d['xml']}.jpg";
			echo "свотч $hx «{$d['name']}» — будет добавлен" . (is_file($file) ? '' : ' (НЕТ ФАЙЛА!)') . "\n";
			if ($apply) {
				$res = $hlClass::add([
					'UF_NAME' => $d['name'], 'UF_XML_ID' => $hx, 'UF_SORT' => (int)$d['sort'],
					'UF_FILE' => is_file($file) ? CFile::MakeFileArray($file) : false,
				]);
				if (!$res->isSuccess()) {
					exit("ERR свотч $hx: " . implode('; ', $res->getErrorMessages()) . "\n");
				}
				$backup['created_hl'][] = $res->getId();
				$saveBk();
				$log[] = "справочник\t$hx\t\tдобавлен ID " . $res->getId();
			}
		}
		$sw[$d['xml']] = $hx;
	}
}

// 3. элементы
$touched = 0;
foreach ($groups as $g) {
	$code = $g['prop'];
	$sortOf = [];
	$allIds = array_merge(...array_column($g['colors'], 'ids'));
	$r = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IB, 'ID' => $allIds, 'ACTIVE' => 'Y'], false, false, ['ID', 'SORT', 'NAME']);
	while ($e = $r->Fetch()) {
		$sortOf[(int)$e['ID']] = [(int)$e['SORT'], (int)$e['ID'], $e['NAME']];
	}
	$main = [];
	foreach ($g['colors'] as $c) {
		$ids = array_values(array_filter($c['ids'], static fn($id) => isset($sortOf[$id])));
		usort($ids, static fn($a, $b) => [$sortOf[$a][0], $a] <=> [$sortOf[$b][0], $b]);
		if ($ids) {
			$main[$c['xml']] = $ids[0];
		}
	}
	echo "== {$g['key']}: " . implode('; ', array_map(static fn($x, $id) => "$x→$id", array_keys($main), $main)) . "\n";
	foreach ($g['colors'] as $c) {
		foreach ($c['ids'] as $id) {
			if (!isset($sortOf[$id])) {
				echo "  $id неактивен — пропуск\n";
				continue;
			}
			$assoc = count($main) > 1 ? array_values(array_map(static fn($x) => $x === $c['xml'] ? $id : $main[$x], array_keys($main))) : [];
			$new = ['COLOR_MAIN_EL' => $sw[$c['xml']], $code => $enum[$code][$c['xml']] ?? '(после создания)', 'ASSOCIATED' => $assoc];
			$old = ndVals($id);
			echo "  $id «{$sortOf[$id][2]}»: $code={$c['name']}, свотч={$sw[$c['xml']]}, вариации=" . implode(',', $assoc) . "\n";
			if (!$apply) {
				continue;
			}
			CIBlockElement::SetPropertyValuesEx($id, ND_IB, [
				'COLOR_MAIN_EL' => $new['COLOR_MAIN_EL'],
				$code => $new[$code],
				'ASSOCIATED' => $assoc ?: false,
			]);
			foreach ($new as $k => $v) {
				$log[] = "$id\t$k\t" . json_encode($old[$k] ?? [], JSON_UNESCAPED_UNICODE) . "\t" . json_encode($v, JSON_UNESCAPED_UNICODE);
			}
			$touched++;
		}
	}
}
if ($apply) {
	file_put_contents($logFile, "элемент/объект\tсвойство\tбыло\tстало\n" . implode("\n", $log) . "\n");
	CIBlock::clearIblockTagCache(ND_IB);
	echo "записано элементов: $touched\nжурнал: $logFile\nбэкап: $bkFile\n";
}
