<?php
require_once __DIR__.'/OrderDelivery.php';

final class SiteOrderCrmStore
{
    public function lock(int $id): bool {return (int)$GLOBALS['DB']->Query("SELECT GET_LOCK('site_order_crm_$id',0) AS L")->Fetch()['L']===1;}
    public function unlock(int $id): void {$GLOBALS['DB']->Query("SELECT RELEASE_LOCK('site_order_crm_$id')");}
    public function load(int $id): ?array {
        $r=$GLOBALS['DB']->Query('SELECT * FROM b_site_order_crm WHERE order_id='.$id)->Fetch();
        if($r)$r['error']=$r['last_error'];return $r?:null;
    }
    public function save(int $id,array $s): void {
        $db=$GLOBALS['DB'];$status=$db->ForSql($s['status']);$error=$db->ForSql($s['error']??'');
        $db->Query("UPDATE b_site_order_crm SET status='$status',last_error='$error',lead_id=".(int)$s['lead_id'].",attempts=".(int)$s['attempts'].",updated_at=NOW() WHERE order_id=$id");
    }
    public function routeBlock(int $id,array $s): ?array {
        if(!SiteOrderCrm::enabled())return ['status'=>'error','error'=>'Отправка заказов в CRM выключена.'];
        if($s['status']==='skipped')return ['status'=>'skipped','error'=>'Отправка заказа в общую CRM запрещена для исходного региона.'];
        $context=json_decode($s['context_json'],true);
        if(!is_array($context)||($context['allowed']??null)!==true)return ['status'=>'error','error'=>'Не подтверждены исходный сайт и разрешение отправки заказа.'];
        if(SiteOrderCrm::config()['dealer_rules']){
            $allowed=SiteOrderCrm::regionAllowed((int)($context['region_id']??0));
            if($allowed===false)return ['status'=>'skipped','error'=>'Для исходного дилерского региона отправка в CRM отключена.'];
            if($allowed!==true)return ['status'=>'error','error'=>'Исходный регион недоступен.'];
        }
        if(!CModule::IncludeModule('sale')||!CSaleOrder::GetByID($id))return ['status'=>'error','error'=>'Исходный заказ не найден.'];
        return null;
    }
    public function payload(int $id,array $s): array {
        $payload=json_decode($s['payload_json'],true,512,JSON_THROW_ON_ERROR);
        if(!is_array($payload))throw new RuntimeException('Invalid payload');return $payload;
    }
}

final class SiteOrderCrm
{
    public const AGENT='SiteOrderCrm::runQueue();';
    public static function config(): array {return require __DIR__.'/config.php';}
    public static function enabled(): bool {return COption::GetOptionString('sale','site_order_crm_enabled','N')==='Y';}
    public static function regionAllowed(int $id): ?bool {
        if($id<=0||!CModule::IncludeModule('iblock'))return null;
        if(!CIBlockElement::GetList([],['IBLOCK_ID'=>7,'ID'=>$id,'ACTIVE'=>'Y'],false,['nTopCount'=>1],['ID'])->Fetch())return null;
        $v=[];foreach(['REGION_FOR_DILER','REGION_MY'] as $code){$p=CIBlockElement::GetProperty(7,$id,[],['CODE'=>$code])->Fetch();if(!$p)return null;$v[$code]=!empty($p['VALUE']);}
        return !$v['REGION_FOR_DILER']||$v['REGION_MY'];
    }
    public static function enqueue(int $id,array $fields,float $amount): void {
        if(!self::enabled()||$id<=0)return;
        try {
            $cfg=self::config();$db=$GLOBALS['DB'];$host=strtolower((string)($_SERVER['HTTP_HOST']??''));
            $host=preg_replace('/:\d+$/','',$host);
            $valid=$host===$cfg['domain']||str_ends_with($host,'.'.$cfg['domain']);
            $region=0;$allowed=$valid?true:null;
            if($cfg['dealer_rules']){
                require_once $_SERVER['DOCUMENT_ROOT'].'/local/templates/aspro_next/diler_region.php';
                $region=(int)easydeckingCurrentRegionId();$allowed=$valid?self::regionAllowed($region):null;
            }
            // Host comes from the request, not a hidden form field. Preserve the submitted page only on the same host.
            $page=(string)($fields['UF_CRM_1670914240846']??'');
            $pageHost=strtolower((string)parse_url($page,PHP_URL_HOST));
            if($pageHost!==$host||!in_array(parse_url($page,PHP_URL_SCHEME),['http','https'],true))$page='';
            $fields['UF_CRM_1670914333154']=$host;
            $fields['UF_CRM_1670914240846']=$page;
            $fields['OPPORTUNITY']=$amount;
            $fields['CURRENCY_ID']='RUB';
            $fields['ASSIGNED_BY_ID']=65;
            $fields['COMMENTS']=($fields['COMMENTS']??'')."\nЗаказ сайта №".$id;
            if(!is_finite($amount)||$amount<0)throw new RuntimeException('Invalid amount');
            $context=['host'=>$host,'region_id'=>$region,'allowed'=>$allowed];
            $status=$allowed===true?'pending':($allowed===false?'skipped':'error');
            $error=$allowed===false?'Отправка заказа в общую CRM запрещена для дилерского региона.':($allowed===null?'Не подтверждены сайт или регион заказа.':'');
            $payload=$db->ForSql(json_encode($fields,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
            $ctx=$db->ForSql(json_encode($context,JSON_THROW_ON_ERROR));$error=$db->ForSql($error);
            $db->Query("INSERT IGNORE INTO b_site_order_crm (order_id,status,lead_id,attempts,last_error,payload_json,context_json,updated_at) VALUES ($id,'$status',0,0,'$error','$payload','$ctx',NOW())");
        }catch(Throwable $e){AddMessage2Log('Order CRM queue insert failed; saved order #'.$id.' requires verification.','site.order.crm');}
    }
    public static function request(string $method,array $params): array {
        if(!in_array($method,['crm.lead.list','crm.lead.add'],true))throw new RuntimeException('Unsupported method');
        $cfg=self::config();$secret=@include dirname(dirname($_SERVER['DOCUMENT_ROOT'])).'/'.$cfg['secret_file'];
        $hook=is_array($secret)?($secret['webhook']??''):'';
        if(!preg_match('~^https://latitudo\.bitrix24\.ru/rest/\d+/[a-zA-Z0-9]+/$~D',$hook))return ['_definite_error'=>true,'error_description'=>'Вебхук не настроен.'];
        $ch=curl_init($hook.$method.'.json');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($params),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        $body=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);$r=is_string($body)?json_decode($body,true):null;
        if(!is_array($r))return ['error_description'=>'Нет корректного ответа CRM (HTTP '.$http.').'];
        if(isset($r['error'])&&is_string($r['error'])&&$http<500){$error=str_replace($hook,'[webhook]',(string)($r['error_description']??$r['error']));$error=preg_replace('~https?://\S+~u','[URL]',$error);return ['_definite_error'=>true,'error_description'=>mb_substr($error,0,1500)];}
        if($http!==200)return ['error_description'=>'Неожиданный ответ CRM (HTTP '.$http.').'];return $r;
    }
    public static function delivery(): SiteOrderDelivery {return new SiteOrderDelivery(new SiteOrderCrmStore,[self::class,'request'],self::config()['domain'].':order');}
    public static function runQueue(): string {
        if(!self::enabled())return self::AGENT;
        $q=$GLOBALS['DB']->Query("SELECT order_id FROM b_site_order_crm WHERE status='pending' OR (status IN ('sending','uncertain') AND updated_at<DATE_SUB(NOW(),INTERVAL 5 MINUTE)) ORDER BY updated_at,order_id LIMIT 3");
        while($r=$q->Fetch()){try{self::delivery()->send((int)$r['order_id']);}catch(Throwable $e){AddMessage2Log('Order CRM worker failed for #'.(int)$r['order_id'],'site.order.crm');}}
        return self::AGENT;
    }
    public static function statusHtml(?array $s): string {
        if(!$s)return 'Нет подтверждённой связи с CRM. Старые заказы автоматически не переотправляются.';
        $labels=['pending'=>'Ожидает отправки','sending'=>'Отправляется','sent'=>'Отправлено','error'=>'Ошибка отправки','uncertain'=>'Результат отправки уточняется','skipped'=>'CRM отключена для дилерского региона'];
        $text=htmlspecialcharsbx($labels[$s['status']]??'Неизвестный статус');
        if((int)$s['lead_id']>0)$text.=' — <a href="https://latitudo.bitrix24.ru/crm/lead/details/'.(int)$s['lead_id'].'/">Лид #'.(int)$s['lead_id'].'</a>';
        elseif(!empty($s['error']))$text.='<br>'.htmlspecialcharsbx($s['error']);
        return $text;
    }
    public static function adminNote(&$tabs): void {
        if(!defined('ADMIN_SECTION')||ADMIN_SECTION!==true||!self::enabled())return;
        $path=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
        if(!in_array($path,['/bitrix/admin/sale_order_view.php','/bitrix/admin/sale_order_edit.php'],true))return;
        $id=(int)($_REQUEST['ID']??0);if($id<=0)return;
        static $shown=false;if($shown)return;$shown=true;
        $s=(new SiteOrderCrmStore)->load($id);
        CAdminMessage::ShowMessage(['TYPE'=>'OK','MESSAGE'=>'Отправка заказа в Битрикс24','DETAILS'=>self::statusHtml($s),'HTML'=>true]);
    }
}
AddEventHandler('main','OnAdminTabControlBegin',[SiteOrderCrm::class,'adminNote']);
