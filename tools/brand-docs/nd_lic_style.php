<?php
// Страница «Сертификаты и лицензии» (ИБ 25, эл. 25102, EDITOR1): блоку iblock_elements(13)
// ставим вариант param1=style5 — табы по производителям, вывод по галочке SERT_LICENSE (10.11.2026).
// php nd_lic_style.php <DOCUMENT_ROOT> dry|run|rollback ; бэкап ~/nd_brand_docs/lic_editor1_before.json
$root=$argv[1]; $mode=$argv[2]??'dry'; $_SERVER['DOCUMENT_ROOT']=$root;
define('NO_KEEP_STATISTIC',true); define('NOT_CHECK_PERMISSIONS',true); define('NO_AGENT_CHECK',true);
require $root.'/bitrix/modules/main/include/prolog_before.php'; CModule::IncludeModule('iblock');
$ID=25102; $IB=25; $bak=getenv('HOME').'/nd_brand_docs/lic_editor1_before.json';
if($mode==='rollback'){ CIBlockElement::SetPropertyValuesEx($ID,$IB,['EDITOR1'=>file_get_contents($bak)]); CIBlock::clearIblockTagCache($IB); echo "restored\n"; exit; }
$p=CIBlockElement::GetProperty($IB,$ID,[],['CODE'=>'EDITOR1'])->Fetch();
$raw=$p['VALUE']; if(is_array($raw)) $raw=$raw['TEXT']??''; 
$j=json_decode($raw,true); if(!$j) die("bad json\n");
$n=0;
foreach($j['blocks'] as &$b){
  if(($b['name']??'')==='iblock_elements' && (int)($b['iblock_id']??0)===13){
    echo "block: param1=",json_encode($b['settings']??null,JSON_UNESCAPED_UNICODE)," ids=",count($b['element_ids']??[]),"\n";
    $b['settings']=is_array($b['settings']??null)?$b['settings']:[]; $b['settings']['param1']='style5'; $n++;
  }
}
unset($b);
echo "found $n\n";
if($mode!=='run'||$n!==1) exit;
if(!file_exists($bak)) file_put_contents($bak,$raw);
CIBlockElement::SetPropertyValuesEx($ID,$IB,['EDITOR1'=>json_encode($j,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
CIBlock::clearIblockTagCache($IB); echo "done\n";
