<?php
require __DIR__.'/../../local/php_interface/include/latitudo_form_crm_delivery.php';

final class MemoryCrmStore implements LatitudoFormCrmStore
{
    public ?array $state = null;
    public bool $busy = false;
    public bool $released = false;
    public bool $marked = false;
    public bool $failFlag = false;
    public bool $badPayload = false;
    public array $record = ['ID' => 42, 'FORM_ID' => 1, 'SENT_TO_CRM' => 'N'];
    public function lock(int $id): bool { return !$this->busy; }
    public function unlock(int $id): void { $this->released = true; }
    public function load(int $id): ?array { return $this->state; }
    public function save(int $id, array $state): void { $this->state = $state; }
    public function result(int $id): array { return $this->record; }
    public function payload(int $id): array {
        if ($this->badPayload) throw new RuntimeException('Missing mapping');
        return ['TITLE' => 'Test', 'ASSIGNED_BY_ID' => 1];
    }
    public function markSent(int $id): void {
        if ($this->failFlag) throw new RuntimeException('Flag database failure');
        $this->marked = true;
    }
}
function check($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function delivery(MemoryCrmStore $store, array $responses, array &$calls): LatitudoFormCrmDelivery {
    return new LatitudoFormCrmDelivery($store, function($method,$params) use (&$responses,&$calls,$store) {
        $calls[] = [$method,$params];
        if ($method === 'crm.lead.add') check($store->state['status']==='sending', 'Must persist sending before HTTP');
        if (!$responses) throw new RuntimeException('Unexpected network call');
        $reply = array_shift($responses);
        if ($reply instanceof Throwable) throw $reply;
        return $reply;
    });
}
$count=0;
$s=new MemoryCrmStore();$calls=[];$d=delivery($s,[['result'=>[]],['result'=>123]],$calls);
$r=$d->send(42);check($r['lead_id']===123 && $s->marked && $s->released,'Success flag and ID');
check($calls[1][1]['fields']['ASSIGNED_BY_ID']===65,'Responsible is 65');
check($calls[1][1]['fields']['ORIGIN_ID']==='1:42','Stable request identity');
$d->send(42);check(count($calls)===2,'Retry must not call CRM after confirmed success');$count++;

$s=new MemoryCrmStore();$calls=[];$r=delivery($s,[['result'=>[['ID'=>'777']]]],$calls)->send(42);
check($r['lead_id']===777 && count($calls)===1,'Existing remote lead must be adopted');$count++;

$s=new MemoryCrmStore();$calls=[];$r=delivery($s,[['result'=>[]],['error_description'=>'Invalid phone','_definite_error'=>true]],$calls)->send(42);
check($r['status']==='error' && !$s->marked,'Validation error must not mark success');
$calls=[];$r=delivery($s,[['result'=>[]],['result'=>124]],$calls)->send(42);
check($r['lead_id']===124 && $r['attempts']===2,'Definite failure permits corrected retry');$count++;

foreach ([[], new RuntimeException('timeout')] as $lostReply) {
    $s=new MemoryCrmStore();$calls=[];$r=delivery($s,[['result'=>[]],$lostReply],$calls)->send(42);
    check($r['status']==='uncertain' && !$s->marked,'Timeout is uncertain');
    $calls=[];$r=delivery($s,[['result'=>[]]],$calls)->send(42);
    check(count($calls)===1 && $r['status']==='uncertain','Unknown outcome must never create again');
    $calls=[];$r=delivery($s,[['result'=>[['ID'=>'125']]]],$calls)->send(42);
    check($r['lead_id']===125 && count($calls)===1,'Late lead recovered after timeout');$count++;
}
$s=new MemoryCrmStore();$s->state=['status'=>'sending','lead_id'=>0,'attempts'=>1];$calls=[];
$r=delivery($s,[['result'=>[]]],$calls)->send(42);
check($r['status']==='uncertain' && count($calls)===1,'Crash during add is not retried blindly');$count++;

$s=new MemoryCrmStore();$s->busy=true;$calls=[];$r=delivery($s,[],$calls)->send(42);
check($r['status']==='busy' && !$calls,'Concurrent worker cannot create duplicate');$count++;

$s=new MemoryCrmStore();$s->record['SENT_TO_CRM']='Y';$calls=[];$r=delivery($s,[],$calls)->send(42);
check($r['status']==='legacy' && !$calls,'Do not resend historical successful results');$count++;

$s=new MemoryCrmStore();$s->record['FORM_ID']=19;$calls=[];
try { delivery($s,[],$calls)->send(42); throw new LogicException('Wrong form accepted'); }
catch (RuntimeException $e) { check(!$calls && $s->released,'Wrong form rejected and lock released'); }$count++;

$s=new MemoryCrmStore();$s->badPayload=true;$calls=[];$r=delivery($s,[['result'=>[]]],$calls)->send(42);
check($r['status']==='error' && count($calls)===1,'Mapping failure does not send');$count++;

$s=new MemoryCrmStore();$calls=[];$r=delivery($s,[['error_description'=>'Access denied']],$calls)->send(42);
check($r['status']==='error' && count($calls)===1,'Lookup failure must block add');$count++;

$s=new MemoryCrmStore();$calls=[];$r=delivery($s,[['result'=>[['ID'=>1],['ID'=>2]]]],$calls)->send(42);
check($r['status']==='uncertain' && !$s->marked,'Conflicting remote IDs require review');$count++;

$s=new MemoryCrmStore();$s->failFlag=true;$calls=[];$d=delivery($s,[['result'=>[]],['result'=>126]],$calls);
try { $d->send(42); throw new LogicException('Expected flag error'); } catch (RuntimeException $e) {}
check($s->state['lead_id']===126,'ID persisted even if native flag update fails');
$s->failFlag=false;$d->send(42);check(count($calls)===2 && $s->marked,'Repair flag without creating another lead');$count++;

$mapping=[['CRM_FIELD'=>'TITLE','FIELD_ID'=>0,'FIELD_ALT'=>'FORM_NAME'],
    ['CRM_FIELD'=>'PHONE_WORK','FIELD_ID'=>2,'FIELD_ALT'=>''],
    ['CRM_FIELD'=>'EMAIL_WORK','FIELD_ID'=>3,'FIELD_ALT'=>''],
    ['CRM_FIELD'=>'UF_CRM_TEST','FIELD_ID'=>4,'FIELD_ALT'=>'']];
$answers=['PHONE'=>[['FIELD_ID'=>2,'USER_TEXT'=>'+70000000000']],
    'EMAIL'=>[['FIELD_ID'=>3,'USER_TEXT'=>'test@example.invalid']],
    'UTM'=>[['FIELD_ID'=>4,'USER_TEXT'=>'0']]];
$fields=LatitudoFormCrmDelivery::mapFields(['NAME'=>'Товар под заказ','SID'=>'TOORDER'],['ID'=>42],$answers,$mapping);
check($fields['TITLE']==='Товар под заказ' && $fields['PHONE'][0]['VALUE_TYPE']==='WORK'
    && $fields['EMAIL'][0]['VALUE']==='test@example.invalid' && $fields['UF_CRM_TEST']==='0','Legacy mapping converted to REST without losing zero values');$count++;
echo "$count delivery scenarios passed; no network calls or real leads.\n";
