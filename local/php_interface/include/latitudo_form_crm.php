<?php
require_once __DIR__.'/latitudo_form_crm_delivery.php';

final class LatitudoFormCrmBitrixStore implements LatitudoFormCrmStore
{
    public function lock(int $id): bool
    {
        $r = $GLOBALS['DB']->Query("SELECT GET_LOCK('nd_form1_crm_".$id."',0) AS L")->Fetch();
        return (int)$r['L'] === 1;
    }
    public function unlock(int $id): void
    {
        $GLOBALS['DB']->Query("SELECT RELEASE_LOCK('nd_form1_crm_".$id."')");
    }
    public function load(int $id): ?array
    {
        $r = $GLOBALS['DB']->Query('SELECT * FROM b_latitudo_form_crm WHERE result_id='.$id)->Fetch();
        if ($r) $r['error'] = $r['last_error'];
        return $r ?: null;
    }
    public function save(int $id, array $state): void
    {
        $db = $GLOBALS['DB'];
        $status = $db->ForSql($state['status']);
        $error = $db->ForSql($state['error'] ?? '');
        $lead = (int)($state['lead_id'] ?? 0);
        $attempts = (int)($state['attempts'] ?? 0);
        $db->Query("INSERT INTO b_latitudo_form_crm (result_id,status,lead_id,attempts,last_error,updated_at)
            VALUES ($id,'$status',$lead,$attempts,'$error',NOW())
            ON DUPLICATE KEY UPDATE status=VALUES(status),lead_id=VALUES(lead_id),
            attempts=VALUES(attempts),last_error=VALUES(last_error),updated_at=NOW()");
    }
    public function result(int $id): array
    {
        $r = CFormResult::GetByID($id)->Fetch();
        if (!$r) throw new RuntimeException('Результат формы не найден.');
        return $r;
    }
    public function payload(int $id): array
    {
        $formId = (int)$this->result($id)['FORM_ID'];
        if (!LatitudoFormCrm::enabled($formId)) throw new RuntimeException('REST для формы выключен.');
        $link = CFormCrm::GetByFormID($formId)->Fetch();
        if (!$link || $link['LINK_TYPE'] !== 'M') {
            throw new RuntimeException('Старая автоматическая отправка не отключена. Отправка вебхуком остановлена во избежание дублей.');
        }
        $result = $answers = [];
        CFormResult::GetDataByID($id, [], $result, $answers);
        if ((int)($result['FORM_ID'] ?? 0) !== $formId) throw new RuntimeException('Неверная форма.');
        $mapping = [];
        $rows = CFormCrm::GetFields($link['ID']);
        while ($r = $rows->Fetch()) $mapping[] = $r;
        if (!$mapping) throw new RuntimeException('Связи полей CRM отсутствуют.');
        $fields = LatitudoFormCrmDelivery::mapFields(CForm::GetByID($formId)->Fetch(), $result, $answers, $mapping);
        foreach ($mapping as $map) {
            if ($map['CRM_FIELD'] !== 'UF_CRM_1772713891') continue;
            unset($fields[$map['CRM_FIELD']]);
            foreach ($answers as $rows) foreach ($rows as $answer) {
                if ((int)$answer['FIELD_ID'] !== (int)$map['FIELD_ID'] || empty($answer['USER_FILE_ID'])) continue;
                $file = CFile::GetFileArray((int)$answer['USER_FILE_ID']);
                $path = $file ? realpath($_SERVER['DOCUMENT_ROOT'].$file['SRC']) : false;
                $upload = realpath($_SERVER['DOCUMENT_ROOT'].'/upload');
                if (!$path || !$upload || strpos($path, $upload.DIRECTORY_SEPARATOR) !== 0 || !is_readable($path)) {
                    throw new RuntimeException('Файл заявки недоступен; лид не создан.');
                }
                $data = file_get_contents($path);
                if ($data === false) throw new RuntimeException('Не удалось прочитать вложение.');
                $fields[$map['CRM_FIELD']][] = ['fileData' => [$file['ORIGINAL_NAME'] ?: $file['FILE_NAME'], base64_encode($data)]];
            }
        }
        return $fields;
    }
    public function markSent(int $id): void
    {
        CFormResult::SetCRMFlag($id, 'Y');
    }
}

final class LatitudoFormCrm
{
    public const OPTION = 'nd_form1_rest_enabled';
    public const AGENT = 'LatitudoFormCrm::runQueue();';

    public static function enabled(int $formId = 1): bool
    {
        return in_array($formId, LatitudoFormCrmDelivery::FORMS, true)
            && COption::GetOptionString('form', 'nd_form'.$formId.'_rest_enabled', 'N') === 'Y';
    }

    public static function request(string $method, array $params): array
    {
        if (!in_array($method, ['crm.lead.list', 'crm.lead.add'], true)) throw new RuntimeException('Unsupported REST method');
        $cfg = @include dirname(dirname($_SERVER['DOCUMENT_ROOT'])).'/.latitudo_b24.php';
        $hook = is_array($cfg) ? (string)($cfg['webhook'] ?? '') : '';
        if (!preg_match('~^https://latitudo\.bitrix24\.ru/rest/\d+/[a-zA-Z0-9]+/$~D', $hook)) {
            return ['_definite_error' => true, 'error_description' => 'Не настроен вебхук портала latitudo.bitrix24.ru.'];
        }
        $ch = curl_init($hook.$method.'.json');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
        $body = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $reply = is_string($body) ? json_decode($body, true) : null;
        if (!is_array($reply)) return ['error_description' => 'Нет корректного ответа CRM (HTTP '.$http.').'];
        if (isset($reply['error']) && is_string($reply['error']) && $http < 500) {
            $message = (string)($reply['error_description'] ?? $reply['error']);
            // No tokens, URLs or full request payloads in the UI or logs.
            $message = str_replace($hook, '[webhook]', $message);
            $message = preg_replace('~https?://\S+~u', '[URL]', $message);
            return ['_definite_error' => true, 'error_description' => mb_substr($message, 0, 1500)];
        }
        if ($http !== 200) return ['error_description' => 'Неожиданный ответ CRM (HTTP '.$http.').'];
        return $reply;
    }

    public static function delivery(): LatitudoFormCrmDelivery
    {
        return new LatitudoFormCrmDelivery(new LatitudoFormCrmBitrixStore(), [self::class, 'request']);
    }

    public static function afterAdd($formId, $resultId): void
    {
        if (!self::enabled((int)$formId)) return;
        try {
            // No REST request in the visitor's request: mail and the saved result
            // do not depend on CRM availability. The installed agent handles delivery.
            $id = (int)$resultId;
            $GLOBALS['DB']->Query("INSERT IGNORE INTO b_latitudo_form_crm
                (result_id,status,lead_id,attempts,last_error,updated_at) VALUES ($id,'pending',0,0,'',NOW())");
        } catch (Throwable $e) {
            AddMessage2Log('CRM queue insert failed; result #'.(int)$resultId.' remains on the site.', 'latitudo.form.crm');
        }
    }

    public static function runQueue(): string
    {
        if (!CModule::IncludeModule('form')) return self::AGENT;
        $active = array_values(array_filter(LatitudoFormCrmDelivery::FORMS, [self::class, 'enabled']));
        if (!$active) return self::AGENT;
        // Recover a queue insert missed by an interrupted request. Never import
        // historical results automatically: the cutover ID is saved at activation.
        foreach ($active as $formId) {
        $cutover = (int)COption::GetOptionString('form', 'nd_form'.$formId.'_rest_cutover', '2147483647');
        $GLOBALS['DB']->Query("INSERT IGNORE INTO b_latitudo_form_crm
            (result_id,status,lead_id,attempts,last_error,updated_at)
            SELECT r.ID,'pending',0,0,'',NOW() FROM b_form_result r
            LEFT JOIN b_latitudo_form_crm q ON q.result_id=r.ID
            WHERE r.FORM_ID=$formId AND r.ID>$cutover AND r.SENT_TO_CRM='N' AND q.result_id IS NULL
            ORDER BY r.ID LIMIT 100");
        }
        // Failed explicit validations need manual correction; unknown outcomes are
        // periodically reconciled, never blindly resent. Limit work per agent run.
        $rows = $GLOBALS['DB']->Query("SELECT q.result_id FROM b_latitudo_form_crm q JOIN b_form_result r ON r.ID=q.result_id
            WHERE r.FORM_ID IN (".implode(',',$active).") AND (status='pending' OR (status IN ('sending','uncertain') AND updated_at<DATE_SUB(NOW(),INTERVAL 5 MINUTE)))
            ORDER BY updated_at,result_id LIMIT 3");
        while ($r = $rows->Fetch()) {
            try { self::delivery()->send((int)$r['result_id']); }
            catch (Throwable $e) { AddMessage2Log('CRM worker failed for result #'.(int)$r['result_id'], 'latitudo.form.crm'); }
        }
        return self::AGENT;
    }

    public static function adminRequest(): void
    {
        if (!defined('ADMIN_SECTION') || ADMIN_SECTION !== true ) return;
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        // Keep the legacy connection (field mapping and native CRM flag), but not
        // its automatic sender. The REST queue is the only automatic sender.
        if ($path === '/bitrix/admin/form_edit.php' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && self::enabled((int)($_REQUEST['ID'] ?? 0))) {
            $_REQUEST['CRM_LINK_TYPE'] = $_POST['CRM_LINK_TYPE'] = 'M';
        }
        if ($path !== '/bitrix/admin/form_crm.php' || ($_REQUEST['action'] ?? '') !== 'add_lead'
            || !self::enabled((int)($_REQUEST['FORM_ID'] ?? 0))) return;
        $formId = (int)$_REQUEST['FORM_ID'];
        $id = (int)($_REQUEST['RESULT_ID'] ?? 0);
        $response = ['result' => 'error', 'error' => 'Недостаточно прав или сессия истекла.'];
        if ($id > 0 && $GLOBALS['USER']->IsAuthorized() && check_bitrix_sessid() && CModule::IncludeModule('form')) {
            $result = CFormResult::GetByID($id)->Fetch();
            $rights = CFormResult::GetPermissions($id);
            if ((int)($result['FORM_ID'] ?? 0) === $formId && CForm::GetPermission($formId) >= 20
                && (in_array('EDIT', $rights, true) || in_array('VIEW', $rights, true))) {
                try {
                    $state = self::delivery()->send($id);
                    $response = !empty($state['lead_id'])
                        ? ['result' => 'ok', 'ID' => (int)$state['lead_id']]
                        : ['result' => 'error', 'error' => $state['error'] ?? 'Отправка не подтверждена.'];
                } catch (Throwable $e) {
                    $response['error'] = 'Не удалось обработать заявку. Обновите список для проверки статуса.';
                    AddMessage2Log('CRM manual delivery failed for result #'.$id, 'latitudo.form.crm');
                }
            }
        }
        $GLOBALS['APPLICATION']->RestartBuffer();
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        die();
    }

    public static function adminList(&$list): void
    {
        $matched = false;
        foreach (LatitudoFormCrmDelivery::FORMS as $formId) {
            if (self::enabled($formId) && $list->table_id === 'tbl_form_result_list_'.md5((string)$formId)) $matched = true;
        }
        if (!$matched) return;
        $ids = [];
        foreach ($list->aRows as $row) if ((int)$row->id > 0) $ids[] = (int)$row->id;
        if (!$ids) return;
        $states = [];
        $rows = $GLOBALS['DB']->Query('SELECT * FROM b_latitudo_form_crm WHERE result_id IN ('.implode(',', $ids).')');
        while ($s = $rows->Fetch()) $states[(int)$s['result_id']] = $s;
        foreach ($list->aRows as $row) {
            $id = (int)$row->id;
            if (!isset($states[$id])) continue;
            $s = $states[$id];
            $sent = (int)$s['lead_id'] > 0;
            $labels = ['pending' => 'Ожидает отправки', 'sending' => 'Отправляется', 'sent' => 'Отправлено',
                'error' => 'Ошибка отправки', 'uncertain' => 'Результат отправки уточняется'];
            $text = $labels[$s['status']] ?? 'Не отправлено';
            if (($_REQUEST['mode'] ?? '') === 'excel') {
                $row->AddViewField('SENT_TO_CRM', htmlspecialcharsbx($text.($sent ? ' — лид #'.$s['lead_id'] : ' — '.$s['last_error'])));
                continue;
            }
            $html = '<img src="/bitrix/images/form/'.($sent ? 'green' : 'red').'.gif" alt="" id="crm_sent_'.$id.'"> ';
            $html .= htmlspecialcharsbx($text);
            if ($sent) {
                $html .= ' <a href="https://latitudo.bitrix24.ru/crm/lead/details/'.(int)$s['lead_id'].'/">Лид #'.(int)$s['lead_id'].'</a>';
            } elseif ($s['last_error'] !== '') {
                $html .= '<br>'.htmlspecialcharsbx($s['last_error']);
            }
            $row->AddViewField('SENT_TO_CRM', $html);
        }
    }

    public static function adminFormNote(&$tabs): void
    {
        if (!self::enabled((int)($_REQUEST['ID'] ?? 0))
            || parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) !== '/bitrix/admin/form_edit.php') return;
        CAdminMessage::ShowMessage(['TYPE' => 'OK', 'MESSAGE' =>
            'Заявки автоматически отправляются в Битрикс24 через вебхук с начальным ответственным ID 65. '
            .'Правила CRM могут затем изменить ответственного. Статус и ссылка на лид доступны в результатах формы. '
            .'Режим «вручную» ниже относится только к прежнему подключению CRM; связи полей сохранены.']);
    }
}

AddEventHandler('form', 'onAfterResultAdd', ['LatitudoFormCrm', 'afterAdd']);
AddEventHandler('main', 'OnProlog', ['LatitudoFormCrm', 'adminRequest']);
AddEventHandler('main', 'OnAdminListDisplay', ['LatitudoFormCrm', 'adminList']);
AddEventHandler('main', 'OnAdminTabControlBegin', ['LatitudoFormCrm', 'adminFormNote']);
