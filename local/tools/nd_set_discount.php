<?php
/**
 * Скидка на комплект (19.10.2026): правило работы с корзиной магазина.
 * Если в корзине есть и пергола 34833, и комплект мебели 34834 — скидка 68 000 ₽
 * один раз на весь комплект (309 000 + 148 000 = 457 000 → 389 000).
 * Технически скидка ложится на мебель («сумма на все товары» — один раз,
 * сколько бы штук ни было), но корзина нового дизайна цену товаров не
 * перечёркивает: по XML_ID «nd_set_…» показывает полные цены, а скидку —
 * только в итогах с подписью «Ваша выгода при покупке комплекта»
 * (sale.basket.basket/templ/mutator.php).
 *
 *   php nd_set_discount.php <DOCROOT>            — показать, что будет
 *   php nd_set_discount.php <DOCROOT> --apply    — создать (или обновить) правило
 *   php nd_set_discount.php <DOCROOT> rollback   — удалить правило
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix')) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

\Bitrix\Main\Loader::includeModule('sale');
\Bitrix\Main\Loader::includeModule('catalog');

const ND_XML_ID = 'nd_set_pergola_34833_34834';
const ND_PERGOLA = 34833;
const ND_FURNITURE = 34834;
const ND_DISCOUNT = 68000;

$current = \Bitrix\Sale\Internals\DiscountTable::getList([
	'select' => ['ID', 'NAME', 'ACTIVE'],
	'filter' => ['=XML_ID' => ND_XML_ID],
])->fetch();

if (($argv[2] ?? '') === 'rollback') {
	if (!$current) {
		exit("правила нет\n");
	}
	CSaleDiscount::Delete((int)$current['ID']);
	exit("правило {$current['ID']} удалено\n");
}

foreach ([ND_PERGOLA, ND_FURNITURE] as $id) {
	$el = CIBlockElement::GetByID($id)->Fetch();
	$price = CPrice::GetBasePrice($id);
	if (!$el) {
		exit("товара $id нет\n");
	}
	echo "$id {$el['NAME']} — " . (float)$price['PRICE'] . " ₽\n";
}

$inBasket = function (int $id): array {
	return [
		'CLASS_ID' => 'CondBsktProductGroup',
		'DATA' => ['Found' => 'Found', 'All' => 'AND'],
		'CHILDREN' => [
			['CLASS_ID' => 'CondIBElement', 'DATA' => ['logic' => 'Equal', 'value' => [$id]]],
		],
	];
};

$groups = [];
$res = CGroup::GetList('id', 'asc', ['ACTIVE' => 'Y']);
while ($g = $res->Fetch()) {
	$groups[] = (int)$g['ID'];
}

$fields = [
	'LID' => 's1',
	'XML_ID' => ND_XML_ID,
	'NAME' => 'Комплект: пергола LATITUDO 3х3 + уличная мебель — скидка 68 000 ₽',
	'CURRENCY' => 'RUB',
	'ACTIVE' => 'Y',
	'SORT' => 100,
	'PRIORITY' => 1,
	'LAST_DISCOUNT' => 'N',
	'LAST_LEVEL_DISCOUNT' => 'N',
	'USER_GROUPS' => $groups,
	'CONDITIONS' => [
		'CLASS_ID' => 'CondGroup',
		'DATA' => ['All' => 'AND', 'True' => 'True'],
		'CHILDREN' => [$inBasket(ND_PERGOLA), $inBasket(ND_FURNITURE)],
	],
	'ACTIONS' => [
		'CLASS_ID' => 'CondGroup',
		'DATA' => ['All' => 'AND'],
		'CHILDREN' => [[
			'CLASS_ID' => 'ActSaleBsktGrp',
			'DATA' => ['Type' => 'Discount', 'Value' => ND_DISCOUNT, 'Unit' => 'CurAll', 'Max' => 0, 'All' => 'AND', 'True' => 'True'],
			'CHILDREN' => [
				['CLASS_ID' => 'CondIBElement', 'DATA' => ['logic' => 'Equal', 'value' => [ND_FURNITURE]]],
			],
		]],
	],
];

echo 'правило: ' . ($current ? "есть, ID {$current['ID']} — будет обновлено" : 'нет — будет создано') . "\n";
echo 'группы пользователей: ' . implode(',', $groups) . "\n";
if (!in_array('--apply', $argv, true)) {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

global $APPLICATION;
$id = $current
	? (CSaleDiscount::Update((int)$current['ID'], $fields) ? (int)$current['ID'] : 0)
	: (int)CSaleDiscount::Add($fields);
if (!$id) {
	$ex = $APPLICATION->GetException();
	exit('ошибка: ' . ($ex ? $ex->GetString() : '?') . "\n");
}
echo "готово, правило ID $id\n";
