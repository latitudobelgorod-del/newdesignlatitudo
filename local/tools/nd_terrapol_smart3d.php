<?php
/**
 * Ограждения Террапол Смарт 3D (Ирина, 12.11.2026): новый раздел рядом с «Классик» и «Практик»
 * + 28 товаров (столб, перила, подбалясенник, балясина × 7 цветов) + недостающие цвета крышки
 * и рамки 3D (Анис, Венге Соренто). Фото — с terrapol.ru, цены — розница из «Дилер Террапол 2026»
 * (таблица «Практик и 3D»: ₽/пог.м × длина; Анис, Дуб беленый, Дуб Севилья +10%).
 * Карточки — по образцу Практик (33550/33552/33554/33556 и 27304/27305): те же свойства,
 * своё имя, цвет, фото, цена.
 *
 * Всё создаётся ВЫКЛЮЧЕННЫМ (раздел и товары). --activate включает раздел и товары и переносит
 * в новый раздел крышки/рамки 3D из «Практика» (27304, 33568–33570, 27305, 33565–33567).
 *
 *   php nd_terrapol_smart3d.php <DOCROOT> <папка с plan.json и фото>            — прогон без записи
 *   php nd_terrapol_smart3d.php <DOCROOT> <папка> --apply                        — создать (выключенным)
 *   php nd_terrapol_smart3d.php <DOCROOT> <папка> --activate                     — включить и перенести
 *   php nd_terrapol_smart3d.php <DOCROOT> <папка> rollback                       — удалить созданное, вернуть перенесённое
 * Созданное записывается в <папка>/created.json.
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
$dir = rtrim($argv[2] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix') || !is_file("$dir/plan.json")) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');

const IB = 19;
const IB_OFF = 20;
const PARENT_SECTION = 559;     // Ограждения Террапол
const TPL_SECTION = 320;        // Ограждения Террапол Практик
const SEC_CODE = 'ograzhdeniya-terrapol-smart-3d';
const MOVE = [27304, 33568, 33569, 33570, 27305, 33565, 33566, 33567];
// крепёж и опора из «С этим товаром покупают» у Практик (общая система Практик/Смарт 3D)
const HARDWARE = [25231, 25230, 4405, 4404, 27293, 23342];

$mode = $argv[3] ?? '';
$plan = json_decode(file_get_contents("$dir/plan.json"), true);
$log = "$dir/created.json";
$created = is_file($log) ? json_decode(file_get_contents($log), true) : ['section' => 0, 'products' => [], 'offers' => [], 'moved' => []];
$save = function () use (&$created, $log) {
	file_put_contents($log, json_encode($created, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
};
// запись каталога: Битрикс сам заводит её у родителя при добавлении ТП, поэтому add или update
$product = function (array $f) {
	if (\Bitrix\Catalog\ProductTable::getById($f['ID'])->fetch()) {
		$id = $f['ID'];
		unset($f['ID']);
		return \Bitrix\Catalog\Model\Product::update($id, $f);
	}
	return \Bitrix\Catalog\Model\Product::add($f);
};
$clear = function () {
	CIBlock::clearIblockTagCache(IB);
	CIBlock::clearIblockTagCache(IB_OFF);
};

if ($mode === 'rollback') {
	foreach ($created['moved'] as $id => $old) {
		CIBlockElement::SetElementSection((int)$id, $old);
		\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(IB, (int)$id);
		echo "вернула $id в раздел " . implode(',', $old) . "\n";
	}
	foreach ($created['offers'] as $id) {
		CIBlockElement::Delete((int)$id);
	}
	foreach ($created['products'] as $id) {
		CIBlockElement::Delete((int)$id);
	}
	// ассоциации крышек/рамок, куда добавили новые цвета, — вернуть
	foreach ($created['assoc_before'] ?? [] as $id => $vals) {
		CIBlockElement::SetPropertyValuesEx((int)$id, IB, ['ASSOCIATED' => $vals ?: false]);
	}
	if ($created['section']) {
		CIBlockSection::Delete((int)$created['section']);
	}
	echo 'удалено товаров ' . count($created['products']) . ', ТП ' . count($created['offers']) . ', раздел ' . $created['section'] . "\n";
	unlink($log);
	$clear();
	exit;
}

if ($mode === '--activate') {
	if (!$created['section']) {
		exit("сначала --apply\n");
	}
	(new CIBlockSection())->Update((int)$created['section'], ['ACTIVE' => 'Y']);
	$el = new CIBlockElement();
	foreach ($created['products'] as $id) {
		$el->Update((int)$id, ['ACTIVE' => 'Y']);
	}
	foreach (MOVE as $id) {
		if (isset($created['moved'][$id])) {
			continue;
		}
		$old = [];
		$r = CIBlockElement::GetElementGroups($id, true, ['ID']);
		while ($g = $r->Fetch()) {
			$old[] = (int)$g['ID'];
		}
		$created['moved'][$id] = $old;
		CIBlockElement::SetElementSection($id, [(int)$created['section']]);
		$el->Update($id, ['IBLOCK_SECTION_ID' => (int)$created['section']]);
		\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(IB, $id);
	}
	$save();
	$clear();
	exit('включено: раздел ' . $created['section'] . ', товаров ' . count($created['products']) . ', перенесено ' . count(MOVE) . "\n");
}

// ---- прогон / создание
$tpl = [];
foreach (array_unique(array_column($plan, 'tpl')) as $tid) {
	$o = CIBlockElement::GetList([], ['ID' => $tid], false, false, ['*'])->GetNextElement();
	$tpl[$tid] = ['f' => $o->GetFields(), 'p' => $o->GetProperties()];
}
$enumFormat = [];
$r = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => IB, 'CODE' => 'FORMAT']);
while ($e = $r->Fetch()) {
	$enumFormat[$e['VALUE']] = (int)$e['ID'];
}
foreach ($plan as $x) {
	foreach ($x['photos'] as $f) {
		if (!is_file("$dir/$f")) {
			exit("нет фото $dir/$f\n");
		}
	}
	echo sprintf("%-8s %6d ₽  %s  (фото %d)\n", $x['type'], $x['price'], $x['name'], count($x['photos']));
}
echo 'раздел: ' . ($created['section'] ? "уже есть {$created['section']}" : 'будет создан выключенным') . ', товаров к созданию: ' . count($plan) . "\n";
if ($mode !== '--apply') {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}
if ($created['products']) {
	exit("уже создано (" . count($created['products']) . ") — сначала rollback\n");
}

// 1. раздел — копия «Практика»
$s = CIBlockSection::GetList([], ['IBLOCK_ID' => IB, 'ID' => TPL_SECTION], false, ['*', 'UF_*'])->Fetch();
$sf = [
	'IBLOCK_ID' => IB, 'IBLOCK_SECTION_ID' => PARENT_SECTION, 'ACTIVE' => 'N', 'SORT' => 55,
	'NAME' => 'Ограждения Террапол Смарт 3D', 'CODE' => SEC_CODE,
	'PICTURE' => $s['PICTURE'] ? CFile::MakeFileArray($s['PICTURE']) : false,
];
foreach ($s as $k => $v) {
	if (strpos($k, 'UF_') === 0 && $v && $k !== 'UF_ND_ICON') {
		$sf[$k] = $v;
	}
}
if ($s['UF_ND_ICON']) {
	$sf['UF_ND_ICON'] = CFile::MakeFileArray($s['UF_ND_ICON']);
}
$bs = new CIBlockSection();
$sid = (int)$bs->Add($sf);
if (!$sid) {
	exit('раздел: ' . $bs->LAST_ERROR . "\n");
}
$created['section'] = $sid;
$save();
echo "раздел $sid\n";

// 2. товары
$el = new CIBlockElement();
$byType = [];
foreach ($plan as $i => $x) {
	$t = $tpl[$x['tpl']];
	$photos = array_map(function ($f) use ($dir) {
		return CFile::MakeFileArray("$dir/$f");
	}, $x['photos']);
	$code = CUtil::translit($x['name'], 'ru', ['replace_space' => '-', 'replace_other' => '-', 'max_len' => 200]);
	$fields = [
		'IBLOCK_ID' => IB, 'IBLOCK_SECTION_ID' => $sid, 'ACTIVE' => 'N', 'NAME' => $x['name'], 'CODE' => $code,
		'SORT' => $t['f']['SORT'],
		'PREVIEW_PICTURE' => $photos[0], 'DETAIL_PICTURE' => CFile::MakeFileArray("$dir/{$x['photos'][0]}"),
	];
	$pid = (int)$el->Add($fields);
	if (!$pid) {
		exit("товар «{$x['name']}»: " . $el->LAST_ERROR . "\n");
	}
	$created['products'][] = $pid;
	$save();
	$tp = $t['p'];
	$props = [
		'BRAND' => $tp['BRAND']['VALUE'],
		'COLOR' => $x['cenum'],
		'COLOR_MAIN_EL' => $x['cme'],
		'CML2_ARTICLE' => $pid,
		'SERVICES' => $tp['SERVICES']['VALUE'] ?: false,
		'INSTRUCTIONS_FILE' => $tp['INSTRUCTIONS_FILE']['VALUE'] ?: false,
		'LIST_PRISE' => $tp['LIST_PRISE']['VALUE_ENUM_ID'] ?: false,
		'ND_BRAND_WEIGHT' => $tp['ND_BRAND_WEIGHT']['VALUE'] ?: false,
		'MINIMUM_PRICE' => $x['price'],
		'MAXIMUM_PRICE' => $x['price'],
	];
	if ($x['kind'] === 'offer') {
		$props['FORMAT'] = $enumFormat[$x['format']] ?? false;
		$props['CML2_ATTRIBUTES'] = [
			['VALUE' => $x['colors'], 'DESCRIPTION' => 'Цвет:'],
			['VALUE' => $x['size'], 'DESCRIPTION' => 'Размер, мм:'],
		];
		if ($x['type'] === 'stolb') {
			// из образца оставляем только первый блок (текст про трубу 80х80), галерея Практик не нужна
			$ed = json_decode($tp['EDITOR1']['~VALUE'] ?: $tp['EDITOR1']['VALUE'], false);
			if ($ed && !empty($ed->blocks)) {
				$ed->blocks = [$ed->blocks[0]];
				$props['EDITOR1'] = json_encode($ed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
		}
	} else {
		$props['FORMAT'] = $tp['FORMAT']['VALUE_ENUM_ID'] ?: false;
	}
	CIBlockElement::SetPropertyValuesEx($pid, IB, $props);
	$byType[$x['type']][$x['color']] = $pid;
	$plan[$i]['pid'] = $pid;

	if ($x['kind'] === 'offer') {
		$more = [];
		foreach (array_slice($x['photos'], 1) as $f) {
			$more[] = ['VALUE' => CFile::MakeFileArray("$dir/$f"), 'DESCRIPTION' => ''];
		}
		$oid = (int)$el->Add([
			'IBLOCK_ID' => IB_OFF, 'ACTIVE' => 'Y', 'NAME' => $x['oname'], 'SORT' => 500,
			'PREVIEW_PICTURE' => CFile::MakeFileArray("$dir/{$x['photos'][0]}"),
			'DETAIL_PICTURE' => CFile::MakeFileArray("$dir/{$x['photos'][0]}"),
			'PROPERTY_VALUES' => [
				'CML2_LINK' => $pid, 'COLOR_REF' => $x['cref'], 'MORE_PHOTO' => $more,
				'UNIT_KOEF' => [['VALUE' => (string)$x['koef'], 'DESCRIPTION' => '1']],
			],
		]);
		if (!$oid) {
			exit("ТП «{$x['oname']}»: " . $el->LAST_ERROR . "\n");
		}
		$created['offers'][] = $oid;
		$save();
		CIBlockElement::SetPropertyValuesEx($oid, IB_OFF, ['ARTICLE' => $oid]);
		$product(['ID' => $pid, 'TYPE' => 3, 'MEASURE' => 5, 'CAN_BUY_ZERO' => 'Y', 'QUANTITY' => 0, 'VAT_INCLUDED' => 'N']);
		$product(['ID' => $oid, 'TYPE' => 4, 'MEASURE' => 5, 'CAN_BUY_ZERO' => 'Y', 'QUANTITY' => 0, 'VAT_INCLUDED' => 'N']);
		\Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $oid, 'CATALOG_GROUP_ID' => 1, 'PRICE' => $x['price'], 'CURRENCY' => 'RUB']);
		$plan[$i]['oid'] = $oid;
	} else {
		$product(['ID' => $pid, 'TYPE' => 1, 'MEASURE' => 5, 'CAN_BUY_ZERO' => 'Y', 'QUANTITY' => 0, 'VAT_INCLUDED' => 'N']);
		\Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $pid, 'CATALOG_GROUP_ID' => 1, 'PRICE' => $x['price'], 'CURRENCY' => 'RUB']);
	}
	echo "  $pid {$x['name']} — {$x['price']} ₽\n";
}

// 3. «Вариации цветов» и «С этим товаром покупают»
$capOf = ['kryshka' => [27304, 33568, 33569, 33570], 'ramka' => [27305, 33565, 33566, 33567]];
foreach (['kryshka', 'ramka'] as $k) {
	$group = array_merge($capOf[$k], array_values($byType[$k] ?? []));
	foreach ($capOf[$k] as $id) {
		$vals = [];
		$r = CIBlockElement::GetProperty(IB, $id, [], ['CODE' => 'ASSOCIATED']);
		while ($v = $r->Fetch()) {
			if ($v['VALUE']) {
				$vals[] = (int)$v['VALUE'];
			}
		}
		$created['assoc_before'][$id] = $vals;
	}
	foreach ($group as $id) {
		CIBlockElement::SetPropertyValuesEx($id, IB, ['ASSOCIATED' => $group]);
	}
}
// цвет крышки/рамки для «С этим товаром покупают»: существующие по названию
$capByColor = [];
foreach (array_merge($capOf['kryshka'], $capOf['ramka'], array_values($byType['kryshka'] ?? []), array_values($byType['ramka'] ?? [])) as $id) {
	$n = CIBlockElement::GetByID($id)->Fetch()['NAME'];
	foreach (array_keys($byType['stolb']) as $c) {
		if (mb_stripos($n, $c) !== false) {
			$capByColor[$c][] = $id;
		}
	}
}
foreach (['stolb', 'perila', 'podbal', 'balyas'] as $k) {
	$group = array_values($byType[$k]);
	foreach ($byType[$k] as $c => $id) {
		$same = [];
		foreach (['stolb', 'perila', 'podbal', 'balyas'] as $k2) {
			if ($k2 !== $k) {
				$same[] = $byType[$k2][$c];
			}
		}
		CIBlockElement::SetPropertyValuesEx($id, IB, [
			'ASSOCIATED' => $group,
			'EXPANDABLES' => array_merge($same, $capByColor[$c] ?? [], HARDWARE),
		]);
		\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(IB, $id);
	}
}
$save();
file_put_contents("$dir/plan_done.json", json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$clear();
echo 'готово: раздел ' . $sid . ' (выключен), товаров ' . count($created['products']) . ', ТП ' . count($created['offers']) . "\n";
