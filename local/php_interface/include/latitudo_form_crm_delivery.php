<?php
/** Delivery state machine. No Bitrix bootstrap or network calls on inclusion. */
interface LatitudoFormCrmStore
{
    public function lock(int $id): bool;
    public function unlock(int $id): void;
    public function load(int $id): ?array;
    public function save(int $id, array $state): void;
    public function result(int $id): array;
    public function payload(int $id): array;
    public function markSent(int $id): void;
}

final class LatitudoFormCrmDelivery
{
    public const FORMS = [1, 19, 21, 22, 23, 26, 27];
    public const ORIGIN = 'latitudo.ru:webform';
    private LatitudoFormCrmStore $store;
    private $request;

    public function __construct(LatitudoFormCrmStore $store, callable $request)
    {
        $this->store = $store;
        $this->request = $request;
    }

    public function send(int $id): array
    {
        if (!$this->store->lock($id)) {
            return ['status' => 'busy', 'error' => 'Заявка уже обрабатывается. Обновите список.'];
        }
        try {
            $result = $this->store->result($id);
            if (!in_array((int)($result['FORM_ID'] ?? 0), self::FORMS, true)) {
                throw new RuntimeException('Форма не подключена к REST.');
            }
            $state = $this->store->load($id);
            if (!empty($state['lead_id'])) {
                $this->store->markSent($id);
                return $state;
            }
            if (($result['SENT_TO_CRM'] ?? 'N') === 'Y') {
                return ['status' => 'legacy', 'error' => 'Заявка уже отправлена прежним подключением. Повторное создание отключено.'];
            }
            $uncertain = in_array($state['status'] ?? '', ['sending', 'uncertain'], true);
            $state = $state ?? ['status' => 'pending', 'lead_id' => 0, 'attempts' => 0, 'error' => ''];
            // Search before every attempt. Do not use phone matching: different requests
            // from the same customer must remain distinct leads.
            $found = ($this->request)('crm.lead.list', [
                'filter' => ['=ORIGINATOR_ID' => self::ORIGIN, '=ORIGIN_ID' => (int)$result['FORM_ID'].':'.$id],
                'select' => ['ID'], 'order' => ['ID' => 'ASC'],
            ]);
            if (!isset($found['result']) || !is_array($found['result'])) {
                return $this->failure($id, $state, $uncertain, $found['error_description'] ?? 'Не удалось проверить существующий лид.');
            }
            if (count($found['result']) > 1) {
                return $this->failure($id, $state, true, 'Найдено несколько лидов с ID этой заявки. Требуется проверка менеджера.');
            }
            if (count($found['result']) === 1) {
                $leadId = (int)($found['result'][0]['ID'] ?? 0);
                if ($leadId <= 0) {
                    return $this->failure($id, $state, $uncertain, 'CRM вернула некорректный ID лида.');
                }
                return $this->success($id, $state, $leadId);
            }
            if ($uncertain) {
                return $this->failure($id, $state, true,
                    'Ответ на создание лида не получен. Лид пока не найден; повторное создание заблокировано во избежание дубля.');
            }
            try {
                $fields = $this->store->payload($id);
            } catch (Throwable $e) {
                return $this->failure($id, $state, false, $e->getMessage());
            }
            $fields['ASSIGNED_BY_ID'] = 65;
            $fields['ORIGINATOR_ID'] = self::ORIGIN;
            $fields['ORIGIN_ID'] = (int)$result['FORM_ID'].':'.$id;
            $state['status'] = 'sending';
            $state['error'] = '';
            $state['attempts']++;
            // Persist BEFORE sending. A worker crash is treated as an unknown outcome.
            $this->store->save($id, $state);
            try {
                $reply = ($this->request)('crm.lead.add', ['fields' => $fields, 'params' => ['REGISTER_SONET_EVENT' => 'Y']]);
            } catch (Throwable $e) {
                return $this->failure($id, $state, true, 'Соединение прервано; результат создания лида неизвестен.');
            }
            if (isset($reply['result']) && is_scalar($reply['result']) && ctype_digit((string)$reply['result']) && (int)$reply['result'] > 0) {
                return $this->success($id, $state, (int)$reply['result']);
            }
            // Only a structured Bitrix error is a definite rejection. Timeouts,
            // proxy errors and malformed responses must never trigger another add.
            return $this->failure($id, $state, empty($reply['_definite_error']),
                $reply['error_description'] ?? 'Нет подтверждения создания лида.');
        } finally {
            $this->store->unlock($id);
        }
    }

    private function success(int $id, array $state, int $leadId): array
    {
        $state['status'] = 'sent';
        $state['lead_id'] = $leadId;
        $state['error'] = '';
        // Save the ID first: retry can repair the native flag without another lead.
        $this->store->save($id, $state);
        $this->store->markSent($id);
        return $state;
    }

    private function failure(int $id, array $state, bool $uncertain, string $message): array
    {
        $state['status'] = $uncertain ? 'uncertain' : 'error';
        $state['error'] = mb_substr($message, 0, 1500);
        $this->store->save($id, $state);
        return $state;
    }

    public static function mapFields(array $form, array $result, array $answers, array $mapping): array
    {
        $byId = [];
        foreach ($answers as $rows) {
            foreach ($rows as $answer) {
                $value = (string)($answer['USER_TEXT'] ?? '');
                if ($value === '') $value = (string)($answer['ANSWER_TEXT'] ?? '');
                if ($value === '') $value = (string)($answer['VALUE'] ?? '');
                if ($value !== '') $byId[(int)$answer['FIELD_ID']][] = $value;
            }
        }
        $fields = ['SOURCE_ID' => 'WEB'];
        foreach ($mapping as $map) {
            $key = (string)$map['CRM_FIELD'];
            if ((int)$map['FIELD_ID'] > 0) {
                $values = $byId[(int)$map['FIELD_ID']] ?? [];
            } else {
                $alternatives = ['FORM_NAME' => $form['NAME'], 'FORM_SID' => $form['SID'],
                    'RESULT_ID' => $result['ID'], 'SITE_ID' => $result['SITE_ID'] ?? 's1'];
                if (!array_key_exists($map['FIELD_ALT'], $alternatives)) {
                    throw new RuntimeException('Не поддержана связь поля '.$key.': '.$map['FIELD_ALT']);
                }
                $values = [(string)$alternatives[$map['FIELD_ALT']]];
            }
            if (preg_match('/^(PHONE|EMAIL|WEB|IM)_([A-Z0-9_]+)$/D', $key, $m)) {
                foreach ($values as $value) $fields[$m[1]][] = ['VALUE' => $value, 'VALUE_TYPE' => $m[2]];
            } else {
                $fields[$key] = implode(', ', $values);
            }
        }
        if (trim($fields['TITLE'] ?? '') === '') throw new RuntimeException('Не заполнен заголовок лида. Проверьте связи полей формы.');
        return $fields;
    }
}
