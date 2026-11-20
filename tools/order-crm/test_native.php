<?php
if(PHP_SAPI!=='cli')exit;
$codeRoot=realpath(__DIR__.'/../..');$cfg=require $codeRoot.'/local/php_interface/include/order-crm/config.php';
if(!str_contains($codeRoot,'/codex-stage/')||posix_getpwuid(posix_geteuid())['name']!==$cfg['user'])throw new RuntimeException('Private staging only');
$root=$cfg['document_root'];$_SERVER['DOCUMENT_ROOT']=$root;$_SERVER['HTTP_HOST']=$_SERVER['SERVER_NAME']=$cfg['dealer_rules']?'vrn.easydecking.ru':$cfg['domain'];$_SERVER['REQUEST_METHOD']='GET';
define('NO_AGENT_CHECK',true);define('NO_KEEP_STATISTIC',true);define('NOT_CHECK_PERMISSIONS',true);
require $root.'/bitrix/modules/main/include/prolog_before.php';require $codeRoot.'/local/php_interface/include/order-crm/OrderCrm.php';CModule::IncludeModule('sale');
function testOrder($ok,$text){if(!$ok)throw new RuntimeException($text);}
$db=$GLOBALS['DB'];
// The option is overridden ONLY in this PHP process. Never call Option::set or touch shared option caches.
SiteOrderCrm::enabled();$prop=new ReflectionProperty('Bitrix\Main\Config\Option','options');$prop->setAccessible(true);$options=$prop->getValue();$options['sale']['-']['site_order_crm_enabled']='Y';$options['sale']['s1']['site_order_crm_enabled']='Y';$prop->setValue(null,$options);
testOrder(SiteOrderCrm::enabled(),'In-memory activation');
$db->Query("CREATE TEMPORARY TABLE b_site_order_crm (order_id INT UNSIGNED NOT NULL PRIMARY KEY,status VARCHAR(16) NOT NULL,lead_id BIGINT UNSIGNED NOT NULL DEFAULT 0,attempts INT UNSIGNED NOT NULL DEFAULT 0,last_error TEXT NOT NULL,payload_json MEDIUMTEXT NOT NULL,context_json TEXT NOT NULL,updated_at DATETIME NOT NULL,KEY queue_status(status,updated_at)) ENGINE=InnoDB");
$id=(int)$db->Query('SELECT MAX(ID) AS ID FROM b_sale_order')->Fetch()['ID'];testOrder($id>0,'Existing order for read-only lookup');
$fields=['TITLE'=>'TEST — do not process','NAME'=>'TEST','PHONE'=>[['VALUE'=>'+70000000000','VALUE_TYPE'=>'WORK']],'UF_CRM_1670914333154'=>'wrong.example','UF_CRM_1670914240846'=>'https://'.$_SERVER['HTTP_HOST'].'/basket/','COMMENTS'=>'Test items'];
SiteOrderCrm::enqueue($id,$fields,22512);$store=new SiteOrderCrmStore;$s=$store->load($id);testOrder($s&&$s['status']==='pending','Enqueue');
$payload=$store->payload($id,$s);testOrder($payload['OPPORTUNITY']===22512&&$payload['UF_CRM_1670914333154']===$_SERVER['HTTP_HOST'],'Amount and actual domain');
SiteOrderCrm::enqueue($id,['TITLE'=>'Changed'],1);testOrder($store->payload($id,$store->load($id))['TITLE']===$fields['TITLE'],'No overwriting original payload');
$calls=[];$engine=new SiteOrderDelivery($store,function($method,$params)use(&$calls){$calls[]=$method;if($method==='crm.lead.add')testOrder($params['fields']['ASSIGNED_BY_ID']===65,'Responsible');return ['result'=>$method==='crm.lead.add'?987654321:[]];},$cfg['domain'].':order');
$r=$engine->send($id);$engine->send($id);testOrder((int)$r['lead_id']===987654321&&count($calls)===2,'Confirmed ID and idempotence');
testOrder(str_contains(SiteOrderCrm::statusHtml($store->load($id)),'/crm/lead/details/987654321/'),'Admin lead link');
testOrder(!str_contains(SiteOrderCrm::statusHtml(['status'=>'error','lead_id'=>0,'error'=>'<script>alert(1)</script>']),'<script>'),'Escaped errors');
testOrder(str_contains(SiteOrderCrm::statusHtml(null),'Старые заказы'),'Historical state not fabricated');
if($cfg['dealer_rules']){
 testOrder(SiteOrderCrm::regionAllowed(34735)===false,'Anapa blocked');testOrder(SiteOrderCrm::regionAllowed(9278)===true,'Voronezh allowed');testOrder(SiteOrderCrm::regionAllowed(9277)===true,'Non-dealer independent of REGION_MY');
 $s['context_json']=json_encode(['host'=>'anapa.easydecking.ru','region_id'=>34735,'allowed'=>true]);$s['status']='pending';testOrder($store->routeBlock($id,$s)['status']==='skipped','Recheck current dealer prohibition');
}
echo 'NATIVE_ORDER_TEST_OK '.$cfg['domain'].': temporary queue, process-local option, no live order changes, no HTTP calls, no mail'."\n";
