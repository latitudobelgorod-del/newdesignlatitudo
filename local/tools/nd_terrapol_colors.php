<?php
/**
 * Недостающие цвета Террапола (Ирина, 12.11.2026, сверка с образцами для филиалов):
 * новый цвет = копия карточки-образца той же позиции (все свойства, разделы, ТП по длинам, единицы),
 * своё имя, цвет, фото с terrapol.ru, цена по прайсу «Дилер Террапол 2026»
 * (у образца цена уже по прайсу; Анис и Дуб беленый +10% к цене штуки, как у Дуба Севилья).
 * Из блоков редактора выкидываются галереи фото образца (там чужой цвет).
 * «С этим товаром покупают»: позиции цвета образца заменяются такими же нового цвета, если они есть,
 * иначе убираются. «Вариации цветов» дополняются у всей группы. Для Сердолика заводятся цвета в справочниках.
 *
 *   php nd_terrapol_colors.php <DOCROOT> <папка с plan.json и фото>          — прогон без записи
 *   php nd_terrapol_colors.php <DOCROOT> <папка> --apply                      — создать (включёнными)
 *   php nd_terrapol_colors.php <DOCROOT> <папка> rollback                     — удалить созданное, вернуть свойства
 * Журнал: <папка>/created.json
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
\Bitrix\Main\Loader::includeModule('highloadblock');

const IB = 19;
const IB_OFF = 20;
// свойства, которые у копии свои
const SKIP_P = ['CML2_ARTICLE', 'ND_SHORT_URI', 'ASSOCIATED', 'EXPANDABLES', 'COLOR_MAIN_EL', 'COLOR', 'HIT',
	'MINIMUM_PRICE', 'MAXIMUM_PRICE', 'EDITOR1', 'MORE_PHOTO'];
const SKIP_O = ['CML2_LINK', 'COLOR_REF', 'MORE_PHOTO', 'ARTICLE', 'VID'];

$mode = $argv[3] ?? '';
$plan = json_decode(file_get_contents("$dir/plan.json"), true);
$log = "$dir/created.json";
$created = is_file($log) ? json_decode(file_get_contents($log), true)
	: ['products' => [], 'offers' => [], 'hl' => [], 'props_before' => []];
$save = function () use (&$created, $log) {
	file_put_contents($log, json_encode($created, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
};
$hlClass = function ($table) {
	$hl = \Bitrix\Highloadblock\HighloadBlockTable::getList(['filter' => ['TABLE_NAME' => $table]])->fetch();
	return \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hl)->getDataClass();
};
$propVals = function ($id, $code) {
	$vals = [];
	$r = CIBlockElement::GetProperty(IB, $id, [], ['CODE' => $code]);
	while ($v = $r->Fetch()) {
		if ($v['VALUE'] !== '' && $v['VALUE'] !== null) {
			$vals[] = $v['DESCRIPTION'] !== null && $v['DESCRIPTION'] !== '' ? ['VALUE' => $v['VALUE'], 'DESCRIPTION' => $v['DESCRIPTION']] : $v['VALUE'];
		}
	}
	return $vals;
};
$clear = function () {
	CIBlock::clearIblockTagCache(IB);
	CIBlock::clearIblockTagCache(IB_OFF);
};

if ($mode === 'rollback') {
	foreach ($created['offers'] as $id) {
		CIBlockElement::Delete((int)$id);
	}
	foreach ($created['products'] as $id) {
		CIBlockElement::Delete((int)$id);
	}
	foreach ($created['props_before'] as $id => $props) {
		foreach ($props as $code => $vals) {
			CIBlockElement::SetPropertyValuesEx((int)$id, IB, [$code => $vals ?: false]);
		}
	}
	foreach ($created['hl'] as $h) {
		$hlClass($h['table'])::delete($h['id']);
	}
	echo 'удалено товаров ' . count($created['products']) . ', ТП ' . count($created['offers']) . ', записей справочников ' . count($created['hl']) . "\n";
	unlink($log);
	$clear();
	exit;
}

// ---- прогон
$norm = function ($s) {
	return mb_strtolower(str_replace(['ё', 'Ё'], ['е', 'Е'], trim($s)));
};
$tpls = [];
foreach ($plan as $i => $x) {
	$t = $x['tpl'];
	if (!isset($tpls[$t])) {
		$o = CIBlockElement::GetList([], ['ID' => $t], false, false, ['*'])->GetNextElement();
		$f = $o->GetFields();
		$offs = [];
		$r = CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => IB_OFF, '=PROPERTY_CML2_LINK' => $t], false, false, ['*']);
		while ($oo = $r->GetNextElement()) {
			$of = $oo->GetFields();
			$offs[] = ['f' => $of, 'p' => $oo->GetProperties(), 'price' => (float)CPrice::GetBasePrice($of['ID'])['PRICE'],
				'cat' => \Bitrix\Catalog\ProductTable::getById($of['ID'])->fetch()];
		}
		$groups = [];
		$r = CIBlockElement::GetElementGroups($t, true, ['ID']);
		while ($g = $r->Fetch()) {
			$groups[] = (int)$g['ID'];
		}
		$tpls[$t] = ['f' => $f, 'p' => $o->GetProperties(), 'offers' => $offs, 'groups' => $groups,
			'cat' => \Bitrix\Catalog\ProductTable::getById($t)->fetch()];
	}
	$T = $tpls[$t];
	if (mb_strpos($T['f']['~NAME'], $x['tcolor']) === false) {
		exit("в названии образца $t нет цвета «{$x['tcolor']}»\n");
	}
	$name = str_replace($x['tcolor'], $x['color'], $T['f']['~NAME']);
	if (CIBlockElement::GetList([], ['IBLOCK_ID' => IB, '=NAME' => $name], [])) {
		exit("уже есть товар «{$name}»\n");
	}
	foreach ($x['photos'] as $ph) {
		if (!is_file("$dir/$ph")) {
			exit("нет фото $ph\n");
		}
	}
	$prices = [];
	foreach ($T['offers'] as $of) {
		$p = $of['price'];
		if ($x['plus10']) {
			$p = round($p * 1.1);   // как у уже заведённых Дуб Севилья: +10% к цене штуки
		}
		$prices[] = $p;
	}
	$plan[$i]['name'] = $name;
	$plan[$i]['prices'] = $prices;
	echo sprintf("%-80s %s ₽  (образец %d: %s ₽; фото %d)\n", $name, implode(' / ', $prices), $t,
		implode(' / ', array_column($T['offers'], 'price')), count($x['photos']));
}
if ($mode !== '--apply') {
	exit('Прогон без записи (' . count($plan) . " шт.). Для записи добавьте --apply\n");
}
if ($created['products']) {
	exit("уже создано — сначала rollback\n");
}

// ---- справочники цветов для Сердолика
$need = array_filter($plan, function ($x) {
	return $x['cref'][0] === '@';
});
if ($need && is_file("$dir/serdolik_swatch.jpg")) {
	$c4 = $hlClass('b_hlbd_tsvetaaytekhplast');
	$c9 = $hlClass('next_tsveta_el');
	$src4 = $c4::getById(564)->fetch();   // Маренго (Мультиколор) — образец записи
	$src9 = $c9::getById(742)->fetch();
	$xml4 = substr(str_shuffle('abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);
	foreach ([[$c4, $src4, 'b_hlbd_tsvetaaytekhplast', $xml4], [$c9, $src9, 'next_tsveta_el', 'serdolik_terrapol']] as [$cls, $src, $table, $xml]) {
		$row = $src;
		unset($row['ID']);
		$row['UF_NAME'] = 'Сердолик';
		$row['UF_XML_ID'] = $xml;
		$row['UF_FILE'] = CFile::MakeFileArray("$dir/serdolik_swatch.jpg");
		$res = $cls::add($row);
		if (!$res->isSuccess()) {
			exit("справочник $table: " . implode('; ', $res->getErrorMessages()) . "\n");
		}
		$created['hl'][] = ['table' => $table, 'id' => $res->getId()];
		$save();
	}
	foreach ($plan as $i => $x) {
		if ($x['cref'] === '@serdolik') {
			$plan[$i]['cref'] = $xml4;
		}
	}
	echo "справочники: Сердолик $xml4 / serdolik_terrapol\n";
}

$product = function (array $f) {
	if (\Bitrix\Catalog\ProductTable::getById($f['ID'])->fetch()) {
		$id = $f['ID'];
		unset($f['ID']);
		return \Bitrix\Catalog\Model\Product::update($id, $f);
	}
	return \Bitrix\Catalog\Model\Product::add($f);
};
$catFields = function ($cat, $id, $type) {
	$f = ['ID' => $id, 'TYPE' => $type, 'QUANTITY' => 0];
	foreach (['MEASURE', 'CAN_BUY_ZERO', 'VAT_ID', 'VAT_INCLUDED', 'WEIGHT', 'WIDTH', 'LENGTH', 'HEIGHT', 'QUANTITY_TRACE', 'SUBSCRIBE', 'PURCHASING_PRICE', 'PURCHASING_CURRENCY'] as $k) {
		if (isset($cat[$k]) && $cat[$k] !== null && $cat[$k] !== '') {
			$f[$k] = $cat[$k];
		}
	}
	return $f;
};
$ratio = function ($from, $to) {
	$r = \Bitrix\Catalog\MeasureRatioTable::getList(['filter' => ['PRODUCT_ID' => $from]])->fetch();
	if ($r && (float)$r['RATIO'] != 1) {
		$ex = \Bitrix\Catalog\MeasureRatioTable::getList(['filter' => ['PRODUCT_ID' => $to]])->fetch();
		$ex ? \Bitrix\Catalog\MeasureRatioTable::update($ex['ID'], ['RATIO' => $r['RATIO']])
			: \Bitrix\Catalog\MeasureRatioTable::add(['PRODUCT_ID' => $to, 'RATIO' => $r['RATIO'], 'IS_DEFAULT' => 'Y']);
	}
};
// значения свойств образца в виде для SetPropertyValuesEx
$copyProps = function ($props, $skip, $tcolor, $color) {
	$out = [];
	foreach ($props as $code => $p) {
		if (in_array($code, $skip) || $p['PROPERTY_TYPE'] === 'F') {
			continue;
		}
		if ($p['VALUE'] === '' || $p['VALUE'] === false || $p['VALUE'] === null || $p['VALUE'] === []) {
			continue;
		}
		if ($p['PROPERTY_TYPE'] === 'L') {
			$out[$code] = $p['VALUE_ENUM_ID'];
			continue;
		}
		$v = $p['~VALUE'];
		if ($p['PROPERTY_TYPE'] === 'S' && !$p['USER_TYPE']) {
			$v = is_array($v) ? array_map(function ($s) use ($tcolor, $color) {
				return str_replace($tcolor, $color, $s);
			}, $v) : str_replace($tcolor, $color, $v);
		}
		if ($p['WITH_DESCRIPTION'] === 'Y') {
			$vv = [];
			foreach ((array)$v as $k => $one) {
				$vv[] = ['VALUE' => $one, 'DESCRIPTION' => ((array)$p['~DESCRIPTION'])[$k] ?? ''];
			}
			$v = $vv;
		}
		$out[$code] = $v;
	}
	return $out;
};
$editor = function ($json) {
	$ed = json_decode($json, false);
	if (!$ed || empty($ed->blocks)) {
		return false;
	}
	$keep = [];
	foreach ($ed->blocks as $b) {
		if (in_array($b->name, ['gallery', 'image', 'images', 'slider'])) {
			if ($keep && end($keep)->name === 'htag') {
				array_pop($keep);
			}
			continue;
		}
		$keep[] = $b;
	}
	if (!$keep) {
		return false;
	}
	$ed->blocks = $keep;
	return json_encode($ed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
};

$el = new CIBlockElement();
$newByTpl = [];   // tpl => [color => pid]
foreach ($plan as $i => $x) {
	$T = $tpls[$x['tpl']];
	$tf = $T['f'];
	// без фото – карточка с заглушкой «Нет фото» (цвета нет на terrapol.ru)
	$ph = function ($n) use ($dir, $x) {
		return isset($x['photos'][$n]) ? CFile::MakeFileArray("$dir/{$x['photos'][$n]}") : false;
	};
	$pid = (int)$el->Add([
		'IBLOCK_ID' => IB, 'IBLOCK_SECTION_ID' => $tf['IBLOCK_SECTION_ID'], 'ACTIVE' => 'Y',
		'NAME' => $x['name'], 'SORT' => $tf['SORT'],
		'CODE' => CUtil::translit($x['name'], 'ru', ['replace_space' => '-', 'replace_other' => '-', 'max_len' => 200]),
		'PREVIEW_TEXT' => str_replace($x['tcolor'], $x['color'], $tf['~PREVIEW_TEXT']), 'PREVIEW_TEXT_TYPE' => $tf['PREVIEW_TEXT_TYPE'],
		'DETAIL_TEXT' => str_replace($x['tcolor'], $x['color'], $tf['~DETAIL_TEXT']), 'DETAIL_TEXT_TYPE' => $tf['DETAIL_TEXT_TYPE'],
		'PREVIEW_PICTURE' => $ph(0), 'DETAIL_PICTURE' => $ph(0),
	]);
	if (!$pid) {
		exit("товар «{$x['name']}»: " . $el->LAST_ERROR . "\n");
	}
	$created['products'][] = $pid;
	$save();
	if (count($T['groups']) > 1) {
		CIBlockElement::SetElementSection($pid, $T['groups'], false, 0, $tf['IBLOCK_SECTION_ID']);
	}
	$props = $copyProps($T['p'], SKIP_P, $x['tcolor'], $x['color']);
	$props['CML2_ARTICLE'] = $pid;
	$props['COLOR_MAIN_EL'] = $x['cme'];
	if ($T['p']['COLOR']['VALUE'] && $x['cenum']) {
		$props['COLOR'] = $x['cenum'];
	}
	if ($T['p']['EDITOR1']['~VALUE'] && ($e = $editor($T['p']['EDITOR1']['~VALUE']))) {
		$props['EDITOR1'] = $e;
	}
	$props['MINIMUM_PRICE'] = min($x['prices']);
	$props['MAXIMUM_PRICE'] = max($x['prices']);
	CIBlockElement::SetPropertyValuesEx($pid, IB, $props);
	$product($catFields($T['cat'], $pid, $T['offers'] ? 3 : 1));

	foreach ($T['offers'] as $k => $of) {
		$op = $copyProps($of['p'], SKIP_O, $x['tcolor'], $x['color']);
		$op['CML2_LINK'] = $pid;
		if ($of['p']['COLOR_REF']['VALUE']) {
			$op['COLOR_REF'] = $x['cref'];
		}
		if (count($x['photos']) > 1) {
			$op['MORE_PHOTO'] = array_map(function ($f) use ($dir) {
				return ['VALUE' => CFile::MakeFileArray("$dir/$f"), 'DESCRIPTION' => ''];
			}, array_slice($x['photos'], 1));
		}
		$oid = (int)$el->Add([
			'IBLOCK_ID' => IB_OFF, 'ACTIVE' => 'Y', 'SORT' => $of['f']['SORT'],
			'NAME' => str_replace($x['tcolor'], $x['color'], $of['f']['~NAME']),
			'PREVIEW_PICTURE' => $ph(0), 'DETAIL_PICTURE' => $ph(0),
			'PROPERTY_VALUES' => $op,
		]);
		if (!$oid) {
			exit("ТП: " . $el->LAST_ERROR . "\n");
		}
		$created['offers'][] = $oid;
		$save();
		CIBlockElement::SetPropertyValuesEx($oid, IB_OFF, ['ARTICLE' => $oid]);
		$product($catFields($of['cat'], $oid, 4));
		$ratio($of['f']['ID'], $oid);
		\Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $oid, 'CATALOG_GROUP_ID' => 1, 'PRICE' => $x['prices'][$k], 'CURRENCY' => 'RUB']);
	}
	if (!$T['offers']) {
		$ratio($x['tpl'], $pid);
		\Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $pid, 'CATALOG_GROUP_ID' => 1, 'PRICE' => $x['prices'][0], 'CURRENCY' => 'RUB']);
	}
	$newByTpl[$x['tpl']][$x['color']] = $pid;
	$plan[$i]['pid'] = $pid;
	echo "  $pid {$x['name']} — " . implode(' / ', $x['prices']) . " ₽\n";
}

// ---- «Вариации цветов» у всей группы и список цветов в CML2_ATTRIBUTES
$remember = function ($id, $code) use (&$created, $propVals) {
	if (!isset($created['props_before'][$id][$code])) {
		$created['props_before'][$id][$code] = $propVals($id, $code);
	}
};
foreach ($newByTpl as $t => $new) {
	$T = $tpls[$t];
	$group = array_values(array_unique(array_merge(array_map('intval', (array)$T['p']['ASSOCIATED']['VALUE']), [$t], array_values($new))));
	foreach ($group as $id) {
		if (!in_array($id, $new)) {
			$remember($id, 'ASSOCIATED');
		}
		CIBlockElement::SetPropertyValuesEx($id, IB, ['ASSOCIATED' => $group]);
	}
	$attr = $T['p']['CML2_ATTRIBUTES'];
	if ($attr['VALUE'] && ($ci = array_search('Цвет:', (array)$attr['DESCRIPTION'])) !== false) {
		$colors = array_map('trim', explode(',', $attr['VALUE'][$ci]));
		$colors = implode(', ', array_unique(array_merge($colors, array_keys($new))));
		foreach ($group as $id) {
			$cur = $propVals($id, 'CML2_ATTRIBUTES');
			if (!in_array($id, $new)) {
				$remember($id, 'CML2_ATTRIBUTES');
			}
			$vals = [];
			foreach ((array)$attr['VALUE'] as $k => $v) {
				$vals[] = ['VALUE' => $k === $ci ? $colors : $v, 'DESCRIPTION' => $attr['DESCRIPTION'][$k]];
			}
			CIBlockElement::SetPropertyValuesEx($id, IB, ['CML2_ATTRIBUTES' => $vals]);
		}
	}
}
// ---- «С этим товаром покупают»: позиции цвета образца → такие же нового цвета
$nameCache = [];
$byName = function ($name) use (&$nameCache, $norm) {
	$k = $norm($name);
	if (!array_key_exists($k, $nameCache)) {
		$r = CIBlockElement::GetList([], ['IBLOCK_ID' => IB, 'ACTIVE' => 'Y', '=NAME' => $name], false, ['nTopCount' => 1], ['ID'])->Fetch();
		$nameCache[$k] = $r ? (int)$r['ID'] : 0;
	}
	return $nameCache[$k];
};
foreach ($plan as $x) {
	$T = $tpls[$x['tpl']];
	$exp = [];
	foreach ((array)$T['p']['EXPANDABLES']['VALUE'] as $id) {
		$n = CIBlockElement::GetByID($id)->Fetch()['NAME'];
		if (mb_strpos($n, $x['tcolor']) === false) {
			$exp[] = (int)$id;
			continue;
		}
		$alt = $byName(str_replace($x['tcolor'], $x['color'], $n));
		if (!$alt && $x['color'] === 'Дуб беленый') {
			$alt = $byName(str_replace($x['tcolor'], 'Дуб белёный', $n));
		}
		if ($alt) {
			$exp[] = $alt;
		}
	}
	CIBlockElement::SetPropertyValuesEx($x['pid'], IB, ['EXPANDABLES' => $exp ?: false]);
	\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(IB, $x['pid']);
	echo "  {$x['pid']} покупают: " . implode(',', $exp) . "\n";
}
$save();
file_put_contents("$dir/plan_done.json", json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$clear();
echo 'готово: товаров ' . count($created['products']) . ', ТП ' . count($created['offers']) . "\n";
