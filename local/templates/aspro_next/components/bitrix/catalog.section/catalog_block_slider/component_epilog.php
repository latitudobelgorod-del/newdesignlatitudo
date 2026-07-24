<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $templateData */
/** @var @global CMain $APPLICATION */
use Bitrix\Main\Loader;

if (isset($templateData['TEMPLATE_LIBRARY']) && !empty($templateData['TEMPLATE_LIBRARY'])){
	$loadCurrency = false;
	if (!empty($templateData['CURRENCIES']))
		$loadCurrency = Loader::includeModule('currency');
	CJSCore::Init($templateData['TEMPLATE_LIBRARY']);
	if ($loadCurrency){?>
	<script type="text/javascript">
		BX.Currency.setCurrencies(<? echo $templateData['CURRENCIES']; ?>);
	</script>
	<?}
}

//	big data json answers

if(isset($arParams["USE_BIG_DATA"]) && $arParams["USE_BIG_DATA"] === 'Y'){
	$request = \Bitrix\Main\Context::getCurrent()->getRequest();

	if ($request->isAjaxRequest() && ($request->get('action') === 'deferredLoad'))
	{
		$content = ob_get_contents();
		ob_end_clean();

		list(, $itemsContainer) = explode('<!-- items-container -->', $content);
		$component::sendJsonAnswer(array(
			'items' => $itemsContainer,
		));
		
	}
	$arExtensions[] = 'bigdata';
}

\Aspro\Next\Functions\Extensions::init($arExtensions);
?>