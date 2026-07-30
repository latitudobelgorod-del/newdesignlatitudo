<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<? global $arTheme, $arSection, $APPLICATION;?>
<?if ($GLOBALS['arRegionLink']) {
	if (isset($GLOBALS['arRegionLink']['IBLOCK_ID'])) {
		unset($GLOBALS['arRegionLink']['IBLOCK_ID']);
	}
}?>

<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
	array(
		"COMPONENT_TEMPLATE" => ".default",
		"PATH" => SITE_DIR."include/left_block/menu.left_menu.php",
		"AREA_FILE_SHOW" => "file",
		"AREA_FILE_SUFFIX" => "",
		"AREA_FILE_RECURSIVE" => "Y",
		"EDIT_TEMPLATE" => "include_area.php"
	),
	false
);?>
<?$APPLICATION->ShowViewContent('left_menu');?>



<?$APPLICATION->ShowViewContent('under_sidebar_content');?>

<?if (substr_count($_SERVER['REQUEST_URI'], '/') <=4 ):?>

<?CNext::get_banners_position('SIDE', 'Y');?>

<?endif;?>

<?$requestUrl = $_SERVER['REQUEST_URI'];?>

<?if ((CSite::InDir('/projects/')) || (strpos($requestUrl, '/help/') !== false)) :?>

<?else:?>

<? // новый дизайн рисует блок своей разметкой, у старого свой include/infochat.php ?>
<div class="infochat nd-infochat-wrap" style="margin-top:20px;">
			<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
			array(
				"COMPONENT_TEMPLATE" => ".default",
				"PATH" => SITE_DIR."include/infochat_newdesign.php",
				"AREA_FILE_SHOW" => "file",
				"AREA_FILE_SUFFIX" => "",
				"AREA_FILE_RECURSIVE" => "Y",
				"EDIT_TEMPLATE" => "standard.php"
			),
			false
		);?>

</div>
<?endif;?>