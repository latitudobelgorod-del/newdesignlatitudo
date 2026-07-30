<?

/** @global CMain $APPLICATION */
define("NO_KEEP_STATISTIC", true);
define('PUBLIC_AJAX_MODE', true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$application = Bitrix\Main\Application::getInstance();
$request = $application->getContext()->getRequest()->toArray();

if (isset($request['SITE_ID']) && !empty($request['SITE_ID'])) {
	$site_id = htmlspecialchars($request['SITE_ID'], ENT_COMPAT, (defined("BX_UTF") ? "UTF-8" : "ISO-8859-1"));
	if (!is_string($site_id))
		die();
	if (preg_match('/^[a-z0-9_]{2}$/i', $site_id) === 1)
		define('SITE_ID', $site_id);
} else {
	die();
}


if (check_bitrix_sessid()) {
	$commParams = array();
	
	if (isset($request['IBLOCK_ID']) && isset($request['ELEMENT_ID'])) {
		$iblockID = (int)$request['IBLOCK_ID'];
		$elementID = (int)$request['ELEMENT_ID'];
		
		if ($iblockID > 0 && $elementID > 0) {
			$paramsId = 'IBLOCK_CATALOG_COMMENTS_PARAMS_' . $iblockID . '_' . $elementID;
			
			if (!empty($_SESSION[$paramsId]) && is_array($_SESSION[$paramsId])) {
				$commParams = $_SESSION[$paramsId];
				$commParams['ELEMENT_ID'] = $request['ELEMENT_ID'];
				$commParams['USE_FILTER'] = $request['act'] ? "N" : "Y";
			}
			unset($paramsId);
		}
		unset($elementID, $iblockID);
	}
	
	if (!empty($commParams)) {
		$APPLICATION->IncludeComponent(
			'bitrix:catalog.comments',
			'catalog',
			$commParams,
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
}
die();