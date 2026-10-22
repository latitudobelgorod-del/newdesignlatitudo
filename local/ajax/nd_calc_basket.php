<?php
/* Калькуляторы террасы и ограждений (/info/kalkulyator-terrasy/, /info/kalkulyator-ograzhdeniy/):
   кнопка «Добавить всё в корзину» (Ирина, 22.10.2026).

   Браузер присылает строки сметы: ID товара (калькулятор ограждений — ID есть в его снимке каталога)
   или артикул (калькулятор террасы; у товара — свойство CML2_ARTICLE, у предложения — ARTICLE),
   а на случай пустого артикула — точное название товара, и количество.
   Цену берёт корзина из каталога, как при обычном «В корзину». Кладём только активные товары
   каталога (ИБ 19) и торговые предложения (ИБ 20), не «родителей» с предложениями.
   Товар, который уже лежит в корзине, — количество прибавляется.

   Название ищем без крайних пробелов: у части предложений в каталоге имя начинается или кончается
   пробелом (« Террасная доска Террапол Антик 147х23х4000 Аргос»), в снимке калькулятора — без них.

   POST: sessid, items (JSON [{id?, art?, name?, qty}]). Ответ — JSON {ok, added, failed[названия], error?}. */

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$APPLICATION->RestartBuffer();
header('Content-Type: application/json; charset=utf-8');

$ndCbAnswer = function ($ok, $extra = array()) {
	echo json_encode(array_merge(array('ok' => (bool)$ok), $extra), JSON_UNESCAPED_UNICODE);
	die();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid()) {
	$ndCbAnswer(false, array('error' => 'Обновите страницу и попробуйте ещё раз.'));
}
if (!\Bitrix\Main\Loader::includeModule('iblock') || !\Bitrix\Main\Loader::includeModule('catalog') || !\Bitrix\Main\Loader::includeModule('sale')) {
	$ndCbAnswer(false, array('error' => 'Корзина сейчас недоступна.'));
}

$ndCbFilter = array('IBLOCK_ID' => array(19, 20), 'ACTIVE' => 'Y');
$ndCbSelect = array('ID', 'NAME', 'CATALOG_TYPE');

// смета: не больше 30 строк, количество — целое от 1 до 10 000
$ndCbQty = array();
$ndCbFailed = array();
foreach (array_slice((array)json_decode((string)($_POST['items'] ?? ''), true), 0, 30) as $row) {
	if (!is_array($row)) continue;
	$qty = (int)($row['qty'] ?? 0);
	$name = trim(preg_replace('/\s+/u', ' ', (string)($row['name'] ?? '')));
	$art = trim((string)($row['art'] ?? ''));
	if ($qty < 1 || $qty > 10000) continue;

	$id = 0;
	if ((int)($row['id'] ?? 0) > 0) {
		if ($el = CIBlockElement::GetList(array(), array_merge($ndCbFilter, array('ID' => (int)$row['id'])), false, false, $ndCbSelect)->Fetch()) {
			$id = $el['CATALOG_TYPE'] == 3 ? 0 : (int)$el['ID'];
		}
	}
	if (!$id && $art !== '' && mb_strlen($art) <= 100) {
		foreach (array(19 => 'PROPERTY_CML2_ARTICLE', 20 => 'PROPERTY_ARTICLE') as $ib => $prop) {
			$res = CIBlockElement::GetList(array('ID' => 'ASC'), array('IBLOCK_ID' => $ib, 'ACTIVE' => 'Y', '='.$prop => $art), false, array('nTopCount' => 5), $ndCbSelect);
			while ($el = $res->Fetch()) {
				if ($el['CATALOG_TYPE'] != 3) {
					$id = (int)$el['ID'];
					break 2;
				}
			}
		}
	}
	if (!$id && empty($row['id']) && $name !== '' && mb_strlen($name) <= 255) {
		$res = CIBlockElement::GetList(array('ID' => 'ASC'), array_merge($ndCbFilter, array('NAME' => '%'.$name.'%')), false, array('nTopCount' => 20), $ndCbSelect);
		while ($el = $res->Fetch()) {
			if ($el['CATALOG_TYPE'] != 3 && trim(preg_replace('/\s+/u', ' ', $el['NAME'])) === $name) {
				$id = (int)$el['ID'];
				break;
			}
		}
	}
	if ($id) {
		$ndCbQty[$id] = ($ndCbQty[$id] ?? 0) + $qty;
	} else {
		$ndCbFailed[] = $name !== '' ? mb_substr($name, 0, 120) : 'товар #'.(int)($row['id'] ?? 0);
	}
}
if (!$ndCbQty) {
	$ndCbAnswer(false, array('failed' => $ndCbFailed, 'error' => 'Не нашли эти товары в каталоге — добавьте их из карточек товаров.'));
}

$ndCbAdded = 0;
foreach ($ndCbQty as $id => $qty) {
	$r = \Bitrix\Catalog\Product\Basket::addProduct(array('PRODUCT_ID' => $id, 'QUANTITY' => $qty), array('USE_MERGE' => 'Y'));
	if ($r->isSuccess()) {
		$ndCbAdded++;
	} else {
		$ndCbFailed[] = 'товар #'.$id;
	}
}

$ndCbAnswer($ndCbAdded > 0, array('added' => $ndCbAdded, 'failed' => $ndCbFailed,
	'error' => $ndCbAdded ? '' : 'Не удалось добавить товары в корзину.'));
