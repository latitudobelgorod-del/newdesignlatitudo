<?php
class MemoryMultiFormStore implements LatitudoFormCrmStore {
 private int $form;private ?array $state=null;
 function __construct($form){$this->form=$form;}
 function lock(int $id):bool{return true;}function unlock(int $id):void{}
 function load(int $id):?array{return $this->state;}function save(int $id,array $state):void{$this->state=$state;}
 function result(int $id):array{return ['FORM_ID'=>$this->form,'ID'=>$id,'SENT_TO_CRM'=>'N'];}
 function payload(int $id):array{return ['TITLE'=>'Test'];}function markSent(int $id):void{}
}

$audit=json_decode(file_get_contents(__DIR__.'/mappings.json'),true);
foreach($audit as $formId=>$config){
 if($formId===1)continue;
 $fid=(int)$formId;$rid=$id+$fid;
 $db->Query("INSERT INTO b_form_crm_link (ID,FORM_ID,CRM_ID,LINK_TYPE) VALUES (".(100+$fid).",$fid,".(int)$config['crm'].",'M')");
 // Use actual local mapping IDs; the online audited mapping is tested separately below.
 $original=$db->Query('SELECT ID FROM b_form_crm WHERE ID='.(int)$config['crm'])->Fetch();
 $db->Query("INSERT INTO b_form_result (ID,FORM_ID,STATUS_ID,DATE_CREATE,TIMESTAMP_X,SENT_TO_CRM) VALUES ($rid,$fid,1,NOW(),NOW(),'N')");
 COption::SetOptionString('form','nd_form'.$fid.'_rest_enabled','Y');
 LatitudoFormCrm::afterAdd($fid,$rid);verifyCrm($store->load($rid)['status']==='pending','Queue form '.$fid);
 $synthetic=[];
 foreach($config['fields'] as $map){if((int)$map['FIELD_ID'])$synthetic['f'.$map['FIELD_ID']][]=['FIELD_ID'=>$map['FIELD_ID'],'USER_TEXT'=>'test-'.$map['FIELD_ID']];}
 $mapped=LatitudoFormCrmDelivery::mapFields(['NAME'=>'Test '.$fid,'SID'=>$config['sid']],['ID'=>$rid],$synthetic,$config['fields']);
 verifyCrm(!empty($mapped['TITLE']) && !empty($mapped['PHONE']),'Audited field mapping '.$fid);
 $stateStore=new MemoryMultiFormStore($fid);
 $requests=[];
 $d=new LatitudoFormCrmDelivery($stateStore,function($method,$params)use(&$requests,$fid,$rid){$requests[]=$method;verifyCrm(($method==='crm.lead.add'?$params['fields']['ORIGIN_ID']:$params['filter']['=ORIGIN_ID'])===$fid.':'.$rid,'Per-form origin');return ['result'=>$method==='crm.lead.add'?900000+$fid:[]];});
 $d->send($rid);$d->send($rid);verifyCrm(count($requests)===2,'No duplicate for form '.$fid);
 $row->id=$rid;$list->table_id='tbl_form_result_list_'.md5((string)$fid);LatitudoFormCrm::adminList($list);verifyCrm(strpos($row->fields['SENT_TO_CRM'],'Ожидает отправки')!==false,'List form '.$fid);
}
echo "All seven form mappings, activation, queue, list status and per-form idempotency passed.\n";
