<?php
/* Галочка «Доставка» в корзине → сессия (Ирина, 28.09.2026; 02.10.2026).

   Сам расчёт с 02.10.2026 делает сервер через API калькулятора
   (/local/ajax/nd_delivery_calc.php) и сразу кладёт в сессию
   ND_DELIVERY_QUOTE. Здесь только галочка и сброс: цену из браузера больше
   не принимаем — раньше её присылал фрейм, и подставить можно было любую.

   POST: sessid, action=with (value=Y|N) | clear. Ответ — JSON {ok}. */

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$APPLICATION->RestartBuffer();
header('Content-Type: application/json; charset=utf-8');

$ndDqAnswer = function ($ok) {
	echo json_encode(array('ok' => (bool)$ok));
	die();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid()) {
	$ndDqAnswer(false);
}

$ndDqAction = (string)($_POST['action'] ?? '');

if ($ndDqAction === 'clear') {
	unset($_SESSION['ND_DELIVERY_QUOTE']);
	$ndDqAnswer(true);
}

if ($ndDqAction === 'with' && is_array($_SESSION['ND_DELIVERY_QUOTE'] ?? null)) {
	$_SESSION['ND_DELIVERY_QUOTE']['WITH'] = (($_POST['value'] ?? '') === 'Y');
	$ndDqAnswer(true);
}

$ndDqAnswer(false);
