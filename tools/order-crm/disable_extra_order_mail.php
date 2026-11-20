<?php
// Prepared migration. check is read-only; apply/rollback require explicit production approval.
if(PHP_SAPI!=='cli')exit;
$mode=$argv[1]??'check';
if(!in_array($mode,['check','apply','rollback'],true))throw new RuntimeException('Invalid mode');
$cfg=require getenv('HOME').'/codex-stage/order-crm-20261120/local/php_interface/include/order-crm/config.php';
if(realpath($cfg['document_root'])!==$cfg['document_root'])throw new RuntimeException('Root mismatch');
$_SERVER['DOCUMENT_ROOT']=$cfg['document_root'];$_SERVER['HTTP_HOST']=$_SERVER['SERVER_NAME']=$cfg['domain'];$_SERVER['REQUEST_METHOD']='GET';
define('NO_AGENT_CHECK',true);define('NO_KEEP_STATISTIC',true);define('NOT_CHECK_PERMISSIONS',true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
$db=$GLOBALS['DB'];$expected=[29=>'SALE_NEW_ORDER',40=>'SALE_STATUS_CHANGED_F',41=>'SALE_STATUS_CHANGED_N',55=>'SALE_STATUS_CHANGED_P'];$rows=[];
foreach($expected as $id=>$event){$r=$db->Query('SELECT * FROM b_event_message WHERE ID='.(int)$id)->Fetch();if(!$r||$r['EVENT_NAME']!==$event||$r['EMAIL_TO']!=='#EMAIL#')throw new RuntimeException('Template mismatch '.$id);$rows[$id]=$r;}
$keep=$db->Query('SELECT ID,ACTIVE,EVENT_NAME,EMAIL_TO FROM b_event_message WHERE ID=54')->Fetch();
if(!$keep||$keep['ACTIVE']!=='Y'||$keep['EVENT_NAME']!=='NEW_ONE_CLICK_BUY')throw new RuntimeException('Required template missing');
if($mode==='check'){echo json_encode(['domain'=>$cfg['domain'],'disable'=>array_keys($rows),'keep'=>$keep],JSON_UNESCAPED_UNICODE).PHP_EOL;exit;}
if($mode==='apply'){
 foreach($rows as $r)if($r['ACTIVE']!=='Y')throw new RuntimeException('State changed; review before apply');
 $schema=$db->Query('SHOW CREATE TABLE b_event_message')->Fetch();
 $dir=getenv('HOME').'/codex-backups';if(!is_dir($dir)||!is_writable($dir))throw new RuntimeException('Backup directory unavailable');
 $path=$dir.'/extra-order-mail-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
 $data=json_encode(['domain'=>$cfg['domain'],'schema'=>$schema,'rows'=>$rows,'keep'=>$keep],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
 if(file_put_contents($path,$data,LOCK_EX)!==strlen($data)||hash_file('sha256',$path)!==hash('sha256',$data))throw new RuntimeException('Backup failed');chmod($path,0600);
 foreach($expected as $id=>$event){$m=new CEventMessage;if(!$m->Update($id,['ACTIVE'=>'N']))throw new RuntimeException('Update failed '.$id);}
 echo 'BACKUP '.$path.PHP_EOL;
}else{
 $path=realpath($argv[2]??'');$dir=realpath(getenv('HOME').'/codex-backups');
 if(!$path||!$dir||strpos($path,$dir.'/')!==0)throw new RuntimeException('Invalid backup path');
 $backup=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
 if(($backup['domain']??null)!==$cfg['domain'])throw new RuntimeException('Backup domain mismatch');
 foreach($expected as $id=>$event){if(($backup['rows'][$id]['EVENT_NAME']??null)!==$event||$rows[$id]['ACTIVE']!=='N')throw new RuntimeException('Rollback state mismatch');}
 foreach($expected as $id=>$event){$m=new CEventMessage;if(!$m->Update($id,['ACTIVE'=>$backup['rows'][$id]['ACTIVE']]))throw new RuntimeException('Rollback failed');}
}
foreach($expected as $id=>$event){$r=$db->Query('SELECT ID,EVENT_NAME,ACTIVE FROM b_event_message WHERE ID='.(int)$id)->Fetch();echo json_encode($r).PHP_EOL;}
$after=$db->Query('SELECT ID,ACTIVE,EVENT_NAME,EMAIL_TO FROM b_event_message WHERE ID=54')->Fetch();if($after!==$keep)throw new RuntimeException('Required template changed');
