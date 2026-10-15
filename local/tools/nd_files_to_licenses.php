<?php
/**
 * Файлы товаров → инфоблок «Лицензии и сертификаты» (Ирина, 15.10.2026).
 *
 * У части товаров каталога (ИБ 19) файл загружен прямо в свойство «Файлы»
 * (INSTRUCTIONS, тип «файл»). Должно быть как у остальных: документ — элемент
 * ИБ 13 «Лицензии и сертификаты» (файл в его свойстве «Файл» INSTRUCTIONS),
 * а товар ссылается на него через «Файлы / лицензии / сертификаты»
 * (INSTRUCTIONS_FILE). Для каждого разного файла (по содержимому) — один
 * элемент ИБ 13 в разделе «Инструкции по монтажу» (355), имя — описание файла;
 * к ссылкам товара он добавляется (прежние остаются); «Файлы» товара очищаются.
 * Только активные товары. Галку «Показывать на странице Монтажные схемы и
 * инструкции» не ставим — это решение отдельно.
 *
 *   php nd_files_to_licenses.php <DOCROOT> [--apply]
 *   php nd_files_to_licenses.php <DOCROOT> rollback <файл бэкапа>
 * Без --apply — только показывает. Бэкап — в домашнюю папку (вне www).
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

const ND_CATALOG = 19;
const ND_LICENSES = 13;
const ND_SECTION = 355; // «Инструкции по монтажу»
$home = getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT']);

function ndLinks(int $id): array
{
	$v = [];
	$r = CIBlockElement::GetProperty(ND_CATALOG, $id, [], ['CODE' => 'INSTRUCTIONS_FILE']);
	while ($p = $r->Fetch()) {
		if ($p['VALUE']) {
			$v[] = (int)$p['VALUE'];
		}
	}
	return $v;
}

if (($argv[2] ?? '') === 'rollback') {
	$bk = json_decode(file_get_contents($argv[3]), true);
	foreach ($bk['products'] as $id => $row) {
		// файл возвращаем копией из созданного элемента ИБ 13 — исходный
		// Битрикс удалил вместе со значением свойства
		$files = [];
		foreach ($row['files'] as $f) {
			$arr = CFile::MakeFileArray((int)$f['copy_file_id']);
			if ($arr) {
				$files[] = ['VALUE' => $arr, 'DESCRIPTION' => $f['description']];
			}
		}
		CIBlockElement::SetPropertyValuesEx((int)$id, ND_CATALOG, [
			'INSTRUCTIONS' => $files ?: false,
			'INSTRUCTIONS_FILE' => $row['links'] ?: false,
		]);
	}
	foreach ($bk['created'] as $eid) {
		CIBlockElement::Delete((int)$eid);
	}
	CIBlock::clearIblockTagCache(ND_CATALOG);
	CIBlock::clearIblockTagCache(ND_LICENSES);
	exit('откат: товаров ' . count($bk['products']) . ', удалено элементов ИБ 13: ' . count($bk['created']) . "\n");
}

$apply = in_array('--apply', $argv, true);

// товары с заполненными «Файлами»
$groups = []; // md5 => [name, file_id, products[]]
$products = [];
$rs = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => ND_CATALOG, 'ACTIVE' => 'Y', '!PROPERTY_INSTRUCTIONS' => false], false, false, ['ID', 'NAME']);
while ($e = $rs->Fetch()) {
	$id = (int)$e['ID'];
	if (isset($products[$id])) {
		continue;
	}
	$products[$id] = ['name' => $e['NAME'], 'files' => [], 'links' => ndLinks($id)];
	$r = CIBlockElement::GetProperty(ND_CATALOG, $id, [], ['CODE' => 'INSTRUCTIONS']);
	while ($p = $r->Fetch()) {
		if (!$p['VALUE']) {
			continue;
		}
		$file = CFile::GetFileArray((int)$p['VALUE']);
		$path = $file ? $_SERVER['DOCUMENT_ROOT'] . $file['SRC'] : '';
		if (!$file || !is_file($path)) {
			echo "  $id: файл {$p['VALUE']} не найден на диске — пропуск\n";
			continue;
		}
		$md5 = md5_file($path);
		$name = trim((string)$p['DESCRIPTION']) ?: pathinfo($file['ORIGINAL_NAME'], PATHINFO_FILENAME);
		$groups[$md5] = $groups[$md5] ?? ['name' => $name, 'file_id' => (int)$p['VALUE'], 'original' => $file['ORIGINAL_NAME'], 'products' => []];
		$groups[$md5]['products'][] = $id;
		$products[$id]['files'][] = ['file_id' => (int)$p['VALUE'], 'value_id' => (int)$p['PROPERTY_VALUE_ID'], 'description' => (string)$p['DESCRIPTION'], 'md5' => $md5];
	}
}

foreach ($groups as $g) {
	echo "«{$g['name']}» ({$g['original']}) → товары " . implode(', ', $g['products']) . "\n";
}
echo 'товаров: ' . count($products) . ', новых элементов ИБ 13: ' . count($groups) . "\n";
if (!$apply || !$groups) {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

$backup = ['products' => [], 'created' => []];
$bkFile = "$home/nd_files_to_licenses_backup_" . date('Ymd-His') . '.json';
$el = new CIBlockElement;
$newId = [];
foreach ($groups as $md5 => $g) {
	$eid = $el->Add([
		'IBLOCK_ID' => ND_LICENSES,
		'IBLOCK_SECTION_ID' => ND_SECTION,
		'NAME' => $g['name'],
		// символьный код обязателен — транслит названия, как в админке
		'CODE' => CUtil::translit($g['name'], 'ru', ['max_len' => 100, 'replace_space' => '-', 'replace_other' => '-']),
		'ACTIVE' => 'Y',
		'SORT' => 500,
		'PROPERTY_VALUES' => [
			// копия файла: исходный Битрикс удалит, когда очистим свойство товара
			'INSTRUCTIONS' => [['VALUE' => CFile::MakeFileArray($g['file_id']), 'DESCRIPTION' => '']],
		],
	]);
	if (!$eid) {
		exit("не создался элемент «{$g['name']}»: {$el->LAST_ERROR}\n");
	}
	$newId[$md5] = (int)$eid;
	$backup['created'][] = (int)$eid;
	echo "создан элемент ИБ 13: $eid «{$g['name']}»\n";
}

// id копии файла — для отката
$copyFile = [];
foreach ($newId as $md5 => $eid) {
	$p = CIBlockElement::GetProperty(ND_LICENSES, $eid, [], ['CODE' => 'INSTRUCTIONS'])->Fetch();
	$copyFile[$md5] = (int)$p['VALUE'];
}

foreach ($products as $id => $row) {
	$backup['products'][$id] = [
		'links' => $row['links'],
		'files' => array_map(static function ($f) use ($copyFile) {
			return ['description' => $f['description'], 'copy_file_id' => $copyFile[$f['md5']]];
		}, $row['files']),
	];
}
file_put_contents($bkFile, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "бэкап: $bkFile\n";

foreach ($products as $id => $row) {
	$links = $row['links'];
	foreach ($row['files'] as $f) {
		$links[] = $newId[$f['md5']];
	}
	// множественное свойство-файл очищается только так: по ID значения с del=Y
	$del = [];
	foreach ($row['files'] as $f) {
		$del[$f['value_id']] = ['VALUE' => ['MODULE_ID' => 'iblock', 'del' => 'Y']];
	}
	CIBlockElement::SetPropertyValuesEx($id, ND_CATALOG, [
		'INSTRUCTIONS_FILE' => array_values(array_unique($links)),
		'INSTRUCTIONS' => $del,
	]);
	// контроль: «Файлы» пусты
	$left = CIBlockElement::GetProperty(ND_CATALOG, $id, [], ['CODE' => 'INSTRUCTIONS'])->Fetch();
	echo "$id: ссылки " . implode(',', ndLinks($id)) . ', «Файлы» ' . (!empty($left['VALUE']) ? 'НЕ ОЧИЩЕНЫ' : 'очищены') . "\n";
}
// SetPropertyValuesEx кеш инфоблока не сбрасывает
CIBlock::clearIblockTagCache(ND_CATALOG);
CIBlock::clearIblockTagCache(ND_LICENSES);
echo "готово\n";
