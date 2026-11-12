<?php
/**
 * Инструкция по монтажу ограждений Террапол Практик и Смарт 3D (Ирина, 12.11.2026, с terrapol.ru/company/docs/).
 * Заводит документ в ИБ 13 и ставит его вместо инструкции Классика (25235) в INSTRUCTIONS_FILE
 * у товаров разделов «Практик» (320) и «Смарт 3D» (590).
 * * Плюс новые версии файлов у документов: инструкция по террасной доске (25310), схема заглушки ступени (34830),
 * рекомендации по заборам (34831, PDF вместо docx) –
 * привязки к товарам не меняются. Прежние значения и ID старых файлов – в <папка>/instr_before.json.
 *   php nd_terrapol_instr.php <DOCROOT> <папка с pdf> [--apply | rollback]
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
$dir = rtrim($argv[2] ?? '', '/');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');
const PDF = 'instruktsiya-po-montazhu-ograzhdeniy-terrapol-praktik-smart-3d.pdf';
const NAME = 'Инструкция по монтажу ограждений Террапол Практик и Смарт 3D';
const OLD = 25235;
// документ => новый файл (старый файл не удаляется, его ID в журнале для отката)
const REPLACE = [25310 => 'instruktsiya-po-montazhu-terrasnoy-doski-terrapol.pdf', 34830 => 'skhema-krepleniya-tortsevoy-zaglushki-stupeni-terrapol.pdf', 34831 => 'rekomendatsii-po-stroitelstvu-zaborov-terrapol.pdf'];
$mode = $argv[3] ?? '';
$log = "$dir/instr_before.json";

if ($mode === 'rollback') {
	$b = json_decode(file_get_contents($log), true);
	foreach ($b['products'] as $id => $vals) {
		CIBlockElement::SetPropertyValuesEx((int)$id, 19, ['INSTRUCTIONS_FILE' => $vals]);
	}
	CIBlockElement::Delete($b['doc']);
	foreach ($b['files'] ?? [] as $id => $fid) {
		CIBlockElement::SetPropertyValuesEx((int)$id, 13, ['INSTRUCTIONS' => CFile::MakeFileArray($fid)]);   // путь к копии в old/
	}
	CIBlock::clearIblockTagCache(19);
	CIBlock::clearIblockTagCache(13);
	unlink($log);
	exit('возвращено товаров ' . count($b['products']) . ", документ {$b['doc']} удалён\n");
}
$vals = function ($id) {
	$v = [];
	$r = CIBlockElement::GetProperty(19, $id, ['sort' => 'asc'], ['CODE' => 'INSTRUCTIONS_FILE']);
	while ($x = $r->Fetch()) {
		if ($x['VALUE']) {
			$v[] = (int)$x['VALUE'];
		}
	}
	return $v;
};
$todo = [];
$r = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => 19, 'SECTION_ID' => [320, 590]], false, false, ['ID', 'NAME', 'ACTIVE']);
while ($e = $r->Fetch()) {
	$v = $vals($e['ID']);
	echo sprintf("%6d %s %-75s %s%s\n", $e['ID'], $e['ACTIVE'], mb_substr($e['NAME'], 0, 75), implode(',', $v), in_array(OLD, $v) ? '  → заменить' : '  (Классика нет – не трогаю)');
	if (in_array(OLD, $v)) {
		$todo[$e['ID']] = $v;
	}
}
echo 'к замене: ' . count($todo) . "\n";
if ($mode !== '--apply' && $mode !== '--files') {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}
if (is_file($log)) {
	exit("уже выполнено – сначала rollback\n");
}
$tpl = CIBlockElement::GetList([], ['ID' => OLD], false, false, ['ID', 'IBLOCK_SECTION_ID', 'SORT'])->Fetch();
$en = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => 13, 'CODE' => 'MONTAJ_INSTR', 'XML_ID' => 'Y'])->Fetch();
$el = new CIBlockElement();
$doc = (int)$el->Add([
	'IBLOCK_ID' => 13, 'IBLOCK_SECTION_ID' => $tpl['IBLOCK_SECTION_ID'], 'ACTIVE' => 'Y', 'NAME' => NAME, 'SORT' => $tpl['SORT'],
	'CODE' => CUtil::translit(NAME, 'ru', ['replace_space' => '-', 'replace_other' => '-']),
	'PROPERTY_VALUES' => ['INSTRUCTIONS' => CFile::MakeFileArray("$dir/" . PDF), 'BRAND' => 1581, 'MONTAJ_INSTR' => $en ? $en['ID'] : false],
]);
if (!$doc) {
	exit('документ: ' . $el->LAST_ERROR . "\n");
}
files:
$files = [];
@mkdir("$dir/old");
foreach (REPLACE as $id => $f) {
	$old = CIBlockElement::GetProperty(13, $id, [], ['CODE' => 'INSTRUCTIONS'])->Fetch();
	// Битрикс удалит старый файл при замене – копия для отката в <папка>/old/
	$fa = CFile::GetFileArray($old['VALUE']);
	copy($_SERVER['DOCUMENT_ROOT'] . $fa['SRC'], "$dir/old/{$id}_" . $fa['ORIGINAL_NAME']);
	$files[$id] = "$dir/old/{$id}_" . $fa['ORIGINAL_NAME'];
	CIBlockElement::SetPropertyValuesEx($id, 13, ['INSTRUCTIONS' => CFile::MakeFileArray("$dir/$f")]);
	echo "документ $id: файл {$old['VALUE']} → " . CIBlockElement::GetProperty(13, $id, [], ['CODE' => 'INSTRUCTIONS'])->Fetch()['VALUE'] . "
";
}
file_put_contents($log, json_encode(['doc' => $doc, 'products' => $todo, 'files' => $files], JSON_UNESCAPED_UNICODE));
foreach ($todo as $id => $v) {
	$new = array_map(function ($x) use ($doc) {
		return $x === OLD ? $doc : $x;
	}, $v);
	CIBlockElement::SetPropertyValuesEx($id, 19, ['INSTRUCTIONS_FILE' => $new]);
}
CIBlock::clearIblockTagCache(19);
CIBlock::clearIblockTagCache(13);
echo "документ $doc, заменено в " . count($todo) . " товарах\n";
