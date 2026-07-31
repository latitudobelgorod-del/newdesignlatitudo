<?if( !defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true ) die();?>
<?
/**
 * Страница «Услуги и работы» /services/ в новом дизайне.
 * Копия services_3el; переделан только этот файл — списковая страница.
 * detail.php, section.php и прежние блоки остались как были.
 *
 * По макету Figma («Чистовик», фрейм «Услуги» 20669:39737):
 * H1 52/57 800 слева и красная кнопка «Заказать расчет проекта» справа,
 * под ними линия-разделитель, дальше две колонки — меню услуг 288 слева
 * (его рисует page_blocks/left_block_1.php шаблона) и сетка карточек
 * 3×320 с зазором 24 справа.
 *
 * Заголовок в макете короче («Услуги»), но берём настоящий заголовок
 * страницы — он же в title и хлебных крошках.
 */
$this->setFrameMode(true);
?>
<div class="nd-page-head nd-services-head">
	<h1 id="pagetitle" class="nd-services__h1"><?$APPLICATION->ShowTitle(false)?></h1>
	<div class="nd-page-head__cta"><span class="callback-block animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="detail_service" data-nd-form-title="Заказать расчет проекта">Заказать расчет проекта</span></div>
</div>

<?if((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (strtolower($_REQUEST['ajax']) == 'y'))
{
	$APPLICATION->RestartBuffer();
}?>
<?
// get section items count and subsections
$arItemFilter = CNext::GetCurrentSectionElementFilter($arResult["VARIABLES"], $arParams, false);
$arSubSectionFilter = CNext::GetCurrentSectionSubSectionFilter($arResult["VARIABLES"], $arParams, false);
$itemsCnt = CNextCache::CIBlockElement_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]), "CACHE_GROUP" => array($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()))), $arItemFilter, array());
$arSubSections = CNextCache::CIBlockSection_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]), "MULTI" => "Y", "CACHE_GROUP" => array($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()))), $arSubSectionFilter, false, array("ID"));
?>
<?if(!$itemsCnt && !$arSubSections):?>
	<div class="alert alert-warning"><?=GetMessage("SECTION_EMPTY")?></div>
<?else:?>
	<?// Разделы услуг на общей странице не выводим — в макете их нет,
	   // список услуг плоский.?>
	<?if(strlen($arParams["FILTER_NAME"])):?>
		<?$arTmpFilter = $GLOBALS[$arParams["FILTER_NAME"]];?>
		<?$GLOBALS[$arParams["FILTER_NAME"]] = array_merge((array)$GLOBALS[$arParams["FILTER_NAME"]], $arItemFilter);?>
	<?else:?>
		<?$arParams["FILTER_NAME"] = "arrFilterServ";?>
		<?$GLOBALS[$arParams["FILTER_NAME"]] = $arItemFilter;?>
	<?endif;?>

	<?include('page_blocks/list_elements_newdesign.php');?>

	<?if(strlen($arParams["FILTER_NAME"])):?>
		<?$GLOBALS[$arParams["FILTER_NAME"]] = $arTmpFilter;?>
	<?endif;?>
<?endif;?>
