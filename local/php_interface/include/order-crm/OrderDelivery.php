<?php
/** Independent delivery engine: store callbacks persist state before every remote mutation. */
final class SiteOrderDelivery
{
    private $store;
    private $request;
    private string $origin;

    public function __construct(object $store, callable $request, string $origin)
    {
        $this->store = $store;
        $this->request = $request;
        $this->origin = $origin;
    }

    public function send(int $id): array
    {
        if (!$this->store->lock($id)) return ['status'=>'busy', 'error'=>'Заказ уже обрабатывается.'];
        try {
            $s = $this->store->load($id);
            if (!$s) throw new RuntimeException('Заказ не поставлен в очередь. Старые заказы автоматически не отправляются.');
            if (!empty($s['lead_id'])) return $s;
            $uncertain = in_array($s['status'], ['sending','uncertain'], true);
            if (!$uncertain) {
                try { $block = $this->store->routeBlock($id, $s); }
                catch (Throwable $e) { $block = ['status'=>'error','error'=>'Не удалось проверить правила отправки заказа.']; }
                if ($block) return $this->save($id, $s, $block['status'], $block['error']);
            }
            try {
                $found = ($this->request)('crm.lead.list', [
                    'filter'=>['=ORIGINATOR_ID'=>$this->origin, '=ORIGIN_ID'=>(string)$id],
                    'select'=>['ID'], 'order'=>['ID'=>'ASC'],
                ]);
            } catch (Throwable $e) {
                return $this->save($id,$s,$uncertain?'uncertain':'error','Не удалось проверить существующий лид.');
            }
            if (!isset($found['result']) || !is_array($found['result'])) {
                return $this->save($id,$s,$uncertain?'uncertain':'error',$found['error_description']??'Ошибка проверки CRM.');
            }
            if (count($found['result']) > 1) return $this->save($id,$s,'uncertain','Найдено несколько лидов этого заказа. Нужна ручная проверка.');
            if (count($found['result']) === 1) {
                $lead=(int)($found['result'][0]['ID']??0);
                if ($lead<=0) return $this->save($id,$s,$uncertain?'uncertain':'error','Некорректный ID лида в ответе CRM.');
                return $this->success($id,$s,$lead);
            }
            if ($uncertain) return $this->save($id,$s,'uncertain','Ответ на создание не получен; повторное создание запрещено. Проверяется наличие лида.');
            try { $fields=$this->store->payload($id,$s); }
            catch (Throwable $e) { return $this->save($id,$s,'error','Данные заказа недоступны или некорректны.'); }
            if (trim((string)($fields['TITLE']??''))==='') return $this->save($id,$s,'error','Не заполнен заголовок лида.');
            $fields['ASSIGNED_BY_ID']=65;
            $fields['ORIGINATOR_ID']=$this->origin;
            $fields['ORIGIN_ID']=(string)$id;
            $s['attempts']=(int)$s['attempts']+1;
            $s=$this->save($id,$s,'sending','');
            try { $reply=($this->request)('crm.lead.add',['fields'=>$fields,'params'=>['REGISTER_SONET_EVENT'=>'Y']]); }
            catch (Throwable $e) { return $this->save($id,$s,'uncertain','Соединение прервано; результат создания неизвестен.'); }
            if (isset($reply['result']) && is_scalar($reply['result']) && ctype_digit((string)$reply['result']) && (int)$reply['result']>0) {
                return $this->success($id,$s,(int)$reply['result']);
            }
            return $this->save($id,$s,empty($reply['_definite_error'])?'uncertain':'error',$reply['error_description']??'Нет подтверждения создания лида.');
        } finally { $this->store->unlock($id); }
    }

    private function success(int $id, array $s, int $lead): array
    {
        $s['lead_id']=$lead;
        return $this->save($id,$s,'sent','');
    }

    private function save(int $id, array $s, string $status, string $error): array
    {
        $s['status']=$status;
        $s['error']=mb_substr($error,0,1500);
        $this->store->save($id,$s);
        return $s;
    }
}
