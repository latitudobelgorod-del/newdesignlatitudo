<?php
/** Actual Bitrix APIs + MySQL 5.7 temporary tables; no external requests or mail. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=realpath(__DIR__.'/../..');
if (str_replace('\\','/',$root)!=='C:/OSPanel/home/newdesign-latitudo') throw new RuntimeException('Local test only');
$_SERVER['DOCUMENT_ROOT']=$root;$_SERVER['HTTP_HOST']='latitudo-newdesign.loc';$_SERVER['REQUEST_METHOD']='GET';
define('NO_KEEP_STATISTIC',true);define('NO_AGENT_CHECK',true);define('NOT_CHECK_PERMISSIONS',true);
require $root.'/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('form');
require_once $root.'/local/php_interface/include/latitudo_form_crm.php';
$db=$GLOBALS['DB'];
function verifyCrm($condition,$message){if(!$condition)throw new RuntimeException($message);}
$link=CFormCrm::GetByFormID(1)->Fetch();
// Shadow the real tables for this connection only. They disappear on disconnect.
foreach(['b_form_result','b_form_result_answer','b_form_crm_link','b_option'] as $table){
    $schema=$db->Query('SHOW CREATE TABLE '.$table)->Fetch()['Create Table'];
    $db->Query(preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$schema));
}
$id=2147000000;
$db->Query("INSERT INTO b_form_crm_link (ID,FORM_ID,CRM_ID,LINK_TYPE) VALUES (".(int)$link['ID'].",1,3,'M')");
$db->Query("INSERT INTO b_form_result (ID,FORM_ID,STATUS_ID,DATE_CREATE,TIMESTAMP_X,SENT_TO_CRM)
    VALUES ($id,1,1,NOW(),NOW(),'N')");
$answers=[1=>'Тест интеграции',2=>'+70000000000',3=>'crm-test@example.invalid',4=>'Тестовый товар',
    6=>'Тестовое сообщение',58=>'Тестовый город',96=>'https://latitudo.ru/catalog/test/',97=>'latitudo.ru',
    111=>'test-source',112=>'test-medium',113=>'test-campaign',114=>'test-content',115=>'test-term',123=>'test-geo'];
foreach($answers as $field=>$value){
    $q=$db->Query('SELECT ID,FIELD_TYPE FROM b_form_answer WHERE FIELD_ID='.(int)$field.' ORDER BY ID LIMIT 1')->Fetch();
    if(!$q)throw new RuntimeException('Missing form answer '.$field);
    $db->Query("INSERT INTO b_form_result_answer (RESULT_ID,FORM_ID,FIELD_ID,ANSWER_ID,USER_TEXT)
        VALUES ($id,1,$field,".(int)$q['ID'].",'".$db->ForSql($value)."')");
}
$db->Query("CREATE TEMPORARY TABLE b_latitudo_form_crm (
    result_id INT UNSIGNED NOT NULL PRIMARY KEY,status VARCHAR(16) NOT NULL,
    lead_id BIGINT UNSIGNED NOT NULL DEFAULT 0,attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NOT NULL,updated_at DATETIME NOT NULL,KEY queue_status(status,updated_at)) ENGINE=InnoDB");
COption::SetOptionString('form',LatitudoFormCrm::OPTION,'Y');
LatitudoFormCrm::afterAdd(19,$id);verifyCrm(!(new LatitudoFormCrmBitrixStore())->load($id),'Other forms must not be queued');
LatitudoFormCrm::afterAdd(1,$id);$store=new LatitudoFormCrmBitrixStore();
verifyCrm($store->load($id)['status']==='pending','Actual queue insert');
$payload=$store->payload($id);
verifyCrm($payload['TITLE']==='Товар под заказ','Native form name mapping');
verifyCrm($payload['PHONE'][0]['VALUE']==='+70000000000','Native phone answer mapping');
verifyCrm($payload['COMMENTS']==='Тестовый товар','Existing COMMENTS mapping preserved');
verifyCrm($payload['UF_CRM_1670914076118']==='Тестовое сообщение','Message field preserved');
verifyCrm($payload['UF_CRM_1674473518']==='test-geo','UTM mapping preserved');
$config=include $root.'/bitrix/.settings.php';$localDb=$config['connections']['value']['default'];
verifyCrm(str_starts_with($localDb['host'],'127.'),'Peer must be local');
$peer=new mysqli($localDb['host'],$localDb['login'],$localDb['password'],$localDb['database']);
verifyCrm(!$peer->connect_errno,'Cannot open peer connection');
verifyCrm($store->lock($id),'First lock');
$locked=$peer->query("SELECT GET_LOCK('nd_form1_crm_$id',0) AS L")->fetch_assoc();
verifyCrm((int)$locked['L']===0,'MySQL lock must exclude another connection');$store->unlock($id);$peer->close();
$calls=0;
$delivery=new LatitudoFormCrmDelivery($store,function($method,$params)use(&$calls){
    $calls++;
    if($method==='crm.lead.list')return ['result'=>[]];
    verifyCrm($params['fields']['ASSIGNED_BY_ID']===65,'Assigned user');return ['result'=>987654321];
});
$delivery->send($id);$delivery->send($id);
verifyCrm($calls===2,'No duplicate REST add');
verifyCrm(CFormResult::GetByID($id)->Fetch()['SENT_TO_CRM']==='Y','Native SENT_TO_CRM flag');
verifyCrm((int)$store->load($id)['lead_id']===987654321,'Persisted lead ID');
class CrmTestRow {public $id;public $fields=[];public function AddViewField($key,$value){$this->fields[$key]=$value;}}
$row=new CrmTestRow();$row->id=$id;$list=(object)['table_id'=>'tbl_form_result_list_'.md5('1'),'aRows'=>[$row]];
LatitudoFormCrm::adminList($list);
verifyCrm(strpos($row->fields['SENT_TO_CRM'],'/crm/lead/details/987654321/')!==false,'Lead link in actual list hook');
$store->save($id,['status'=>'error','lead_id'=>0,'attempts'=>1,'error'=>'<script>alert(1)</script>']);
LatitudoFormCrm::adminList($list);
verifyCrm(strpos($row->fields['SENT_TO_CRM'],'<script>')===false && strpos($row->fields['SENT_TO_CRM'],'&lt;script&gt;')!==false,'CRM errors must be escaped');
echo "Bitrix/MySQL integration OK: temporary results, native field mapping, queue, cross-connection lock, native CRM flag, lead link, escaped errors. No real leads or mail.\n";
