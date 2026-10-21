<?php
$_SERVER["DOCUMENT_ROOT"]=getcwd(); define("NO_KEEP_STATISTIC",1); define("NOT_CHECK_PERMISSIONS",1);
require "bitrix/modules/main/include/prolog_before.php"; CModule::IncludeModule("iblock"); CModule::IncludeModule("catalog");
$root=CIBlockSection::GetByID(104)->Fetch(); $out=["sections"=>[],"items"=>[]];
$r=CIBlockSection::GetList(["LEFT_MARGIN"=>"ASC"],["IBLOCK_ID"=>19,"ACTIVE"=>"Y",">=LEFT_MARGIN"=>$root["LEFT_MARGIN"],"<=RIGHT_MARGIN"=>$root["RIGHT_MARGIN"]],false,["ID","NAME","CODE","DEPTH_LEVEL","IBLOCK_SECTION_ID","SECTION_PAGE_URL"]);
while($s=$r->GetNext()) $out["sections"][]=["id"=>(int)$s["ID"],"n"=>$s["~NAME"],"code"=>$s["CODE"],"d"=>(int)$s["DEPTH_LEVEL"],"p"=>(int)$s["IBLOCK_SECTION_ID"],"url"=>$s["SECTION_PAGE_URL"]];
$e=CIBlockElement::GetList(["NAME"=>"ASC"],["IBLOCK_ID"=>19,"SECTION_ID"=>104,"INCLUDE_SUBSECTIONS"=>"Y","ACTIVE"=>"Y"],false,false,["ID","NAME","CODE","IBLOCK_SECTION_ID","DETAIL_PAGE_URL","PREVIEW_PICTURE","DETAIL_PICTURE","CATALOG_PRICE_1","CATALOG_TYPE","PROPERTY_BRAND"]);
while($x=$e->GetNext()){
  $offers=[];
  if($x["CATALOG_TYPE"]==3){ $o=CIBlockElement::GetList(["SORT"=>"ASC"],["IBLOCK_ID"=>20,"PROPERTY_167"=>$x["ID"],"ACTIVE"=>"Y"],false,false,["ID","NAME","CATALOG_PRICE_1"]);
    while($y=$o->Fetch()) $offers[]=["id"=>(int)$y["ID"],"n"=>trim($y["NAME"]),"p"=>(float)$y["CATALOG_PRICE_1"]]; }
  else $offers[]=["id"=>(int)$x["ID"],"n"=>trim($x["~NAME"]),"p"=>(float)$x["CATALOG_PRICE_1"]];
  $pic=$x["PREVIEW_PICTURE"]?:$x["DETAIL_PICTURE"]; $img="";
  if($pic){ $f=CFile::ResizeImageGet($pic,["width"=>120,"height"=>120],BX_RESIZE_IMAGE_PROPORTIONAL); $img=$f["src"]; }
  $out["items"][]=["id"=>(int)$x["ID"],"n"=>trim($x["~NAME"]),"code"=>$x["CODE"],"s"=>(int)$x["IBLOCK_SECTION_ID"],"url"=>$x["DETAIL_PAGE_URL"],"img"=>$img,"o"=>$offers];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
