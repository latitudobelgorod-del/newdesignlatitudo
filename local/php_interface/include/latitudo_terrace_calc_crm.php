<?php
/**
 * Калькулятор террасы → общая форма MAINFORM → расчёт в PDF к письму и к лиду Битрикс24
 * (Ирина, 20.10.2026).
 *
 * Как устроено:
 * 1. Кнопка «Рассчитать с менеджером» на /info/kalkulyator-terrasy/ открывает общую форму
 *    (как «Оставить заявку»), а заодно отправляет расчёт в /local/ajax/nd_terrace_calc_lead.php.
 *    Тот собирает PDF (latitudo_terrace_pdf.php), сохраняет его файлом Битрикса и запоминает
 *    в сессии: ndTerraceCalcRemember().
 * 2. Посетитель отправляет форму. Если подпись формы (NAMEFORM) — «Рассчитать с менеджером»
 *    и в сессии свежий расчёт, то:
 *    - PDF вкладывается в письмо FORM_FILLING_MAINFORM (OnBeforeEventAdd, тот же запрос);
 *    - ставится агент Latitudo_TerraceCalcAttach: лид создаёт штатная связь «Веб-формы → CRM»
 *      с задержкой до нескольких минут, агент ждёт его, находит по телефону и кладёт в ленту
 *      комментарий со сметой и PDF, а пустую сумму лида заполняет итогом расчёта.
 *    Та же схема, что у файла претензий (bitrix/php_interface/include/claim_file_crm.php).
 *
 * Вебхук — ~/.latitudo_b24.php вне папки сайта и вне git. Лог только ошибок:
 * upload/tmp/nd_terrace_calc_crm.log.
 */

const ND_TC_FORM_SID = 'MAINFORM';
const ND_TC_FORM_TITLE = 'Рассчитать с менеджером';
const ND_TC_MAX_AGE = 25 * 60;     // сколько агент ждёт появления лида
const ND_TC_SESSION_TTL = 3 * 3600; // сколько расчёт в сессии считается свежим

function ndTerraceCalcLog($msg)
{
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/upload/tmp/nd_terrace_calc_crm.log', date('Y-m-d H:i:s').' '.$msg."\n", FILE_APPEND);
}

/** Запомнить расчёт посетителя: PDF → файл Битрикса, текст сметы и итог → сессия. */
function ndTerraceCalcRemember($pdfBody, $text, $total)
{
	$old = $_SESSION['ND_TC_CALC'] ?? null;
	$fileId = 0;
	if ($pdfBody !== '') {
		$fileId = (int)CFile::SaveFile(array(
			'name' => 'raschet-terrasy-'.date('Y-m-d').'.pdf',
			'type' => 'application/pdf',
			'content' => $pdfBody,
			'MODULE_ID' => 'form',
		), 'nd_terrace_calc');
	}
	// прежний расчёт этой сессии, ещё не ушедший с формой, больше не нужен
	if (is_array($old) && !empty($old['file']) && empty($old['sent'])) {
		CFile::Delete((int)$old['file']);
	}
	$_SESSION['ND_TC_CALC'] = array('file' => $fileId, 'text' => (string)$text, 'total' => (float)$total, 'ts' => time());
	return $fileId > 0;
}

function ndTerraceCalcB24($method, array $params)
{
	static $hook = null;
	if ($hook === null) {
		$cfg = @include dirname(dirname($_SERVER['DOCUMENT_ROOT'])).'/.latitudo_b24.php';
		$hook = (is_array($cfg) && !empty($cfg['webhook'])) ? $cfg['webhook'] : '';
	}
	if ($hook === '') {
		return null;
	}
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_URL => $hook.$method.'.json',
		CURLOPT_POSTFIELDS => http_build_query($params),
	));
	$res = curl_exec($ch);
	curl_close($ch);
	return is_string($res) ? json_decode($res, true) : null;
}

/** Ответ формы по символьному коду поля. */
function ndTerraceCalcAnswer(array $answers, $sid)
{
	foreach ((array)($answers[$sid] ?? array()) as $a) {
		if (isset($a['USER_TEXT']) && $a['USER_TEXT'] !== '') {
			return (string)$a['USER_TEXT'];
		}
	}
	return '';
}

AddEventHandler('form', 'onAfterResultAdd', function ($formId, $resultId) {
	$calc = $_SESSION['ND_TC_CALC'] ?? null;
	if (!is_array($calc) || !empty($calc['sent']) || time() - (int)$calc['ts'] > ND_TC_SESSION_TTL) {
		return;
	}
	try {
		$form = CForm::GetByID($formId)->Fetch();
		if (!$form || $form['SID'] !== ND_TC_FORM_SID) {
			return;
		}
		$q = $answers = array();
		CFormResult::GetDataByID($resultId, array(), $q, $answers);
		if (trim(ndTerraceCalcAnswer($answers, 'NAMEFORM')) !== ND_TC_FORM_TITLE) {
			return; // та же общая форма, но открыта другой кнопкой
		}
		$_SESSION['ND_TC_CALC']['sent'] = true;
		$fileId = (int)$calc['file'];
		if ($fileId > 0) {
			$GLOBALS['ND_TC_MAIL_FILE'] = $fileId;
		}
		$name = 'Latitudo_TerraceCalcAttach('.(int)$resultId.','.time().');';
		COption::SetOptionString('main', 'nd_tc_'.(int)$resultId, serialize(array(
			'file' => $fileId, 'text' => $calc['text'], 'total' => $calc['total'],
		)));
		CAgent::AddAgent($name, 'form', 'N', 90, '', 'Y', ConvertTimeStamp(time() + 90, 'FULL'), 100);
	} catch (\Throwable $e) {
		ndTerraceCalcLog('onAfterResultAdd '.$resultId.': '.$e->getMessage());
	}
});

AddEventHandler('main', 'OnBeforeEventAdd', function (&$event, &$lid, &$fields, &$messageId, &$files) {
	if ($event !== 'FORM_FILLING_'.ND_TC_FORM_SID || empty($GLOBALS['ND_TC_MAIL_FILE'])) {
		return;
	}
	if (!is_array($files)) {
		$files = $files ? array($files) : array();
	}
	$files[] = (int)$GLOBALS['ND_TC_MAIL_FILE'];
	unset($GLOBALS['ND_TC_MAIL_FILE']);
});

/** Агент: найти лид заявки и положить в его ленту смету с PDF. '' — закончить, себя — повторить. */
function Latitudo_TerraceCalcAttach($resultId, $submitTs)
{
	$self = 'Latitudo_TerraceCalcAttach('.(int)$resultId.','.(int)$submitTs.');';
	$optName = 'nd_tc_'.(int)$resultId;
	$done = function () use ($optName) {
		COption::RemoveOption('main', $optName);
		return '';
	};
	try {
		$calc = @unserialize(COption::GetOptionString('main', $optName, ''), array('allowed_classes' => false));
		if (!is_array($calc) || !CModule::IncludeModule('form')) {
			return $done();
		}
		if (time() - (int)$submitTs > ND_TC_MAX_AGE) {
			ndTerraceCalcLog("агент $resultId: лид не появился за ".(ND_TC_MAX_AGE / 60).' мин — стоп');
			return $done();
		}
		$q = $answers = array();
		CFormResult::GetDataByID($resultId, array(), $q, $answers);
		$phone = substr(preg_replace('/\D+/', '', ndTerraceCalcAnswer($answers, 'PHONE')), -10);
		if (strlen($phone) < 10) {
			return $done();
		}

		// crm.duplicate.findbycomm ищет только с кодом страны
		$ids = array();
		foreach (array('+7'.$phone, '7'.$phone, '8'.$phone) as $value) {
			$res = ndTerraceCalcB24('crm.duplicate.findbycomm', array('entity_type' => 'LEAD', 'type' => 'PHONE', 'values' => array($value)));
			if ($res === null) {
				ndTerraceCalcLog("агент $resultId: нет ответа Битрикс24");
				return $self;
			}
			$ids = array_merge($ids, (array)($res['result']['LEAD'] ?? array()));
		}
		$ids = array_map('intval', $ids);
		if (!$ids) {
			return $self; // лид ещё не создан
		}
		$leadId = max($ids);
		$lead = ndTerraceCalcB24('crm.lead.get', array('id' => $leadId));
		$created = !empty($lead['result']['DATE_CREATE']) ? strtotime($lead['result']['DATE_CREATE']) : 0;
		if ($created && $created < (int)$submitTs - 120) {
			return $self; // это старый лид клиента — ждём новый
		}

		$comment = array('ENTITY_ID' => $leadId, 'ENTITY_TYPE' => 'lead', 'COMMENT' => "Расчёт террасы из калькулятора на сайте\n\n".$calc['text']);
		$file = $calc['file'] ? CFile::GetFileArray((int)$calc['file']) : null;
		$path = $file ? $_SERVER['DOCUMENT_ROOT'].$file['SRC'] : '';
		if ($path !== '' && is_file($path)) {
			$comment['FILES'] = array(array($file['ORIGINAL_NAME'] ?: $file['FILE_NAME'], base64_encode(file_get_contents($path))));
		}
		$res = ndTerraceCalcB24('crm.timeline.comment.add', array('fields' => $comment));
		if (empty($res['result'])) {
			ndTerraceCalcLog("агент $resultId: комментарий в лид $leadId не добавлен: ".substr(json_encode($res, JSON_UNESCAPED_UNICODE), 0, 300));
		}
		if ((float)($lead['result']['OPPORTUNITY'] ?? 0) <= 0 && $calc['total'] > 0) {
			ndTerraceCalcB24('crm.lead.update', array('id' => $leadId, 'fields' => array('OPPORTUNITY' => round($calc['total']), 'CURRENCY_ID' => 'RUB')));
		}
		return $done();
	} catch (\Throwable $e) {
		ndTerraceCalcLog("агент $resultId: ".$e->getMessage());
		return $done();
	}
}
