<?
if(isset($templateData["ERROR_404"]) && $templateData["ERROR_404"]){
    if (!defined("ERROR_404"))
    {
        define("ERROR_404", "Y");
        \CHTTP::setStatus("404 Not Found");
    }
}

$bHasPage = (isset($_GET['PAGEN_'.$arResult["NavNum"]]) && $_GET['PAGEN_'.$arResult["NavNum"]]);
if($bHasPage)
{
    if($_GET['PAGEN_'.$arResult["NavNum"]] == 1 && !isset($_REQUEST['q']))
    {
        LocalRedirect($arResult["sUrlPath"], false, "301 Moved permanently");
    }
    elseif($_GET['PAGEN_'.$arResult["NavNum"]] > $arResult["nEndPage"])
    {
        $templateData["ERROR_404"] = true;
        define ("ERROR_PAGEN", true);
    }
}