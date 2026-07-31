<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/**
 * Страница «Производители и марки» /brands/ в новом дизайне.
 * Копия partners_bez_an; переделан только этот файл — списковая страница.
 * detail.php, section.php и прочие блоки остались прежними.
 *
 * По макету Figma («Чистовик», фрейм «Производители» 20738:55460):
 * H1 52/57 800 → подпись 18/25 400 (отступ 16) → линия-разделитель
 * rgba(82,82,100,.15) 2px (отступы по 40) → сетка логотипов 4×316 с зазором 24.
 *
 * Подпись берём из той же включаемой области, что и старый дизайн
 * (/brands/index_inc.php) — текст один и правится из публички в одном месте.
 */
$this->setFrameMode(true);
?>
<div class="nd-brandspage">
	<h1 class="nd-brandspage__h1"><?$APPLICATION->ShowTitle(false)?></h1>

	<div class="nd-brandspage__lead">
		<?$APPLICATION->IncludeComponent(
			"bitrix:main.include",
			"",
			Array(
				"AREA_FILE_SHOW" => "page",
				"AREA_FILE_SUFFIX" => "inc",
				"EDIT_TEMPLATE" => ""
			)
		);?>
	</div>
</div>
<?
$arItemFilter = CNext::GetIBlockAllElementsFilter($arParams);
$itemsCnt = CNextCache::CIblockElement_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), $arItemFilter, array());
?>
<?if(!$itemsCnt):?>
	<div class="alert alert-warning"><?=GetMessage("SECTION_EMPTY")?></div>
<?else:?>
	<?if((isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest") || (strtolower($_REQUEST['ajax']) == 'y'))
	{
		$APPLICATION->RestartBuffer();
	}?>

	<?// Блок списка у нового дизайна один, поэтому подключаем его напрямую,
	   // а не через SECTION_ELEMENTS_TYPE_VIEW: этот параметр приходит из общей
	   // страницы /brands/index.php, которая нужна обоим дизайнам.?>
	<?include('page_blocks/list_elements_newdesign.php');?>

	<?if((isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest") || (strtolower($_REQUEST['ajax']) == 'y'))
	{
		die();
	}?>
<?endif;?>
