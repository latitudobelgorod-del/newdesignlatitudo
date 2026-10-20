<?php
/* Заявка из калькулятора террасы (/info/kalkulyator-terrasy/) → лид в Битрикс24 с расчётом в PDF
   (Ирина, 20.10.2026).

   Браузер присылает контакты, параметры террасы, строки сметы и картинку плана (JPEG).
   Здесь собираем PDF (tFPDF из модуля sale, шрифт PT Sans из модуля main), создаём лид
   (crm.lead.add — тот же вебхук и те же поля, что у заказа в один клик) и прикладываем
   PDF комментарием в ленту лида (crm.timeline.comment.add).

   Вебхук — ~/.latitudo_b24.php вне папки сайта и вне git (см. oneclickbuy.next/script.php).
   Нет файла (локальная сборка) — лид не создаём, отвечаем ok:false.

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

// бот заполнил скрытое поле — делаем вид, что всё хорошо
if (trim((string)($ndTcData['website'] ?? '')) !== '') {
	$ndTcAnswer(true);
}

// не чаще раза в 20 секунд и не больше 5 заявок за сессию
$ndTcNow = time();
$ndTcSent = array_filter((array)($_SESSION['ND_TC_LEAD'] ?? array()), 'is_int');
if (count($ndTcSent) >= 5 || ($ndTcSent && $ndTcNow - max($ndTcSent) < 20)) {
	$ndTcAnswer(false, 'Заявка уже отправлена. Если нужно что-то поправить — позвоните нам: +7 (495) 135-93-05.');
}

$ndTcStr = function ($v, $max = 300) {
	$v = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$v)));
	return mb_substr($v, 0, $max);
};
$ndTcName = $ndTcStr($ndTcData['name'] ?? '', 100);
$ndTcPhone = $ndTcStr($ndTcData['phone'] ?? '', 40);
$ndTcPhoneDigits = preg_replace('/\D/', '', $ndTcPhone);
$ndTcEmail = $ndTcStr($ndTcData['email'] ?? '', 100);
$ndTcComment = mb_substr(trim(strip_tags((string)($ndTcData['comment'] ?? ''))), 0, 1000);

if (strlen($ndTcPhoneDigits) < 10) {
	$ndTcAnswer(false, 'Укажите телефон.');
}
if ($ndTcEmail !== '' && !check_email($ndTcEmail)) {
	$ndTcAnswer(false, 'Проверьте e-mail.');
}
if (($ndTcData['agree'] ?? '') !== 'Y') {
	$ndTcAnswer(false, 'Нужно согласие на обработку персональных данных.');
}

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
$ndTcPdfBody = ndTerraceCalcPdf($ndTcParams, $ndTcRows, $ndTcTotal, $ndTcPlan, $ndTcName, $ndTcPage);

/* ---------- Лид в Битрикс24 ---------- */
$ndB24 = @include dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/.latitudo_b24.php';
$ndTcHook = (is_array($ndB24) && !empty($ndB24['webhook'])) ? $ndB24['webhook'] : '';
if ($ndTcHook === '') {
	$ndTcAnswer(false, 'Отправка временно недоступна. Позвоните нам: +7 (495) 135-93-05.');
}

$ndTcCall = function ($method, array $params) use ($ndTcHook) {
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_URL => $ndTcHook.$method.'.json',
		CURLOPT_POSTFIELDS => http_build_query($params),
	));
	$res = curl_exec($ch);
	curl_close($ch);
	return is_string($res) ? json_decode($res, true) : null;
};

// UTM — из сессии, как у заказа в один клик; utm_content без метки — тип устройства
$ndTcUtm = array();
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_geo') as $key) {
	$ndTcUtm[$key] = !empty($_SESSION['UTM'][$key]) ? (string)$_SESSION['UTM'][$key] : 'empty';
}
if ($ndTcUtm['utm_content'] === 'empty') {
	$ndTcUtm['utm_content'] = preg_match('/Mobile|Android|iPhone|iPad/i', (string)($_SERVER['HTTP_USER_AGENT'] ?? '')) ? 'mobile' : 'desktop';
}

$ndTcText = "<b><font color=red size=4>Расчёт из калькулятора террасы</font></b>\n"
	.($ndTcComment !== '' ? "<b>Сообщение клиента:</b>\n".htmlspecialcharsbx($ndTcComment)."\n\n" : '')
	."<b>Параметры:</b>\n".htmlspecialcharsbx(implode("\n", $ndTcParams))."\n\n<b>Смета (расчёт клиента на сайте):</b>\n";
foreach ($ndTcRows as $row) {
	$ndTcText .= htmlspecialcharsbx($row['name']).': '.htmlspecialcharsbx($row['qty'])
		.($row['sum'] !== null ? ' = '.$ndTcMoney($row['sum']) : '')."\n";
}
$ndTcText .= '<b>Итого:</b> '.$ndTcMoney($ndTcTotal).($ndTcPdfBody !== '' ? "\nPDF с планом и сметой — в ленте лида." : '');

$ndTcLead = $ndTcCall('crm.lead.add', array(
	'fields' => array(
		'TITLE' => 'Расчёт террасы с сайта RU',
		'NAME' => $ndTcName,
		'UF_CRM_1670914333154' => 'latitudo.ru',          // домен сайта
		'UF_CRM_1670914240846' => $ndTcPage,             // страница заявки
		'UF_CRM_1674473471' => $ndTcUtm['utm_source'],
		'UF_CRM_1674473492' => $ndTcUtm['utm_campaign'],
		'UF_CRM_1674473502' => $ndTcUtm['utm_content'],
		'UF_CRM_1674473509' => $ndTcUtm['utm_term'],
		'UF_CRM_1674473526' => $ndTcUtm['utm_medium'],
		'UF_CRM_1674473518' => $ndTcUtm['utm_geo'],
		'OPPORTUNITY' => round($ndTcTotal),
		'COMMENTS' => $ndTcText,
		'PHONE' => array('n0' => array('VALUE' => $ndTcPhoneDigits, 'VALUE_TYPE' => 'WORK')),
		'EMAIL' => $ndTcEmail !== '' ? array('n0' => array('VALUE' => $ndTcEmail, 'VALUE_TYPE' => 'WORK')) : array(),
		'ASSIGNED_BY_ID' => 1,
	),
	'params' => array('REGISTER_SONET_EVENT' => 'Y'),
));
$ndTcLeadId = (int)($ndTcLead['result'] ?? 0);
if ($ndTcLeadId <= 0) {
	$ndTcAnswer(false, 'Не удалось отправить заявку. Позвоните нам: +7 (495) 135-93-05.');
}

$_SESSION['ND_TC_LEAD'][] = $ndTcNow;

if ($ndTcPdfBody !== '') {
	$ndTcCall('crm.timeline.comment.add', array(
		'fields' => array(
			'ENTITY_ID' => $ndTcLeadId,
			'ENTITY_TYPE' => 'lead',
			'COMMENT' => 'Расчёт террасы из калькулятора на сайте (PDF с планом и сметой)',
			'FILES' => array(array('raschet-terrasy-'.date('Y-m-d').'.pdf', base64_encode($ndTcPdfBody))),
		),
	));
}

$ndTcAnswer(true);
