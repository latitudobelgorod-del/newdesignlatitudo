<?php
/**
 * Документы производителя — привязка элементов инфоблока «Лицензии и сертификаты» (13)
 * к марке из «Производители и марки» (12) через свойство BRAND.
 *
 * Раньше блок «Документация» на странице марки собирали руками в редакторе EDITOR2
 * (блок iblock_elements со списком ID). Теперь страница марки выводит компонентом
 * все активные документы, у которых в BRAND стоит эта марка
 * (partners_newdesign/page_blocks/element_1.php → news.list brand-docs_newdesign).
 *
 *   php nd_brand_docs.php <DOCUMENT_ROOT> dry|run|rollback          — свойство и привязки
 *   php nd_brand_docs.php <DOCUMENT_ROOT> clean-dry|clean|clean-rollback — убрать старые
 *        блоки «Документация» из EDITOR2 марок (заголовок htag + iblock_elements ИБ 13)
 *
 * Бэкапы — рядом со скриптом в ~/nd_brand_docs/ (на проде вне www).
 * 10.11.2026.
 */
$root = $argv[1] ?? ''; $mode = $argv[2] ?? 'dry';
if (!$root || !is_dir($root)) die("usage: php nd_brand_docs.php <DOCUMENT_ROOT> <mode>\n");
$_SERVER['DOCUMENT_ROOT'] = $root;
define('NO_KEEP_STATISTIC', true); define('NOT_CHECK_PERMISSIONS', true); define('NO_AGENT_CHECK', true);
require $root.'/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$IB_DOCS = 13; $IB_BRANDS = 12; $CODE = 'BRAND';
$dir = (getenv('HOME') ?: sys_get_temp_dir()).'/nd_brand_docs'; @mkdir($dir);
$bakLinks = "$dir/links_before.json"; $bakEditor = "$dir/editor2_before.json";

/* Разделы поставщиков → марка. «Латитудо + Изидекинг» (360) не здесь: в нём две
   марки, его документы берутся из ручных списков страниц LATITUDO и EasyDecking. */
$SECTION_BRAND = [
	507 => 28619, // 4SiS
	429 => 28528, // Би-Файр
	509 => 33617, // Верста
	379 => 26657, // Сентай (Регалборд) → Regalboard
	570 => 9293,  // Atlas Concorde
	571 => 1625,  // Bruggan
	573 => 23698, // HILST
	574 => 23922, // Legro
	575 => 24134, // Level
	576 => 23703, // Nextwood
	577 => 26048, // POLIVAN GROUP
	581 => 23589, // TimberTex
	583 => 23345, // СМ Декинг
	584 => 27132, // Технодерево
	585 => 1581,  // Террапол
	586 => 24761, // Террасвет
];
/* Документы «Латитудо + Изидекинг», которых не было в ручных списках. */
$EXTRA = [34615 => [26365]]; // Гарантия EasyDecking Вуд-Икс Старт → EasyDecking

/** Блоки редактора EDITOR2 марки: [brandId => ['name'=>, 'json'=>, 'blocks'=>[...]]] */
function nd_brand_editors($IB_BRANDS) {
	$out = [];
	$r = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => $IB_BRANDS], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
	while ($ob = $r->GetNextElement()) {
		$f = $ob->GetFields(); $p = $ob->GetProperties([], ['CODE' => 'EDITOR2']);
		$v = $p['EDITOR2']['~VALUE'] ?? ''; if (is_array($v)) $v = $v['TEXT'] ?? '';
		if (!$v || !($j = json_decode($v, true))) continue;
		$out[$f['ID']] = ['name' => $f['~NAME'], 'json' => $v, 'data' => $j];
	}
	return $out;
}

if (in_array($mode, ['dry', 'run', 'rollback'])) {
	$prop = CIBlockProperty::GetList([], ['IBLOCK_ID' => $IB_DOCS, 'CODE' => $CODE])->Fetch();
	if ($mode === 'rollback') {
		$b = json_decode(file_get_contents($bakLinks), true);
		foreach ($b['values'] as $id => $vals) CIBlockElement::SetPropertyValuesEx($id, $IB_DOCS, [$CODE => $vals ?: false]);
		if (!empty($b['created_property']) && $prop) { CIBlockProperty::Delete($prop['ID']); echo "property deleted\n"; }
		CIBlock::clearIblockTagCache($IB_DOCS); echo "restored ", count($b['values']), "\n"; exit;
	}

	// 1. кто к какой марке
	$links = [];
	foreach (nd_brand_editors($IB_BRANDS) as $bid => $e)
		foreach ($e['data']['blocks'] ?? [] as $bl)
			if (($bl['name'] ?? '') === 'iblock_elements' && (int)($bl['iblock_id'] ?? 0) === $IB_DOCS)
				foreach ($bl['element_ids'] ?? [] as $id) $links[(int)$id][$bid] = $bid;
	$els = [];
	$r = CIBlockElement::GetList([], ['IBLOCK_ID' => $IB_DOCS], false, false, ['ID', 'NAME', 'ACTIVE', 'IBLOCK_SECTION_ID']);
	while ($a = $r->Fetch()) {
		$els[$a['ID']] = $a;
		$g = CIBlockElement::GetElementGroups($a['ID'], true, ['ID']);
		while ($s = $g->Fetch()) if (isset($SECTION_BRAND[$s['ID']])) $links[(int)$a['ID']][$SECTION_BRAND[$s['ID']]] = $SECTION_BRAND[$s['ID']];
	}
	foreach ($EXTRA as $id => $bids) foreach ($bids as $bid) if (isset($els[$id])) $links[$id][$bid] = $bid;
	foreach ($links as $id => $_) if (!isset($els[$id])) { echo "skip unknown element $id\n"; unset($links[$id]); }

	$names = []; $r = CIBlockElement::GetList([], ['IBLOCK_ID' => $IB_BRANDS], false, false, ['ID', 'NAME']); while ($a = $r->Fetch()) $names[$a['ID']] = $a['NAME'];
	$per = []; foreach ($links as $id => $bids) foreach ($bids as $bid) $per[$bid][] = $id;
	ksort($per); foreach ($per as $bid => $ids) echo str_pad($names[$bid] ?? $bid, 16), " ", count($ids), ": ", implode(',', $ids), "\n";
	echo "elements linked: ", count($links), " of ", count($els), "\n";
	if ($mode !== 'run') { echo "dry run\n"; exit; }

	// 2. свойство
	$created = false;
	if (!$prop) {
		$pid = (new CIBlockProperty)->Add([
			'IBLOCK_ID' => $IB_DOCS, 'NAME' => 'Привязка к производителю', 'CODE' => $CODE, 'ACTIVE' => 'Y', 'SORT' => 50,
			'PROPERTY_TYPE' => 'E', 'LINK_IBLOCK_ID' => $IB_BRANDS, 'MULTIPLE' => 'Y', 'FILTRABLE' => 'Y',
			'HINT' => 'На странице этого производителя документ появится в блоке «Документация»',
		]);
		if (!$pid) die("property add failed\n");
		$created = true; echo "property created $pid\n";
	}
	// 3. значения (с бэкапом прежних)
	if (!file_exists($bakLinks)) {
		$old = [];
		foreach ($els as $id => $_) { $v = []; $rp = CIBlockElement::GetProperty($IB_DOCS, $id, [], ['CODE' => $CODE]); while ($x = $rp->Fetch()) if ($x['VALUE']) $v[] = (int)$x['VALUE']; $old[$id] = $v; }
		file_put_contents($bakLinks, json_encode(['created_property' => $created, 'values' => $old]));
	}
	foreach ($links as $id => $bids) CIBlockElement::SetPropertyValuesEx($id, $IB_DOCS, [$CODE => array_values($bids)]);
	CIBlock::clearIblockTagCache($IB_DOCS);
	echo "done\n"; exit;
}

if (in_array($mode, ['clean-dry', 'clean', 'clean-rollback'])) {
	if ($mode === 'clean-rollback') {
		foreach (json_decode(file_get_contents($bakEditor), true) as $bid => $json) CIBlockElement::SetPropertyValuesEx($bid, $IB_BRANDS, ['EDITOR2' => $json]);
		CIBlock::clearIblockTagCache($IB_BRANDS); echo "restored\n"; exit;
	}
	$bak = file_exists($bakEditor) ? json_decode(file_get_contents($bakEditor), true) : [];
	foreach (nd_brand_editors($IB_BRANDS) as $bid => $e) {
		$blocks = $e['data']['blocks'] ?? []; $drop = [];
		foreach ($blocks as $i => $bl) {
			if (($bl['name'] ?? '') !== 'iblock_elements' || (int)($bl['iblock_id'] ?? 0) !== $IB_DOCS) continue;
			$drop[] = $i;
			$prev = $blocks[$i - 1] ?? null; // заголовок «Документация» прямо над списком
			if ($prev && ($prev['name'] ?? '') === 'htag' && mb_stripos(strip_tags($prev['value'] ?? ''), 'документ') !== false) $drop[] = $i - 1;
		}
		if (!$drop) continue;
		echo "$e[name]: remove blocks ", implode(',', $drop), " of ", count($blocks), "\n";
		if ($mode !== 'clean') continue;
		if (!isset($bak[$bid])) { $bak[$bid] = $e['json']; file_put_contents($bakEditor, json_encode($bak, JSON_UNESCAPED_UNICODE)); }
		$e['data']['blocks'] = array_values(array_diff_key($blocks, array_flip($drop)));
		CIBlockElement::SetPropertyValuesEx($bid, $IB_BRANDS, ['EDITOR2' => json_encode($e['data'], JSON_UNESCAPED_UNICODE)]);
	}
	if ($mode === 'clean') CIBlock::clearIblockTagCache($IB_BRANDS);
	echo $mode === 'clean' ? "done\n" : "dry run\n"; exit;
}
echo "unknown mode\n";
