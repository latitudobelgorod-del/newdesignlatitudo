<?php
// Выгрузка для таблицы сверки: наши товары 4SIS (ID, активность, код 1С, название, адрес, цена BASE, артикул производителя)
$_SERVER["DOCUMENT_ROOT"]=getenv("HOME")."/www/latitudo.ru";define("NO_KEEP_STATISTIC",true);define("NOT_CHECK_PERMISSIONS",true);
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";\Bitrix\Main\Loader::includeModule("iblock");\Bitrix\Main\Loader::includeModule("catalog");
$r=CIBlockElement::GetList(["ID"=>"ASC"],["IBLOCK_ID"=>19,"PROPERTY_BRAND"=>28619],false,false,["ID","IBLOCK_ID","ACTIVE","XML_ID","NAME","DETAIL_PAGE_URL","CATALOG_GROUP_1","IBLOCK_SECTION_ID"]);
while($e=$r->GetNext()){$v="";$p=CIBlockElement::GetProperty(19,$e["ID"],[],["CODE"=>"CML2_ATTRIBUTES"]);while($x=$p->Fetch())if(trim($x["DESCRIPTION"])==="Артикул производителя")$v=$x["VALUE"];
$s=CIBlockSection::GetByID($e["IBLOCK_SECTION_ID"])->Fetch();
echo implode("\t",[$e["ID"],$e["ACTIVE"],$e["XML_ID"],$e["~NAME"],"https://latitudo.ru".$e["~DETAIL_PAGE_URL"],$e["CATALOG_PRICE_1"],$v,$s["NAME"]]),"\n";}
