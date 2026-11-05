<?php
/**
 * Ноябрьская акция EasyDecking 2026 — метка «Акция» у товаров (Ирина, 05.11.2026:
 * «поставь в списке метку акция и чтобы можно было по фильтру отобрать»).
 *
 * Метка — значение «Акция» (XML_ID AKCIYA) свойства «Наши предложения» (HIT)
 * товаров каталога: она рисуется плашкой в карточке и стоит в умном фильтре.
 * Остальные значения HIT у товара не трогаем. Кому метку добавили, записываем
 * в ~/nd_promo_nov2026_hit.json (вне www) — rollback снимет её только с них,
 * у кого «Акция» стояла раньше, она останется.
 *
 *   php nd_promo_nov2026_hit.php <DOCROOT>            — показать, кому добавится
 *   php nd_promo_nov2026_hit.php <DOCROOT> --apply    — добавить
 *   php nd_promo_nov2026_hit.php <DOCROOT> rollback   — снять (после 30.11.2026)
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix')) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

\Bitrix\Main\Loader::includeModule('iblock');

const ND_CATALOG_IBLOCK = 19;
const ND_PROP = 'HIT';
const ND_XML = 'AKCIYA';

$mode = $argv[2] ?? '';
$log = (getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT'], 2)) . '/nd_promo_nov2026_hit.json';

$enum = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => ND_CATALOG_IBLOCK, 'CODE' => ND_PROP, 'XML_ID' => ND_XML])->Fetch();
if (!$enum) {
	exit("нет значения «Акция» у свойства HIT\n");
}
$akciya = (int)$enum['ID'];

// текущие значения HIT товара — списком ID значений
$hitOf = function (int $id): array {
	$vals = [];
	$rs = CIBlockElement::GetProperty(ND_CATALOG_IBLOCK, $id, [], ['CODE' => ND_PROP]);
	while ($v = $rs->Fetch()) {
		if ((int)$v['VALUE'] > 0) {
			$vals[] = (int)$v['VALUE'];
		}
	}
	return $vals;
};
$save = function (int $id, array $vals) {
	CIBlockElement::SetPropertyValuesEx($id, ND_CATALOG_IBLOCK, [ND_PROP => $vals ?: false]);
	\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(ND_CATALOG_IBLOCK, $id);
};

if ($mode === 'rollback') {
	$added = is_file($log) ? (array)json_decode(file_get_contents($log), true) : [];
	if (!$added) {
		exit("снимать нечего ($log)\n");
	}
	foreach ($added as $id) {
		$save((int)$id, array_values(array_diff($hitOf((int)$id), [$akciya])));
	}
	CIBlock::clearIblockTagCache(ND_CATALOG_IBLOCK);
	unlink($log);
	exit('метка снята у ' . count($added) . " товаров\n");
}

// товары = родители торговых предложений из состава акции
$offers = [];
foreach (require __DIR__ . '/nd_promo_nov2026_items.php' as $ids) {
	$offers = array_merge($offers, $ids);
}
$products = [];
$rs = CIBlockElement::GetList([], ['ID' => $offers], false, false, ['ID', 'PROPERTY_CML2_LINK']);
while ($o = $rs->Fetch()) {
	$products[(int)$o['PROPERTY_CML2_LINK_VALUE']] = true;
}

$toAdd = [];
foreach (array_keys($products) as $id) {
	if (!in_array($akciya, $hitOf($id), true)) {
		$toAdd[] = $id;
	}
}
echo 'товаров в акции: ' . count($products) . ', метка уже есть: ' . (count($products) - count($toAdd)) . ', добавится: ' . count($toAdd) . "\n";
if ($mode !== '--apply') {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

$added = is_file($log) ? (array)json_decode(file_get_contents($log), true) : [];
foreach ($toAdd as $id) {
	$save($id, array_merge($hitOf($id), [$akciya]));
	$added[] = $id;
}
file_put_contents($log, json_encode(array_values(array_unique($added))));
CIBlock::clearIblockTagCache(ND_CATALOG_IBLOCK);
echo "готово, список для отката: $log\n";
