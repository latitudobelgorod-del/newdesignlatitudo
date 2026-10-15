<?php
/* Сравнение товаров: добавить / убрать / очистить вкладку / список.
   Зовёт js/newdesign-compare.js шаблона и страница /compare/.
   Пока идёт скрытый тест, отвечает только группе «Тестирование сравнения»
   (ndCompareAllowed), остальным — 404, как будто адреса нет.

   POST: action=add|remove|clear|list, id (товар), key (вкладка для clear), sessid.
   Ответ: {ok, ids, count, groups:{ID:KEY}, tabs:{KEY:{TITLE,COUNT}}, last}. */

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/** @var CMain $APPLICATION */
global $APPLICATION;
$APPLICATION->RestartBuffer();
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$ndOut = static function (int $status, array $data) {
	if ($status !== 200) {
		CHTTP::SetStatus($status === 404 ? '404 Not Found' : '400 Bad Request');
	}
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
	die();
};

if (!function_exists('ndCompareAllowed') || !ndCompareAllowed()) {
	$ndOut(404, ['ok' => false]);
}

$action = (string)($_REQUEST['action'] ?? 'list');
if ($action !== 'list' && !check_bitrix_sessid()) {
	$ndOut(400, ['ok' => false, 'error' => 'sessid']);
}

$ids = ndCompareList();
$id = (int)($_REQUEST['id'] ?? 0);

if ($action === 'add' && $id > 0) {
	\Bitrix\Main\Loader::includeModule('iblock');
	$exists = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_COMPARE_IBLOCK, 'ID' => $id, 'ACTIVE' => 'Y'], []);
	if (!$exists) {
		$ndOut(400, ['ok' => false, 'error' => 'product']);
	}
	// повторное добавление поднимает товар в конец: его вкладка откроется первой
	$ids = array_values(array_diff($ids, [$id]));
	$ids[] = $id;
	ndCompareSave($ids);
} elseif ($action === 'remove' && $id > 0) {
	ndCompareSave(array_values(array_diff($ids, [$id])));
} elseif ($action === 'clear') {
	$key = (string)($_REQUEST['key'] ?? '');
	if ($key === '') {
		ndCompareSave([]);
	} else {
		$groups = ndCompareGroups($ids);
		ndCompareSave(array_values(array_filter($ids, static function ($i) use ($groups, $key) {
			return ($groups[$i]['KEY'] ?? '') !== $key;
		})));
	}
}

$ids = ndCompareList();
$groups = ndCompareGroups($ids);
$tabs = [];
$byId = [];
foreach ($ids as $i) {
	if (!isset($groups[$i])) {
		continue; // товар сняли с публикации
	}
	$key = $groups[$i]['KEY'];
	$byId[$i] = $key;
	$tabs[$key] = $tabs[$key] ?? ['TITLE' => $groups[$i]['TITLE'], 'COUNT' => 0];
	$tabs[$key]['COUNT']++;
}

$ndOut(200, [
	'ok' => true,
	'ids' => array_keys($byId),
	'count' => count($byId),
	'groups' => $byId,
	'tabs' => $tabs,
	'last' => ($action === 'add' && isset($byId[$id])) ? ['ID' => $id, 'KEY' => $byId[$id], 'TITLE' => $tabs[$byId[$id]]['TITLE'], 'COUNT' => $tabs[$byId[$id]]['COUNT']] : null,
]);
