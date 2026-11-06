<?php
/**
 * Метка «Акция» и плашка у цены товарам акции (Ирина, 06.11.2026: «на мебель Миларго
 * также поставь [ярлык акция], на элементы участвующие в акции, и допиши плашечку на
 * детальной: Возможна акционная цена. Подробности уточняйте у наших менеджеров»).
 *
 * Товары — «Элементы, участвующие в акции» (LINK_GOODS) элемента акции ИБ 17.
 * Метка — значение «Акция» (XML_ID AKCIYA) свойства HIT, как у ноябрьской акции
 * (nd_promo_nov2026_hit.php); прочие значения HIT не трогаем. Плашка — строковое
 * свойство ND_PRICE_NOTE «Плашка у цены» (создаётся, если его нет), выводит
 * catalog.element/main6_newdesign. Что поставили — в ~/nd_promo_price_note_<акция>.json
 * (вне www): rollback снимет только это.
 *
 *   php nd_promo_price_note.php <DOCROOT> <ID акции>            — показать, кому поставится
 *   php nd_promo_price_note.php <DOCROOT> <ID акции> --apply    — поставить
 *   php nd_promo_price_note.php <DOCROOT> <ID акции> rollback   — снять
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
$promoId = (int)($argv[2] ?? 0);
$mode = $argv[3] ?? '';
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix') || $promoId <= 0) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

\Bitrix\Main\Loader::includeModule('iblock');

const ND_CATALOG_IBLOCK = 19;
const ND_PROMO_IBLOCK = 17;
const ND_NOTE = 'Возможна акционная цена. Подробности уточняйте у наших менеджеров';

$log = (getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT'], 2)) . '/nd_promo_price_note_' . $promoId . '.json';

$enum = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => ND_CATALOG_IBLOCK, 'CODE' => 'HIT', 'XML_ID' => 'AKCIYA'])->Fetch();
if (!$enum) {
	exit("нет значения «Акция» у свойства HIT\n");
}
$akciya = (int)$enum['ID'];

$hitOf = function (int $id): array {
	$vals = [];
	$rs = CIBlockElement::GetProperty(ND_CATALOG_IBLOCK, $id, [], ['CODE' => 'HIT']);
	while ($v = $rs->Fetch()) {
		if ((int)$v['VALUE'] > 0) {
			$vals[] = (int)$v['VALUE'];
		}
	}
	return $vals;
};
$noteOf = function (int $id): string {
	$v = CIBlockElement::GetProperty(ND_CATALOG_IBLOCK, $id, [], ['CODE' => 'ND_PRICE_NOTE'])->Fetch();
	return trim((string)($v['VALUE'] ?? ''));
};
$saveHit = function (int $id, array $vals) {
	CIBlockElement::SetPropertyValuesEx($id, ND_CATALOG_IBLOCK, ['HIT' => $vals ?: false]);
	\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(ND_CATALOG_IBLOCK, $id);
};

if ($mode === 'rollback') {
	$done = is_file($log) ? (array)json_decode(file_get_contents($log), true) : [];
	if (!$done) {
		exit("снимать нечего ($log)\n");
	}
	foreach ((array)($done['hit'] ?? []) as $id) {
		$saveHit((int)$id, array_values(array_diff($hitOf((int)$id), [$akciya])));
	}
	foreach ((array)($done['note'] ?? []) as $id) {
		CIBlockElement::SetPropertyValuesEx((int)$id, ND_CATALOG_IBLOCK, ['ND_PRICE_NOTE' => false]);
	}
	CIBlock::clearIblockTagCache(ND_CATALOG_IBLOCK);
	unlink($log);
	exit('метка снята у ' . count((array)($done['hit'] ?? [])) . ', плашка у ' . count((array)($done['note'] ?? [])) . " товаров\n");
}

$promo = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_PROMO_IBLOCK, 'ID' => $promoId], false, false, ['ID', 'NAME', 'ACTIVE_TO'])->Fetch();
if (!$promo) {
	exit("нет акции $promoId\n");
}
$products = [];
$rs = CIBlockElement::GetProperty(ND_PROMO_IBLOCK, $promoId, [], ['CODE' => 'LINK_GOODS']);
while ($v = $rs->Fetch()) {
	if ((int)$v['VALUE'] > 0) {
		$products[] = (int)$v['VALUE'];
	}
}
echo "акция: {$promo['NAME']} (до {$promo['ACTIVE_TO']}), товаров: " . count($products) . "\n";

$hasProp = (bool)CIBlockProperty::GetList([], ['IBLOCK_ID' => ND_CATALOG_IBLOCK, 'CODE' => 'ND_PRICE_NOTE'])->Fetch();
$toHit = $toNote = [];
foreach ($products as $id) {
	if (!in_array($akciya, $hitOf($id), true)) {
		$toHit[] = $id;
	}
	if (!$hasProp || $noteOf($id) === '') {
		$toNote[] = $id;
	}
}
echo 'метка «Акция» добавится: ' . count($toHit) . ', плашка у цены: ' . count($toNote) .
	($hasProp ? '' : ' (свойство ND_PRICE_NOTE будет создано)') . "\n";
if ($mode !== '--apply') {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

if (!$hasProp) {
	$prop = new CIBlockProperty();
	if (!$prop->Add([
		'IBLOCK_ID' => ND_CATALOG_IBLOCK,
		'CODE' => 'ND_PRICE_NOTE',
		'NAME' => 'Плашка у цены',
		'PROPERTY_TYPE' => 'S',
		'ACTIVE' => 'Y',
		'SORT' => 5000,
		'HINT' => 'Текст плашки под ценой на странице товара (новый дизайн). Пусто — плашки нет.',
	])) {
		exit("не создалось свойство: {$prop->LAST_ERROR}\n");
	}
}

$done = is_file($log) ? (array)json_decode(file_get_contents($log), true) : [];
foreach ($toHit as $id) {
	$saveHit($id, array_merge($hitOf($id), [$akciya]));
	$done['hit'][] = $id;
}
foreach ($toNote as $id) {
	CIBlockElement::SetPropertyValuesEx($id, ND_CATALOG_IBLOCK, ['ND_PRICE_NOTE' => ND_NOTE]);
	$done['note'][] = $id;
}
$done['hit'] = array_values(array_unique((array)($done['hit'] ?? [])));
$done['note'] = array_values(array_unique((array)($done['note'] ?? [])));
file_put_contents($log, json_encode($done));
CIBlock::clearIblockTagCache(ND_CATALOG_IBLOCK);
echo "готово, список для отката: $log\n";
