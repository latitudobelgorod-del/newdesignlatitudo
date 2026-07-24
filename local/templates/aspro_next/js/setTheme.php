<?
define("NOT_CHECK_PERMISSIONS",true);
define("STATISTIC_SKIP_ACTIVITY_CHECK", "true");
define('STOP_STATISTICS', true);
define('PUBLIC_AJAX_MODE', true);
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>
<?
use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Config\Option;

if(isset($_REQUEST['site_id'])) {
	$SITE_ID = $_REQUEST['site_id'];
}

if(isset($_REQUEST['site_dir'])) {
	$SITE_DIR = $_REQUEST['site_dir'];
}

define('CUSTOM_CONTENT', 'Y');?>
<?header('Content-Type: application/javascript');?>
<?/*$arSite = CSite::GetByID($SITE_ID)->Fetch();

if(Loader::includeModule('aspro.next'))
{
$moduleClass = 'CNext';
$arFrontParametrs = $moduleClass::GetFrontParametrsValues($SITE_ID, $SITE_DIR);

?>

var arAsproOptions = ({});
<?
	global $arRegion;
	$arRegion = CNextRegionality::getCurrentRegion();
 	if($arRegion):?>
 		arAsproOptions.REGION = <?=CUtil::PhpToJSObject($arRegion);?>,
 		arAsproOptions.SEO_MARKS = <?=CUtil::PhpToJSObject(CNextRegionality::$arSeoMarks);?>
 	<?endif;?>
 <?}
 */?>