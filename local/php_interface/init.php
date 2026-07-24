<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Форма CLAIM (id 27, «Отправить рекламацию / претензию»):
 * штатная связь «Веб-формы → CRM» создаёт лид в B24, но НЕ умеет выгружать файловые поля.
 * Дозагружаем прикреплённый файл (поле формы FILE) в лид B24 в поле UF_CRM_1772713891
 * тем же вебхуком, что и oneclickbuy.
 *
 * Обработчик висит на onAfterResultAdd с высоким sort — чтобы отработать ПОСЛЕ
 * штатного CRM-коннектора (FormCRM::onResultAdded), когда лид уже создан.
 * Всё в try/catch: любые сбои не должны ломать отправку формы.
 * Лог отладки — /local/php_interface/claim_crm.log (убрать после проверки).
 */
AddEventHandler('form', 'onAfterResultAdd', 'Latitudo_ClaimFileToCrm', false, 9000);

define('LATITUDO_CLAIM_FORM_ID', 27);
define('LATITUDO_CLAIM_CRM_FILE_UF', 'UF_CRM_1772713891');
define('LATITUDO_B24_WEBHOOK', 'https://latitudo.bitrix24.ru/rest/45/rt9aho9sj05d409a/');

function Latitudo_ClaimLog($msg)
{
    @file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/claim_crm.log',
        date('Y-m-d H:i:s') . ' ' . $msg . "\n",
        FILE_APPEND
    );
}

function Latitudo_ClaimFileToCrm($WEB_FORM_ID, $RESULT_ID)
{
    if ((int)$WEB_FORM_ID !== LATITUDO_CLAIM_FORM_ID) {
        return;
    }

    try {
        Latitudo_ClaimLog('START result=' . $RESULT_ID . ' form=' . $WEB_FORM_ID);

        if (!CModule::IncludeModule('form')) {
            Latitudo_ClaimLog('  модуль form не подключился');
            return;
        }

        $arValues = $arResult = $arAnswer = array();
        CFormResult::GetDataByID($RESULT_ID, $arValues, $arResult, $arAnswer);

        // --- файл (поле FILE) ---
        $fileId = Latitudo_ClaimExtractFileId($arAnswer);
        Latitudo_ClaimLog('  fileId=' . $fileId . ' | ключи ответов: ' . implode(',', array_keys((array)$arAnswer)));
        if ($fileId <= 0) {
            Latitudo_ClaimLog('  файл не прикреплён — выходим');
            return;
        }

        $file = CFile::GetFileArray($fileId);
        if (!$file) {
            Latitudo_ClaimLog('  CFile::GetFileArray пусто для ' . $fileId);
            return;
        }
        $path = $_SERVER['DOCUMENT_ROOT'] . $file['SRC'];
        if (!is_file($path)) {
            $path = $_SERVER['DOCUMENT_ROOT'] . CFile::GetPath($fileId);
        }
        if (!is_file($path)) {
            Latitudo_ClaimLog('  файл ' . $fileId . ' не найден на диске: ' . $path);
            return;
        }
        $fileName = $file['ORIGINAL_NAME'] ? $file['ORIGINAL_NAME'] : $file['FILE_NAME'];
        $b64 = base64_encode(file_get_contents($path));
        Latitudo_ClaimLog('  файл: ' . $fileName . ' (' . strlen($b64) . ' b64-байт)');

        // --- телефон, чтобы найти созданный лид ---
        $phone = '';
        if (!empty($arAnswer['PHONE']) && is_array($arAnswer['PHONE'])) {
            foreach ($arAnswer['PHONE'] as $a) {
                if (!empty($a['USER_TEXT'])) { $phone = $a['USER_TEXT']; break; }
            }
        }
        $phone = preg_replace('/\D+/', '', $phone);
        if ($phone === '') {
            Latitudo_ClaimLog('  пустой телефон');
            return;
        }
        $last10 = substr($phone, -10);

        // --- находим свежесозданный лид по телефону ---
        $resp = Latitudo_B24Call('crm.lead.list', array(
            'order'  => array('ID' => 'DESC'),
            'filter' => array('%PHONE' => $last10),
            'select' => array('ID', 'DATE_CREATE'),
            'start'  => 0,
        ));
        $leadId = !empty($resp['result'][0]['ID']) ? (int)$resp['result'][0]['ID'] : 0;
        Latitudo_ClaimLog('  поиск лида по ' . $last10 . ' → leadId=' . $leadId
            . (isset($resp['error']) ? ' | list error: ' . $resp['error'] . ' ' . $resp['error_description'] : ''));
        if ($leadId <= 0) {
            return;
        }

        // --- кладём файл в поле лида ---
        $upd = Latitudo_B24Call('crm.lead.update', array(
            'id'     => $leadId,
            'fields' => array(LATITUDO_CLAIM_CRM_FILE_UF => array($fileName, $b64)),
        ));
        if (!empty($upd['result'])) {
            Latitudo_ClaimLog('  OK: файл записан в лид ' . $leadId);
        } else {
            Latitudo_ClaimLog('  update FAIL лид ' . $leadId . ': ' . substr(print_r($upd, true), 0, 400));
        }
    } catch (\Throwable $e) {
        Latitudo_ClaimLog('  EXCEPTION: ' . $e->getMessage());
    }
}

/** Достаёт ID загруженного файла из ответов формы (поле FILE). */
function Latitudo_ClaimExtractFileId($arAnswer)
{
    if (!empty($arAnswer['FILE']) && is_array($arAnswer['FILE'])) {
        foreach ($arAnswer['FILE'] as $a) {
            if (!empty($a['USER_FILE_ID'])) {
                return (int)$a['USER_FILE_ID'];
            }
        }
    }
    // запасной вариант: любой файловый ответ
    foreach ((array)$arAnswer as $answers) {
        if (is_array($answers)) {
            foreach ($answers as $a) {
                if (!empty($a['USER_FILE_ID'])) {
                    return (int)$a['USER_FILE_ID'];
                }
            }
        }
    }
    return 0;
}

/** POST-запрос к вебхуку B24, возвращает распарсенный ответ. */
function Latitudo_B24Call($method, array $params)
{
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => LATITUDO_B24_WEBHOOK . $method . '.json',
        CURLOPT_POST           => 1,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_TIMEOUT        => 25,
    ));
    $out = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($out, true);
    return is_array($data) ? $data : array();
}
