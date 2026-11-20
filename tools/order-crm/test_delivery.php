<?php
require __DIR__.'/../../local/php_interface/include/order-crm/OrderDelivery.php';
class OrderTestStore {
    public $state=['status'=>'pending','lead_id'=>0,'attempts'=>0,'error'=>''];
    public $blocked=null;public $busy=false;public $released=false;
    function lock($id){return !$this->busy;}function unlock($id){$this->released=true;}
    function load($id){return $this->state;}function save($id,$s){$this->state=$s;}
    function routeBlock($id,$s){return $this->blocked;}
    function payload($id,$s){return ['TITLE'=>'Test order','OPPORTUNITY'=>22512,'ASSIGNED_BY_ID'=>1];}
}
function ok($value,$message){if(!$value)throw new RuntimeException($message);}
function engine($store,$replies,&$calls,$origin='easydecking.ru:order'){
    return new SiteOrderDelivery($store,function($method,$params)use($store,&$replies,&$calls){
        $calls[]=[$method,$params];
        if($method==='crm.lead.add')ok($store->state['status']==='sending','Persist before network');
        if(!$replies)throw new RuntimeException('Unexpected request');
        $r=array_shift($replies);if($r instanceof Throwable)throw $r;return $r;
    },$origin);
}
$n=0;
foreach(['easydecking.ru:order','latitudo.ru:order','latitudo.org:order'] as $origin){
 $s=new OrderTestStore;$c=[];$d=engine($s,[['result'=>[]],['result'=>123]],$c,$origin);$r=$d->send(1649);$d->send(1649);
 ok($r['lead_id']===123&&count($c)===2&&$s->released,'Success without duplicate');
 ok($c[1][1]['fields']['ASSIGNED_BY_ID']===65&&$c[1][1]['fields']['ORIGINATOR_ID']===$origin&&$c[1][1]['fields']['ORIGIN_ID']==='1649','Isolated site/order identity and responsible');$n++;
}
foreach(['skipped','error'] as $status){$s=new OrderTestStore;$s->blocked=['status'=>$status,'error'=>'Blocked'];$c=[];$d=engine($s,[],$c);ok($d->send(1649)['status']===$status&&!$c,'Routing must block all calls');$n++;}
foreach([[],new RuntimeException('Timeout')] as $reply){
 $s=new OrderTestStore;$c=[];$r=engine($s,[['result'=>[]],$reply],$c)->send(1649);ok($r['status']==='uncertain','Lost reply must be uncertain');
 $c=[];$r=engine($s,[['result'=>[]]],$c)->send(1649);ok(count($c)===1&&$r['status']==='uncertain','No blind repeat');
 $s->blocked=['status'=>'skipped','error'=>'Policy changed'];$c=[];$r=engine($s,[['result'=>[['ID'=>'123']]]],$c)->send(1649);ok($r['lead_id']===123&&count($c)===1,'Reconcile late lead despite changed policy');$n++;
}
$s=new OrderTestStore;$c=[];$r=engine($s,[['result'=>[]],['_definite_error'=>true,'error_description'=>'Validation']],$c)->send(1);ok($r['status']==='error','Definite rejection');
$c=[];$r=engine($s,[['result'=>[]],['result'=>124]],$c)->send(1);ok($r['lead_id']===124&&$r['attempts']===2,'Corrected explicit failure can retry');$n++;
$s=new OrderTestStore;$s->state=null;$c=[];try{engine($s,[],$c)->send(1649);throw new LogicException('Missing record accepted');}catch(RuntimeException $e){ok(!$c&&$s->released,'No automatic historical replay');}$n++;
$s=new OrderTestStore;$s->busy=true;$c=[];ok(engine($s,[],$c)->send(1)['status']==='busy'&&!$c,'Concurrent exclusion');$n++;
$s=new OrderTestStore;$c=[];ok(engine($s,[['result'=>[['ID'=>1],['ID'=>2]]]],$c)->send(1)['status']==='uncertain','Conflicting IDs');$n++;
echo "$n order delivery scenarios passed; no real HTTP calls.\n";
