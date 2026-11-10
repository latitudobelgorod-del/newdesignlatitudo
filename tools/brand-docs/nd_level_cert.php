<?php
/**
 * Товарам марки Level (ИБ 19, BRAND = 24134) добавить в «Документы» (INSTRUCTIONS_FILE, prop 425)
 * «Сертификат соответствия на опоры и комплектующие Level» 35443 (Ирина, 10.11.2026).
 *   php nd_level_cert.php <DOCUMENT_ROOT> dry|run|rollback ; бэкап ~/nd_brand_docs/level_cert_before.json
 */
$root=$argv[1]; $mode=$argv[2]??'dry'; $_SERVER['DOCUMENT_ROOT']=$root;
define('NO_KEEP_STATISTIC',true); define('NOT_CHECK_PERMISSIONS',true); define('NO_AGENT_CHECK',true);
require $root.'/bitrix/modules/main/include/prolog_before.php'; CModule::IncludeModule('iblock');
$IB=19; $CERT=35443; $bak=getenv('HOME').'/nd_brand_docs/level_cert_before.json';
if($mode==='rollback'){
  foreach(json_decode(file_get_contents($bak),true) as $id=>$v) CIBlockElement::SetPropertyValuesEx($id,$IB,['INSTRUCTIONS_FILE'=>$v?:false]);
  echo "restored\n"; exit;
}
$old=[]; $add=0;
$r=CIBlockElement::GetList(['ID'=>'ASC'],['IBLOCK_ID'=>$IB,'PROPERTY_BRAND'=>24134],false,false,['ID']);
while($a=$r->Fetch()){
  $v=[]; $q=CIBlockElement::GetProperty($IB,$a['ID'],['sort'=>'asc','id'=>'asc'],['CODE'=>'INSTRUCTIONS_FILE']); while($x=$q->Fetch()) if($x['VALUE']) $v[]=(int)$x['VALUE'];
  $old[$a['ID']]=$v;
  if(in_array($CERT,$v)) continue;
  $add++;
  if($mode==='run') CIBlockElement::SetPropertyValuesEx($a['ID'],$IB,['INSTRUCTIONS_FILE'=>array_merge($v,[$CERT])]);
}
echo "products ",count($old),", add $add\n";
if($mode==='run'){ if(!file_exists($bak)) file_put_contents($bak,json_encode($old)); echo "done\n"; }
