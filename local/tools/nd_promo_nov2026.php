<?php
/**
 * Ноябрьская акция EasyDecking 2026 (05.11.2026): два правила работы с корзиной,
 * −15% и −20% от розничной цены, по образцу правил «Акция на … EasyDecking … −20%»
 * (ID 251–263: скидка на товары, условие — ID торговых предложений).
 *
 * Состав — сводная «Ноябрьская акция — товары на сайте.xlsx»: 57 позиций из 63
 * строк расчёта (Wood-X Start 146×21 на latitudo.ru нет и не заводим — Ирина).
 * Артикул на сайте = ID торгового предложения, его и берёт условие правила.
 *
 * Пока Ирина проверяет цены, правила видит только группа «Администраторы» (1):
 * покупателям скидки нет, администратор видит акционную цену в каталоге и корзине.
 *
 *   php nd_promo_nov2026.php <DOCROOT>             — показать цены, ничего не писать
 *   php nd_promo_nov2026.php <DOCROOT> --apply     — создать/обновить правила (только администраторы)
 *   php nd_promo_nov2026.php <DOCROOT> --public    — открыть правила всем группам
 *   php nd_promo_nov2026.php <DOCROOT> rollback    — удалить оба правила
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
\Bitrix\Main\Loader::includeModule('iblock');

const ND_ADMIN_GROUP = 1;

// процент => ID торговых предложений (= артикул на сайте)
$rules = require __DIR__ . '/nd_promo_nov2026_items.php';

$mode = $argv[2] ?? '';
$xmlId = function (int $percent): string {
	return 'nd_promo_nov2026_ed_' . $percent;
};
$current = [];
foreach (array_keys($rules) as $percent) {
	$current[$percent] = \Bitrix\Sale\Internals\DiscountTable::getList([
		'select' => ['ID', 'NAME', 'ACTIVE'],
		'filter' => ['=XML_ID' => $xmlId($percent)],
	])->fetch() ?: null;
}

if ($mode === 'rollback') {
	foreach ($current as $percent => $row) {
		if ($row) {
			CSaleDiscount::Delete((int)$row['ID']);
			echo "правило {$row['ID']} ({$percent}%) удалено\n";
		} else {
			echo "правила {$percent}% нет\n";
		}
	}
	exit;
}

$allGroups = [];
$res = CGroup::GetList('id', 'asc', ['ACTIVE' => 'Y']);
while ($g = $res->Fetch()) {
	$allGroups[] = (int)$g['ID'];
}
$groups = $mode === '--public' ? $allGroups : [ND_ADMIN_GROUP];

$errors = 0;
foreach ($rules as $percent => $ids) {
	echo "== −{$percent}%: " . count($ids) . " позиций\n";
	foreach ($ids as $id) {
		$el = CIBlockElement::GetList([], ['ID' => $id], false, false, ['ID', 'NAME', 'ACTIVE', 'IBLOCK_ID'])->Fetch();
		$price = CPrice::GetBasePrice($id);
		if (!$el || !$price) {
			echo "  !! $id — нет предложения или цены\n";
			$errors++;
			continue;
		}
		$base = (float)$price['PRICE'];
		echo sprintf("  %d %s%s — %s → %s ₽\n", $id, trim($el['NAME']), $el['ACTIVE'] === 'Y' ? '' : ' [ВЫКЛ]',
			$base, round($base * (100 - $percent) / 100, 2));
	}
}
if ($errors) {
	exit("есть ошибки ($errors) — правила не трогаю\n");
}

foreach ($rules as $percent => $ids) {
	$row = $current[$percent];
	echo "правило {$percent}%: " . ($row ? "есть, ID {$row['ID']} — будет обновлено" : 'нет — будет создано') . "\n";
}
echo 'группы пользователей: ' . implode(',', $groups) . ($mode === '--public' ? ' (все)' : ' (только администраторы)') . "\n";
if ($mode !== '--apply' && $mode !== '--public') {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

global $APPLICATION;
foreach ($rules as $percent => $ids) {
	$fields = [
		'LID' => 's1',
		'XML_ID' => $xmlId($percent),
		'NAME' => "Ноябрьская акция EasyDecking 2026 / {$percent}%",
		'CURRENCY' => 'RUB',
		'ACTIVE' => 'Y',
		'SORT' => 100,
		'PRIORITY' => 1,
		'LAST_DISCOUNT' => 'N',
		'LAST_LEVEL_DISCOUNT' => 'N',
		'USER_GROUPS' => $groups,
		'CONDITIONS' => ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND', 'True' => 'True'], 'CHILDREN' => []],
		'ACTIONS' => [
			'CLASS_ID' => 'CondGroup',
			'DATA' => ['All' => 'AND'],
			'CHILDREN' => [[
				'CLASS_ID' => 'ActSaleBsktGrp',
				'DATA' => ['Type' => 'Discount', 'Value' => $percent, 'Unit' => 'Perc', 'Max' => 0, 'All' => 'OR', 'True' => 'True'],
				'CHILDREN' => [
					['CLASS_ID' => 'CondIBElement', 'DATA' => ['logic' => 'Equal', 'value' => $ids]],
				],
			]],
		],
	];
	$row = $current[$percent];
	$id = $row
		? (CSaleDiscount::Update((int)$row['ID'], $fields) ? (int)$row['ID'] : 0)
		: (int)CSaleDiscount::Add($fields);
	if (!$id) {
		$ex = $APPLICATION->GetException();
		exit("ошибка ({$percent}%): " . ($ex ? $ex->GetString() : '?') . "\n");
	}
	echo "готово: «{$fields['NAME']}», правило ID $id\n";
}
