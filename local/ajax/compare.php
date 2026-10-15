<?php
/* Сравнение товаров: добавить / убрать / очистить вкладку / список.
   Зовёт js/newdesign-compare.js шаблона и страница /compare/.
   Пока идёт скрытый тест, отвечает только группе «Тестирование сравнения»
   (ndCompareAllowed), остальным — 404, как будто адреса нет.

   POST: action=add|remove|clear|list, id (товар), offer (выбранная длина —
   торговое предложение), key (вкладка для clear), sessid.
   Ответ: {ok, ids, count, groups:{ID:KEY}, offers:{ID:OFFER}, tabs:{KEY:{TITLE,COUNT}}, last}. */

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

$entries = ndCompareEntries();
$id = (int)($_REQUEST['id'] ?? 0);
$offer = (int)($_REQUEST['offer'] ?? 0);

if ($action === 'add' && $id > 0) {
	\Bitrix\Main\Loader::includeModule('iblock');
	\Bitrix\Main\Loader::includeModule('catalog');
	$exists = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_COMPARE_IBLOCK, 'ID' => $id, 'ACTIVE' => 'Y'], []);
	if (!$exists) {
		$ndOut(400, ['ok' => false, 'error' => 'product']);
	}
	// выбранная длина — только предложение этого товара, иначе без неё
	if ($offer > 0 && $offer !== $id) {
		$parent = CCatalogSku::GetProductInfo($offer);
		if (!$parent || (int)$parent['ID'] !== $id) {
			$offer = 0;
		}
	} else {
		$offer = 0;
	}
	// одна доска — одна колонка: другая длина заменяет прежнюю и поднимает в конец
	unset($entries[$id]);
	$entries[$id] = $offer;
	ndCompareSave($entries);
} elseif ($action === 'remove' && $id > 0) {
	unset($entries[$id]);
	ndCompareSave($entries);
} elseif ($action === 'clear') {
	$key = (string)($_REQUEST['key'] ?? '');
	if ($key === '') {
		ndCompareSave([]);
	} else {
		$groups = ndCompareGroups(array_keys($entries));
		foreach (array_keys($entries) as $pid) {
			if (($groups[$pid]['KEY'] ?? '') === $key) {
				unset($entries[$pid]);
			}
		}
		ndCompareSave($entries);
	}
}

$entries = ndCompareEntries();
$ids = array_keys($entries);
$groups = ndCompareGroups($ids);
$tabs = [];
$byId = [];
$offers = [];
foreach ($ids as $i) {
	if (!isset($groups[$i])) {
		continue; // товар сняли с публикации
	}
	$key = $groups[$i]['KEY'];
	$byId[$i] = $key;
	$offers[$i] = $entries[$i];
	$tabs[$key] = $tabs[$key] ?? ['TITLE' => $groups[$i]['TITLE'], 'COUNT' => 0];
	$tabs[$key]['COUNT']++;
}

$ndOut(200, [
	'ok' => true,
	'ids' => array_keys($byId),
	'count' => count($byId),
	'groups' => $byId,
	// выбранная длина (торговое предложение) у каждого товара, 0 — без неё
	'offers' => (object)$offers,
	'tabs' => $tabs,
	'last' => ($action === 'add' && isset($byId[$id])) ? ['ID' => $id, 'KEY' => $byId[$id], 'TITLE' => $tabs[$byId[$id]]['TITLE'], 'COUNT' => $tabs[$byId[$id]]['COUNT']] : null,
]);
