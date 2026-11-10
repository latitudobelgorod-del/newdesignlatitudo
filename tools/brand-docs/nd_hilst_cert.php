<?php
/**
 * «Сертификат соответствия на алюминиевые профили HILST» 35442 — в «Документы» (INSTRUCTIONS_FILE)
 * товаров, перечисленных в самом сертификате (Ирина, 10.11.2026). Сверка по названиям:
 *   столб 100х100 24436, поперечный профиль 75х25 24453, П-профиль 31х30 24444, заглушка столба 30х19 24441,
 *   профиль для стекла 25х25 24450, лага JOIST PRO PREMIUM 60х40 → 25614 и старая 23700 Professional 40х60,
 *   JOIST SLIM PREMIUM 60х20 → 25616, JOIST SLIM 50х20 → 24710 и старая 23697, лага 25х35 → 27314,
 *   F-профиль универсальный → 28141 и старый 26329.
 *   Нет в каталоге: армирующий профиль 15х15, лага 40х35, уголок 60х40.
 *   php nd_hilst_cert.php <DOCUMENT_ROOT> dry|run|rollback ; бэкап ~/nd_brand_docs/hilst_cert_before.json
 */
$root=$argv[1]; $mode=$argv[2]??'dry'; $_SERVER['DOCUMENT_ROOT']=$root;
define('NO_KEEP_STATISTIC',true); define('NOT_CHECK_PERMISSIONS',true); define('NO_AGENT_CHECK',true);
require $root.'/bitrix/modules/main/include/prolog_before.php'; CModule::IncludeModule('iblock');
$IB=19; $CERT=35442; $bak=getenv('HOME').'/nd_brand_docs/hilst_cert_before.json';
$IDS=[24436,24453,24444,24441,24450,25614,23700,25616,24710,23697,27314,28141,26329];
if($mode==='rollback'){
  foreach(json_decode(file_get_contents($bak),true) as $id=>$v) CIBlockElement::SetPropertyValuesEx($id,$IB,['INSTRUCTIONS_FILE'=>$v?:false]);
  echo "restored\n"; exit;
}
$old=[]; $add=0;
$r=CIBlockElement::GetList(['ID'=>'ASC'],['IBLOCK_ID'=>$IB,'ID'=>$IDS],false,false,['ID']);
while($a=$r->Fetch()){
  $v=[]; $q=CIBlockElement::GetProperty($IB,$a['ID'],['sort'=>'asc','id'=>'asc'],['CODE'=>'INSTRUCTIONS_FILE']); while($x=$q->Fetch()) if($x['VALUE']) $v[]=(int)$x['VALUE'];
  $old[$a['ID']]=$v;
  if(in_array($CERT,$v)) continue;
  $add++;
  if($mode==='run') CIBlockElement::SetPropertyValuesEx($a['ID'],$IB,['INSTRUCTIONS_FILE'=>array_merge($v,[$CERT])]);
}
echo "products ",count($old),", add $add\n";
if($mode==='run'){ if(!file_exists($bak)) file_put_contents($bak,json_encode($old)); echo "done\n"; }
