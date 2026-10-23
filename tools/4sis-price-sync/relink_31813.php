<?php
// 23.10.2026: «Бордо» 31813 назван как серый RAL7022, а привязан к коду бежево-серого RAL7006 (УТ-00009504).
// Перепривязать к УТ-00007710 (BORE-CH-001 RAL7022) и поправить артикул производителя. rollback — вернуть.
$_SERVER["DOCUMENT_ROOT"]=getenv("HOME")."/www/latitudo.ru";define("NO_KEEP_STATISTIC",true);define("NOT_CHECK_PERMISSIONS",true);
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";\Bitrix\Main\Loader::includeModule("iblock");
$back=($argv[1]??"")==="rollback";
$xml=$back?"УТ-00009504":"УТ-00007710";$art=$back?"BORE-CH-001 (RAL7006) Mua S-G-brown(beige160)":"BORE-CH-001 RAL7022 Mua grey(D-gray027)";
$el=new CIBlockElement();echo $el->Update(31813,["XML_ID"=>$xml])?"XML_ID=$xml\n":$el->LAST_ERROR;
$a=[];$p=CIBlockElement::GetProperty(19,31813,["sort"=>"asc","value_id"=>"asc"],["CODE"=>"CML2_ATTRIBUTES"]);
while($x=$p->Fetch()) if($x["VALUE"]!=="") $a[]=["VALUE"=>trim($x["DESCRIPTION"])==="Артикул производителя"?$art:$x["VALUE"],"DESCRIPTION"=>$x["DESCRIPTION"]];
CIBlockElement::SetPropertyValuesEx(31813,19,["CML2_ATTRIBUTES"=>$a]);echo "артикул=$art\n";
