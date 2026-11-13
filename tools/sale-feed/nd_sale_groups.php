<?php
/**
 * Группы «Распродажи» (13.11.2026): свойство ND_SALE_GROUP в ИБ 19 и разметка товаров раздела по названию.
 *
 *   php nd_sale_groups.php <DOCROOT>                      – сухой прогон: какой товар в какую группу
 *   php nd_sale_groups.php <DOCROOT> apply                – создать свойство (если нет) и проставить группы
 *                                                           товарам раздела, у кого группы ещё нет
 *   php nd_sale_groups.php <DOCROOT> rollback=<done.json> – снять поставленные группы (свойство остаётся)
 *
 * Правила – sale_groups.inc.php. Свойство не в умном фильтре и не в карточке: только для кнопок раздела.
 */
$args = array_slice($argv, 1);
$root = array_shift($args);
$mode = 'dry';
$rollbackFile = '';
foreach ($args as $a) {
	if ($a === 'apply') {
		$mode = 'apply';
	} elseif (strpos($a, 'rollback=') === 0) {
		$mode = 'rollback';
		$rollbackFile = substr($a, 9);
	} else {
		fwrite(STDERR, "Неизвестный параметр: $a\n");
		exit(1);
	}
}
if (!$root || !is_dir($root)) {
	fwrite(STDERR, "Укажите корень сайта первым параметром\n");
	exit(1);
}
$_SERVER['DOCUMENT_ROOT'] = realpath($root);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/sale_groups.inc.php';
\Bitrix\Main\Loader::includeModule('iblock');

const ND_IBLOCK_ID = 19;
const ND_SECTION_CODE = 'rasprodazha';
$outDir = (getenv('HOME') ?: __DIR__) . '/sale-feed/' . date('Y-m-d_His') . '_groups_' . $mode;
mkdir($outDir, 0700, true);

if ($mode === 'rollback') {
	$done = json_decode((string)file_get_contents($rollbackFile), true);
	foreach ((array)$done as $id) {
		CIBlockElement::SetPropertyValuesEx((int)$id, ND_IBLOCK_ID, [ND_SALE_GROUP_PROP => false]);
	}
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	echo 'Группы сняты у товаров: ' . count((array)$done) . "\n";
	exit(0);
}

// свойство
$prop = CIBlockProperty::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID, 'CODE' => ND_SALE_GROUP_PROP])->Fetch();
if (!$prop) {
	echo "Свойства " . ND_SALE_GROUP_PROP . " нет – " . ($mode === 'apply' ? 'создаю' : 'будет создано') . "\n";
	if ($mode === 'apply') {
		$values = [];
		$sort = 100;
		foreach (ND_SALE_GROUPS as $code => $g) {
			$values[] = ['VALUE' => $g[0], 'XML_ID' => $code, 'SORT' => $sort, 'DEF' => 'N'];
			$sort += 100;
		}
		$p = new CIBlockProperty();
		$pid = $p->Add(['IBLOCK_ID' => ND_IBLOCK_ID, 'NAME' => 'Группа распродажи', 'CODE' => ND_SALE_GROUP_PROP,
			'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'L', 'MULTIPLE' => 'N', 'ACTIVE' => 'Y', 'SORT' => 9000,
			'SMART_FILTER' => 'N', 'SEARCHABLE' => 'N', 'FILTRABLE' => 'Y', 'VALUES' => $values,
			'HINT' => 'Кнопка над списком /catalog/rasprodazha/. Новым товарам ставит сверка с фидом по названию; поставленную руками не меняет.']);
		if (!$pid) {
			fwrite(STDERR, 'Не создано: ' . $p->LAST_ERROR . "\n");
			exit(1);
		}
		file_put_contents($outDir . '/property.json', json_encode(['id' => $pid]));
		echo "Создано свойство ID $pid\n";
	}
}
$enums = ndSaleGroupEnums(ND_IBLOCK_ID);

$section = CIBlockSection::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID, '=CODE' => ND_SECTION_CODE], false, ['ID'])->Fetch();
$rs = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => ND_IBLOCK_ID, 'SECTION_ID' => (int)$section['ID']], false, false,
	['ID', 'NAME', 'ACTIVE', 'PROPERTY_' . ND_SALE_GROUP_PROP]);
$done = [];
$count = [];
while ($r = $rs->Fetch()) {
	if (!empty($r['PROPERTY_' . ND_SALE_GROUP_PROP . '_ENUM_ID'])) {
		continue; // уже стоит (в том числе руками)
	}
	$code = ndSaleGroupByName($r['NAME']);
	$count[$code] = ($count[$code] ?? 0) + ($r['ACTIVE'] === 'Y' ? 1 : 0);
	echo str_pad($code, 18) . ($r['ACTIVE'] === 'Y' ? '  ' : '− ') . "{$r['ID']} {$r['NAME']}\n";
	if ($mode === 'apply' && isset($enums[$code])) {
		CIBlockElement::SetPropertyValuesEx((int)$r['ID'], ND_IBLOCK_ID, [ND_SALE_GROUP_PROP => $enums[$code]]);
		$done[] = (int)$r['ID'];
	}
}
echo "\nАктивных по группам:\n";
foreach (ND_SALE_GROUPS as $code => $g) {
	echo '  ' . $g[0] . ': ' . ($count[$code] ?? 0) . "\n";
}
if ($mode === 'apply') {
	file_put_contents($outDir . '/done.json', json_encode($done));
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	echo 'Проставлено: ' . count($done) . "\nОткат: php " . __FILE__ . " $root rollback=$outDir/done.json\n";
} else {
	echo "Сухой прогон – ничего не изменено (− выключенный товар). Поставить: добавьте apply\n";
}
