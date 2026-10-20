<?php
/* Расчёт из калькулятора террасы (/info/kalkulyator-terrasy/) → PDF в сессию посетителя
   (Ирина, 20.10.2026).

   Кнопка «Рассчитать с менеджером» открывает общую форму MAINFORM и одновременно шлёт сюда
   параметры террасы, строки сметы и картинку плана (JPEG). Здесь собираем PDF и запоминаем
   его вместе с текстом сметы (ndTerraceCalcRemember). Когда посетитель отправит форму,
   PDF уйдёт вложением в письмо и в ленту лида Битрикс24 —
   см. local/php_interface/include/latitudo_terrace_calc_crm.php.

   Цены и количества пришли из браузера: это расчёт клиента, в лиде он так и подписан.

   POST: sessid, data (JSON). Ответ — JSON {ok, error?}. */

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$APPLICATION->RestartBuffer();
header('Content-Type: application/json; charset=utf-8');

$ndTcAnswer = function ($ok, $error = '') {
	echo json_encode(array('ok' => (bool)$ok, 'error' => $error), JSON_UNESCAPED_UNICODE);
	die();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid()) {
	$ndTcAnswer(false, 'Обновите страницу и отправьте ещё раз.');
}

$ndTcData = json_decode((string)($_POST['data'] ?? ''), true);
if (!is_array($ndTcData)) {
	$ndTcAnswer(false, 'Не удалось прочитать расчёт.');
}

// расчёт пересылается при каждом открытии формы — не больше 30 за сессию
$_SESSION['ND_TC_PREPARE'] = (int)($_SESSION['ND_TC_PREPARE'] ?? 0) + 1;
if ($_SESSION['ND_TC_PREPARE'] > 30) {
	$ndTcAnswer(false, 'Слишком много расчётов. Обновите страницу.');
}

$ndTcStr = function ($v, $max = 300) {
	$v = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$v)));
	return mb_substr($v, 0, $max);
};

// расчёт: параметры террасы, строки сметы, итог
$ndTcCalc = is_array($ndTcData['calc'] ?? null) ? $ndTcData['calc'] : array();
$ndTcParams = array();
foreach (array_slice((array)($ndTcCalc['params'] ?? array()), 0, 12) as $line) {
	$ndTcParams[] = $ndTcStr($line, 200);
}
$ndTcRows = array();
foreach (array_slice((array)($ndTcCalc['rows'] ?? array()), 0, 30) as $row) {
	if (!is_array($row)) continue;
	$ndTcRows[] = array(
		'name' => $ndTcStr($row['name'] ?? '', 200),
		'note' => $ndTcStr($row['note'] ?? '', 200),
		'qty' => $ndTcStr($row['qty'] ?? '', 20),
		'price' => is_numeric($row['price'] ?? null) ? (float)$row['price'] : null,
		'sum' => is_numeric($row['sum'] ?? null) ? (float)$row['sum'] : null,
	);
}
$ndTcTotal = is_numeric($ndTcCalc['total'] ?? null) ? (float)$ndTcCalc['total'] : 0;
if (!$ndTcRows) {
	$ndTcAnswer(false, 'Сначала заполните параметры террасы.');
}

// картинка плана: только JPEG, до 2 МБ
$ndTcPlan = '';
if (preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#', (string)($ndTcData['plan'] ?? ''), $m)) {
	$bin = base64_decode($m[1], true);
	if ($bin !== false && strlen($bin) < 2 * 1024 * 1024 && substr($bin, 0, 3) === "\xFF\xD8\xFF") {
		$ndTcPlan = $bin;
	}
}

$ndTcPage = $ndTcStr($ndTcData['page'] ?? '', 300);
if (strpos($ndTcPage, 'https://latitudo.ru/') !== 0) {
	$ndTcPage = 'https://latitudo.ru/info/kalkulyator-terrasy/';
}
$ndTcMoney = function ($v) {
	return number_format(round($v), 0, ',', ' ').' руб.';
};

/* ---------- PDF ---------- */
require_once($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_terrace_pdf.php');
$ndTcPdfBody = ndTerraceCalcPdf($ndTcParams, $ndTcRows, $ndTcTotal, $ndTcPlan, '', $ndTcPage);

/* ---------- Текст сметы для ленты лида ---------- */
$ndTcText = implode("\n", $ndTcParams)."\n\nСмета (расчёт клиента на сайте):\n";
foreach ($ndTcRows as $row) {
	$ndTcText .= $row['name'].': '.$row['qty'].($row['sum'] !== null ? ' = '.$ndTcMoney($row['sum']) : '')."\n";
}
$ndTcText .= 'Итого: '.$ndTcMoney($ndTcTotal)."\nСтраница: ".$ndTcPage;

$ndTcAnswer(ndTerraceCalcRemember($ndTcPdfBody, $ndTcText, $ndTcTotal), $ndTcPdfBody === '' ? 'PDF не собрался' : '');
