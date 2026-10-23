<?php
// Включить/выключить товары: php nd_activate.php Y|N ID[,ID]
$_SERVER["DOCUMENT_ROOT"]=getenv("HOME")."/www/latitudo.ru";define("NO_KEEP_STATISTIC",true);define("NOT_CHECK_PERMISSIONS",true);
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";\Bitrix\Main\Loader::includeModule("iblock");
$el=new CIBlockElement();foreach(explode(",",$argv[2]) as $id){echo $id," ",$el->Update((int)$id,["ACTIVE"=>$argv[1]])?"ok":$el->LAST_ERROR,"\n";}
CIBlock::clearIblockTagCache(19);
