<?php 

global $APPLICATION;
// устанавливаем DESCRIPTION
if(isset($arResult["DESCRIPTION"])) {
    $APPLICATION->SetPageProperty("description", $arResult["DESCRIPTION"]);
}

if(isset($arResult["TITLE"])) {
   $APPLICATION->SetPageProperty("title", $arResult["TITLE"]);
}
