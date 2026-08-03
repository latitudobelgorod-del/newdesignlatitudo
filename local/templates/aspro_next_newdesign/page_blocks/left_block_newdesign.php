<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Левая колонка нового дизайна.
 *
 * Копия left_block_1.php (тот выбирается настройкой темы LEFT_BLOCK_TYPE,
 * а она одна на весь сайт — та же беда, что с HEADER_TYPE/FOOTER_TYPE),
 * поэтому подключается напрямую из footer.php шаблона.
 *
 * Отличие от копии одно: **фильтр идёт выше меню разделов каталога**
 * (просьба Ирины, 2026-08-03). Умный фильтр кладёт себя в отложенную
 * область left_menu, меню разделов приходит из включаемой области —
 * значит достаточно поменять местами два вызова.
 */
global $arTheme, $arSection, $APPLICATION;

if($GLOBALS['arRegionLink'] && isset($GLOBALS['arRegionLink']['IBLOCK_ID']))
	unset($GLOBALS['arRegionLink']['IBLOCK_ID']);
?>

<?// Фильтр (умный фильтр отдаёт свою разметку через эту область).?>
<?$APPLICATION->ShowViewContent('left_menu');?>

<?// Меню разделов каталога.?>
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

<?$APPLICATION->ShowViewContent('under_sidebar_content');?>

<?if(substr_count($_SERVER['REQUEST_URI'], '/') <= 4):?>
	<?CNext::get_banners_position('SIDE', 'Y');?>
<?endif;?>

<?$requestUrl = $_SERVER['REQUEST_URI'];?>
<?if(!CSite::InDir('/projects/') && strpos($requestUrl, '/help/') === false):?>
	<?// Новый дизайн рисует блок своей разметкой, у старого свой include/infochat.php.?>
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
