<?php
/**
 * Форма CLAIM (id 27, «Отправить рекламацию / претензию»).
 * Штатная связь «Веб-формы → CRM» создаёт лид в B24 (с задержкой), но НЕ выгружает файловые поля
 * и НЕ вкладывает файл в письмо. Здесь добавлено:
 *   1) файл заявки (поле формы FILE) → в лид B24, поле UF_CRM_1772713891 (множественное, формат fileData);
 *   2) файл заявки → вложением в письмо FORM_FILLING_CLAIM.
 *
 * Лид создаётся асинхронно (несколько минут), поэтому файл в CRM кладёт ОТЛОЖЕННЫЙ АГЕНТ:
 * при сабмите планируется агент, который находит созданный лид по телефону
 * (crm.duplicate.findbycomm, новейший лид не раньше времени заявки) и кладёт файл.
 * Письмо уходит синхронно — файл вкладывается в OnBeforeEventAdd в том же запросе.
 *
 * ВАЖНО: подключается ТОЛЬКО из bitrix/php_interface/init.php (там же CCustomNext, регион-теги).
 * НЕ выносить в local/php_interface/init.php — он перекрывает bitrix-версию.
 *
 * Лог только ошибок — bitrix/php_interface/include/claim_crm.log.
 */

if (!defined('LATITUDO_CLAIM_FORM_ID')) {
    define('LATITUDO_CLAIM_FORM_ID', 27);
    define('LATITUDO_CLAIM_CRM_FILE_UF', 'UF_CRM_1772713891');
    // Ключ вебхука — вне папки сайта (21.09.2026): ~/.latitudo_b24.php
    $__ndB24 = @include dirname(__DIR__, 5) . '/.latitudo_b24.php';
    define('LATITUDO_B24_WEBHOOK', (is_array($__ndB24) && !empty($__ndB24['webhook'])) ? $__ndB24['webhook'] : '');
    define('LATITUDO_CLAIM_MAX_AGE', 25 * 60); // сколько ждём появления лида

    AddEventHandler('form', 'onAfterResultAdd', 'Latitudo_ClaimOnResultAdd', false, 9000);
    AddEventHandler('main', 'OnBeforeEventAdd', 'Latitudo_ClaimMailAttach');
}

if (!function_exists('Latitudo_ClaimLog')) {
    function Latitudo_ClaimLog($msg)
    {
        @file_put_contents(__DIR__ . '/claim_crm.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
    }
}

/** При сабмите: если есть файл — запомнить его для письма и запланировать агент для CRM. */
if (!function_exists('Latitudo_ClaimOnResultAdd')) {
    function Latitudo_ClaimOnResultAdd($WEB_FORM_ID, $RESULT_ID)
    {
        if ((int)$WEB_FORM_ID !== LATITUDO_CLAIM_FORM_ID) {
            return;
        }
        try {
            if (!CModule::IncludeModule('form')) {
                return;
            }
            $v = $r = $a = array();
            CFormResult::GetDataByID($RESULT_ID, $v, $r, $a);
            $fileId = Latitudo_ClaimExtractFileId($a);
            if ($fileId <= 0) {
                return; // файла нет
            }
            // Фаза 2: запомнить файл для вложения в письмо (тот же запрос → OnBeforeEventAdd)
            $GLOBALS['LATITUDO_CLAIM_MAIL_FILE'] = $fileId;
            // REST sends the file atomically with its own lead; keep mail attachment.
            if (class_exists('LatitudoFormCrm') && LatitudoFormCrm::enabled(27)) return;

            // Фаза 1: запланировать агент, который положит файл в лид, когда тот создастся
            $name = 'Latitudo_ClaimAttach(' . (int)$RESULT_ID . ',' . time() . ');';
            if (!CAgent::GetList(array(), array('NAME' => $name))->Fetch()) {
                CAgent::AddAgent($name, 'form', 'N', 90, '', 'Y', ConvertTimeStamp(time() + 90, 'FULL'), 100);
            }
        } catch (\Throwable $e) {
            Latitudo_ClaimLog('onResultAdd EX: ' . $e->getMessage());
        }
    }
}

/** Фаза 2: прикрепить файл заявки к письму FORM_FILLING_CLAIM. */
if (!function_exists('Latitudo_ClaimMailAttach')) {
    function Latitudo_ClaimMailAttach(&$eventName, &$lid, &$cFields, &$messageId, &$file, &$languageId)
    {
        if ($eventName !== 'FORM_FILLING_CLAIM' || empty($GLOBALS['LATITUDO_CLAIM_MAIL_FILE'])) {
            return;
        }
        if (!is_array($file)) {
            $file = $file ? array($file) : array();
        }
        $fid = (int)$GLOBALS['LATITUDO_CLAIM_MAIL_FILE'];
        if ($fid > 0 && !in_array($fid, $file)) {
            $file[] = $fid;
        }
    }
}

/** Агент Фазы 1: найти лид и положить файл. Возвращает себя для ретрая или '' для остановки. */
if (!function_exists('Latitudo_ClaimAttach')) {
    function Latitudo_ClaimAttach($resultId, $submitTs = 0)
    {
        $self = 'Latitudo_ClaimAttach(' . (int)$resultId . ',' . (int)$submitTs . ');';
        try {
            if (!CModule::IncludeModule('form')) {
                return $self;
            }
            if ($submitTs && (time() - (int)$submitTs) > LATITUDO_CLAIM_MAX_AGE) {
                Latitudo_ClaimLog("AGENT $resultId: лид не найден за отведённое время — стоп");
                return '';
            }

            // Existing legacy agents must not overwrite files of REST-created leads.
            if (class_exists('LatitudoFormCrm') && LatitudoFormCrm::enabled(27)) {
                $queued = (new LatitudoFormCrmBitrixStore())->load((int)$resultId);
                if ($queued) return '';
            }
            $v = $arResult = $arAnswer = array();
            CFormResult::GetDataByID($resultId, $v, $arResult, $arAnswer);

            $fileId = Latitudo_ClaimExtractFileId($arAnswer);
            if ($fileId <= 0) {
                return '';
            }

            // телефон
            $phone = '';
            foreach ((array)($arAnswer['PHONE'] ?? array()) as $x) {
                if (!empty($x['USER_TEXT'])) { $phone = $x['USER_TEXT']; break; }
            }
            $phone = preg_replace('/\D+/', '', $phone);
            if ($phone === '') {
                return '';
            }
            $last10 = substr($phone, -10);

            // ищем лид по телефону (findbycomm требует код страны)
            $cand = array();
            foreach (array('+7' . $last10, '7' . $last10, '8' . $last10) as $vv) {
                $fc = Latitudo_B24Call('crm.duplicate.findbycomm', array(
                    'entity_type' => 'LEAD', 'type' => 'PHONE', 'values' => array($vv),
                ));
                if (!empty($fc['result']['LEAD'])) {
                    $cand = array_merge($cand, $fc['result']['LEAD']);
                }
            }
            $cand = array_values(array_unique(array_map('intval', $cand)));
            if (!$cand) {
                return $self; // лид ещё не создан — ретрай
            }
            $leadId = max($cand); // новейший

            // лид должен быть создан не раньше заявки (иначе это старый лид клиента — ждём новый)
            if ($submitTs) {
                $lg = Latitudo_B24Call('crm.lead.get', array('id' => $leadId));
                $leadTs = !empty($lg['result']['DATE_CREATE']) ? strtotime($lg['result']['DATE_CREATE']) : 0;
                if ($leadTs && $leadTs < ((int)$submitTs - 120)) {
                    return $self; // новейший лид старше заявки — ждём новый
                }
            }

            $file = CFile::GetFileArray($fileId);
            if (!$file) {
                return '';
            }
            $path = $_SERVER['DOCUMENT_ROOT'] . $file['SRC'];
            if (!is_file($path)) {
                $path = $_SERVER['DOCUMENT_ROOT'] . CFile::GetPath($fileId);
            }
            if (!is_file($path)) {
                Latitudo_ClaimLog("AGENT $resultId: файл $fileId не найден на диске");
                return '';
            }
            $fileName = $file['ORIGINAL_NAME'] ? $file['ORIGINAL_NAME'] : $file['FILE_NAME'];
            $b64 = base64_encode(file_get_contents($path));

            // множественное файловое поле → [ {fileData:[имя, base64]} ]
            $upd = Latitudo_B24Call('crm.lead.update', array(
                'id'     => $leadId,
                'fields' => array(LATITUDO_CLAIM_CRM_FILE_UF => array(array('fileData' => array($fileName, $b64)))),
            ));
            if (empty($upd['result'])) {
                Latitudo_ClaimLog("AGENT $resultId: update FAIL лид $leadId: " . substr(print_r($upd, true), 0, 300));
            }
            return '';
        } catch (\Throwable $e) {
            Latitudo_ClaimLog("AGENT $resultId EX: " . $e->getMessage());
            return '';
        }
    }
}

/** ID загруженного файла из ответов формы (поле FILE). */
if (!function_exists('Latitudo_ClaimExtractFileId')) {
    function Latitudo_ClaimExtractFileId($arAnswer)
    {
        if (!empty($arAnswer['FILE']) && is_array($arAnswer['FILE'])) {
            foreach ($arAnswer['FILE'] as $a) {
                if (!empty($a['USER_FILE_ID'])) return (int)$a['USER_FILE_ID'];
            }
        }
        foreach ((array)$arAnswer as $answers) {
            if (is_array($answers)) {
                foreach ($answers as $a) {
                    if (!empty($a['USER_FILE_ID'])) return (int)$a['USER_FILE_ID'];
                }
            }
        }
        return 0;
    }
}

/** POST к вебхуку B24. */
if (!function_exists('Latitudo_B24Call')) {
    function Latitudo_B24Call($method, array $params)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => LATITUDO_B24_WEBHOOK . $method . '.json',
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT        => 30,
        ));
        $out = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($out, true);
        return is_array($data) ? $data : array();
    }
}
