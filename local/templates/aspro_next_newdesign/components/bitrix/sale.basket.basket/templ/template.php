<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 * @var string $templateName
 * @var CMain $APPLICATION
 * @var CBitrixBasketComponent $component
 * @var CBitrixComponentTemplate $this
 * @var array $giftParameters
 */

$documentRoot = Main\Application::getDocumentRoot();

if (empty($arParams['TEMPLATE_THEME']))
{
	$arParams['TEMPLATE_THEME'] = Main\ModuleManager::isModuleInstalled('bitrix.eshop') ? 'site' : 'blue';
}

if ($arParams['TEMPLATE_THEME'] === 'site')
{
	$templateId = Main\Config\Option::get('main', 'wizard_template_id', 'eshop_bootstrap', $component->getSiteId());
	$templateId = preg_match('/^eshop_adapt/', $templateId) ? 'eshop_adapt' : $templateId;
	$arParams['TEMPLATE_THEME'] = Main\Config\Option::get('main', 'wizard_'.$templateId.'_theme_id', 'blue', $component->getSiteId());
}

if (!empty($arParams['TEMPLATE_THEME']))
{
	if (!is_file($documentRoot.'/bitrix/css/main/themes/'.$arParams['TEMPLATE_THEME'].'/style.css'))
	{
		$arParams['TEMPLATE_THEME'] = 'blue';
	}
}

if (!isset($arParams['DISPLAY_MODE']) || !in_array($arParams['DISPLAY_MODE'], array('extended', 'compact')))
{
	$arParams['DISPLAY_MODE'] = 'extended';
}

$arParams['USE_DYNAMIC_SCROLL'] = isset($arParams['USE_DYNAMIC_SCROLL']) && $arParams['USE_DYNAMIC_SCROLL'] === 'N' ? 'N' : 'Y';
$arParams['SHOW_FILTER'] = isset($arParams['SHOW_FILTER']) && $arParams['SHOW_FILTER'] === 'N' ? 'N' : 'Y';

$arParams['PRICE_DISPLAY_MODE'] = isset($arParams['PRICE_DISPLAY_MODE']) && $arParams['PRICE_DISPLAY_MODE'] === 'N' ? 'N' : 'Y';

if (!isset($arParams['TOTAL_BLOCK_DISPLAY']) || !is_array($arParams['TOTAL_BLOCK_DISPLAY']))
{
	$arParams['TOTAL_BLOCK_DISPLAY'] = array('top');
}

if (empty($arParams['PRODUCT_BLOCKS_ORDER']))
{
	$arParams['PRODUCT_BLOCKS_ORDER'] = 'props,sku,columns';
}

if (is_string($arParams['PRODUCT_BLOCKS_ORDER']))
{
	$arParams['PRODUCT_BLOCKS_ORDER'] = explode(',', $arParams['PRODUCT_BLOCKS_ORDER']);
}

$arParams['USE_PRICE_ANIMATION'] = isset($arParams['USE_PRICE_ANIMATION']) && $arParams['USE_PRICE_ANIMATION'] === 'N' ? 'N' : 'Y';
$arParams['USE_ENHANCED_ECOMMERCE'] = isset($arParams['USE_ENHANCED_ECOMMERCE']) && $arParams['USE_ENHANCED_ECOMMERCE'] === 'Y' ? 'Y' : 'N';
$arParams['DATA_LAYER_NAME'] = isset($arParams['DATA_LAYER_NAME']) ? trim($arParams['DATA_LAYER_NAME']) : 'dataLayer';
$arParams['BRAND_PROPERTY'] = isset($arParams['BRAND_PROPERTY']) ? trim($arParams['BRAND_PROPERTY']) : '';

if ($arParams['USE_GIFTS'] === 'Y')
{
	$giftParameters = array(
		'SHOW_PRICE_COUNT' => 1,
		'PRODUCT_SUBSCRIPTION' => 'N',
		'PRODUCT_ID_VARIABLE' => 'id',
		'PARTIAL_PRODUCT_PROPERTIES' => 'N',
		'USE_PRODUCT_QUANTITY' => 'N',
		'ACTION_VARIABLE' => 'actionGift',
		'ADD_PROPERTIES_TO_BASKET' => 'Y',

		'BASKET_URL' => $APPLICATION->GetCurPage(),
		'APPLIED_DISCOUNT_LIST' => $arResult['APPLIED_DISCOUNT_LIST'],
		'FULL_DISCOUNT_LIST' => $arResult['FULL_DISCOUNT_LIST'],

		'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
		'PRICE_VAT_INCLUDE' => $arParams['PRICE_VAT_SHOW_VALUE'],
		'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],

		'BLOCK_TITLE' => $arParams['GIFTS_BLOCK_TITLE'],
		'HIDE_BLOCK_TITLE' => $arParams['GIFTS_HIDE_BLOCK_TITLE'],
		'TEXT_LABEL_GIFT' => $arParams['GIFTS_TEXT_LABEL_GIFT'],
		'PRODUCT_QUANTITY_VARIABLE' => $arParams['GIFTS_PRODUCT_QUANTITY_VARIABLE'],
		'PRODUCT_PROPS_VARIABLE' => $arParams['GIFTS_PRODUCT_PROPS_VARIABLE'],
		'SHOW_OLD_PRICE' => $arParams['GIFTS_SHOW_OLD_PRICE'],
		'SHOW_DISCOUNT_PERCENT' => $arParams['GIFTS_SHOW_DISCOUNT_PERCENT'],
		'SHOW_NAME' => $arParams['GIFTS_SHOW_NAME'],
		'SHOW_IMAGE' => $arParams['GIFTS_SHOW_IMAGE'],
		'MESS_BTN_BUY' => $arParams['GIFTS_MESS_BTN_BUY'],
		'MESS_BTN_DETAIL' => $arParams['GIFTS_MESS_BTN_DETAIL'],
		'PAGE_ELEMENT_COUNT' => $arParams['GIFTS_PAGE_ELEMENT_COUNT'],
		'CONVERT_CURRENCY' => $arParams['GIFTS_CONVERT_CURRENCY'],
		'HIDE_NOT_AVAILABLE' => $arParams['GIFTS_HIDE_NOT_AVAILABLE'],

		'LINE_ELEMENT_COUNT' => $arParams['GIFTS_PAGE_ELEMENT_COUNT'],

		'DETAIL_URL' => isset($arParams['GIFTS_DETAIL_URL']) ? $arParams['GIFTS_DETAIL_URL'] : null
	);
}

\CJSCore::Init(array('fx', 'popup', 'ajax'));

$this->addExternalCss('/bitrix/css/main/bootstrap.css');
$this->addExternalCss($templateFolder.'/themes/'.$arParams['TEMPLATE_THEME'].'/style.css');

$this->addExternalJs($templateFolder.'/js/mustache.js');
$this->addExternalJs($templateFolder.'/js/action-pool.js');
$this->addExternalJs($templateFolder.'/js/filter.js');
$this->addExternalJs($templateFolder.'/js/component.js');

$mobileColumns = isset($arParams['COLUMNS_LIST_MOBILE'])
	? $arParams['COLUMNS_LIST_MOBILE']
	: $arParams['COLUMNS_LIST'];
$mobileColumns = array_fill_keys($mobileColumns, true);

$jsTemplates = new Main\IO\Directory($documentRoot.$templateFolder.'/js-templates');
/** @var Main\IO\File $jsTemplate */
foreach ($jsTemplates->getChildren() as $jsTemplate)
{
	include($jsTemplate->getPath());
}

$displayModeClass = $arParams['DISPLAY_MODE'] === 'compact' ? ' basket-items-list-wrapper-compact' : '';

?>
	<?/* Новый дизайн корзины (макет Figma «Чистовик», десктоп 20496:79858).
	   Стили — css/newdesign-basket.css, тегом прямо здесь: компонент рисуется,
	   когда <head> уже отдан. Разметку компонента не трогаем, раскладку делает
	   css поверх неё — через корзину идут заказы, а её пересчёт и мобильная
	   панель завязаны на аспровские классы и data-entity. */?>
	<?
	$ndBasketCss = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/css/newdesign-basket.css';
	?>
	<link href="<?=SITE_TEMPLATE_PATH?>/css/newdesign-basket.css?<?=(file_exists($ndBasketCss) ? filemtime($ndBasketCss) : '')?>" rel="stylesheet">

	<?/* Заголовок и «Очистить корзину» одной строкой, как в макете. H1 печатаем
	   здесь, а не на странице: basket/index.php общий со старым дизайном, там
	   свой заголовок «Ваша корзина» остаётся нетронутым.

	   Готовой очистки корзины в шаблоне нет — Битрикс умеет удалять только по
	   одному товару. Поэтому кнопка жмёт все кнопки удаления разом: они кладут
	   задания в тот же пул действий компонента, что и обычное удаление, и
	   пересчёт проходит штатно. */?>
	<div class="nd-basket-head">
		<h1 class="nd-basket-head__title" id="pagetitle">Корзина</h1>
		<?// У пустой корзины чистить нечего — кнопку не показываем. Пустую
		   // корзину компонент отдаёт той же веткой, что и ошибку: в ERROR_MESSAGE
		   // у него лежит «Ваша корзина пуста».?>
		<?if(empty($arResult['ERROR_MESSAGE'])):?>
		<button class="nd-basket-head__clear" type="button" data-nd-basket-clear>
			<svg width="24" height="24" viewBox="0 0 16 16" fill="none" aria-hidden="true">
				<path d="M9.332.733c.513 0 1.005.204 1.367.567.363.362.567.854.567 1.367v.732h2.733a.6.6 0 0 1 0 1.201h-.733v8.733c0 .513-.204 1.005-.567 1.367a1.93 1.93 0 0 1-1.367.567H4.666a1.93 1.93 0 0 1-1.367-.567 1.93 1.93 0 0 1-.567-1.367V4.6H2a.6.6 0 0 1 0-1.201h2.733v-.732c0-.513.204-1.005.566-1.367A1.93 1.93 0 0 1 6.666.733h2.666ZM3.933 4.6v8.733c0 .195.077.381.215.519.137.137.324.214.518.214h6.666c.195 0 .381-.077.519-.214a.733.733 0 0 0 .214-.519V4.6H3.933Zm2.733-2.667a.733.733 0 0 0-.733.733v.733h4.132v-.733a.733.733 0 0 0-.732-.733H6.666Z" fill="currentColor"/>
			</svg>
			<span>Очистить корзину</span>
		</button>
		<?endif;?>
	</div>
	<script>
	(function () {
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-nd-basket-clear]');
			if (!btn) return;
			var items = document.querySelectorAll('#basket-item-table [data-entity="basket-item-delete"]');
			if (!items.length) return;
			if (!confirm('Удалить все товары из корзины?')) return;
			Array.prototype.forEach.call(items, function (node) { node.click(); });
		});
	})();
	</script>

<?
if (empty($arResult['ERROR_MESSAGE']))
{
	if ($arParams['USE_GIFTS'] === 'Y' && $arParams['GIFTS_PLACE'] === 'TOP')
	{
		$APPLICATION->IncludeComponent(
			'bitrix:sale.gift.basket',
			'.default',
			$giftParameters,
			$component
		);
	}

	if ($arResult['BASKET_ITEM_MAX_COUNT_EXCEEDED'])
	{
		?>
		<div id="basket-item-message">
			<?=Loc::getMessage('SBB_BASKET_ITEM_MAX_COUNT_EXCEEDED', array('#PATH#' => $arParams['PATH_TO_BASKET']))?>
		</div>
		<?
	}
	?>
	
	
	<?//вся корзина?>
	
	
	
	<div id="basket-root" class="bx-basket bx-<?=$arParams['TEMPLATE_THEME']?> bx-step-opacity" style="opacity: 0;">
	
	
	<?//предупреждение - у нас там ничего не выводится?>
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-warning alert-dismissable" id="basket-warning" style="display: none;">
					<span class="close" data-entity="basket-items-warning-notification-close">&times;</span>
					<div data-entity="basket-general-warnings"></div>
					<div data-entity="basket-item-warnings">
						<?=Loc::getMessage('SBB_BASKET_ITEM_WARNING')?>
					</div>
				</div>
			</div>
		</div>
<?//предупреждение - у нас там ничего не выводится?>



<div class="flexbox flexbox--row basket-items-list">
<div class="basket-items-list-outer">
		<?//блок с заказанными товарами?>
		<?if( isMobilelat() ):?>
		
				<div class="basket-items-list-wrapper basket-items-list-wrapper-height-fixed basket-items-list-wrapper-light<?=$displayModeClass?>"
					id="basket-items-list-wrapper">
					<div class="basket-items-list-header" data-entity="basket-items-list-header">
				
						<div class="basket-items-list-header-filter">
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item active"
								data-entity="basket-items-count" data-filter="all" style="display: none;"></a>
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item"
								data-entity="basket-items-count" data-filter="similar" style="display: none;"></a>
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item"
								data-entity="basket-items-count" data-filter="warning" style="display: none;"></a>
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item"
								data-entity="basket-items-count"  style="display: none;"></a>
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item"
								data-entity="basket-items-count" data-filter="not-available" style="display: none;"></a>
								
								
								
								
						</div>
						
			
							

</div>
					
					
					<div class="basket-items-list-container" id="basket-items-list-container">
						<div class="basket-items-list-overlay" id="basket-items-list-overlay" style="display: none;"></div>
						<div class="basket-items-list" id="basket-item-list">
							<div class="basket-search-not-found" id="basket-item-list-empty-result" style="display: none;">
								<div class="basket-search-not-found-icon"></div>
								<div class="basket-search-not-found-text">
									<?=Loc::getMessage('SBB_FILTER_EMPTY_RESULT')?>
								</div>
							</div>
							<table class="basket-items-list-table" id="basket-item-table"></table>
						</div>
					</div>
				</div>
				<?else:?>
				
							<div class="basket-items-list-wrapper basket-items-list-wrapper-height-fixed basket-items-list-wrapper-light<?=$displayModeClass?>"
					id="basket-items-list-wrapper">
					<div class="basket-items-list-header" data-entity="basket-items-list-header">
						<div class="basket-items-search-field" data-entity="basket-filter">
							<div class="form has-feedback">
								<input type="text" class="form-control"
									placeholder="<?=Loc::getMessage('SBB_BASKET_FILTER')?>"
									data-entity="basket-filter-input">
								<span class="form-control-feedback basket-clear" data-entity="basket-filter-clear-btn"></span>
							</div>
						</div>
						<div class="basket-items-list-header-filter">
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item active"
								data-entity="basket-items-count" data-filter="all" style="display: none;"></a>
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item"
								data-entity="basket-items-count" data-filter="similar" style="display: none;"></a>
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item"
								data-entity="basket-items-count" data-filter="warning" style="display: none;"></a>
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item"
								data-entity="basket-items-count"  style="display: none;"></a>
							<a href="javascript:void(0)" class="basket-items-list-header-filter-item"
								data-entity="basket-items-count" data-filter="not-available" style="display: none;"></a>
						</div>
					</div>
					<div class="basket-items-list-container" id="basket-items-list-container">
						<div class="basket-items-list-overlay" id="basket-items-list-overlay" style="display: none;"></div>
						<div class="basket-items-list" id="basket-item-list">
							<div class="basket-search-not-found" id="basket-item-list-empty-result" style="display: none;">
								<div class="basket-search-not-found-icon"></div>
								<div class="basket-search-not-found-text">
									<?=Loc::getMessage('SBB_FILTER_EMPTY_RESULT')?>
								</div>
							</div>
							<table class="basket-items-list-table" id="basket-item-table"></table>
						</div>
					</div>
				</div>
				<?endif;?>
				
		
		<?//блок с заказанными товарами?>
		
</div>



		<?if ($arParams['BASKET_WITH_ORDER_INTEGRATION'] !== 'Y'):?>
				<div class="basket-total-outer ">
					<div class="basket-total-block" data-entity="basket-total-block"></div>
				</div>
			<?endif;?>
</div>









				
		
			</div>
	
	
	
	
<?//вся корзина?>
	<?
	if (!empty($arResult['CURRENCIES']) && Main\Loader::includeModule('currency'))
	{
		CJSCore::Init('currency');

		?>
		<script>
			BX.Currency.setCurrencies(<?=CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true)?>);
		</script>
		<?
	}

	$signer = new \Bitrix\Main\Security\Sign\Signer;
	$signedTemplate = $signer->sign($templateName, 'sale.basket.basket');
	$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.basket.basket');
	$messages = Loc::loadLanguageFile(__FILE__);
	?>
	<script>
		BX.message(<?=CUtil::PhpToJSObject($messages)?>);
		BX.Sale.BasketComponent.init({
			result: <?=CUtil::PhpToJSObject($arResult, false, false, true)?>,
			params: <?=CUtil::PhpToJSObject($arParams)?>,
			template: '<?=CUtil::JSEscape($signedTemplate)?>',
			signedParamsString: '<?=CUtil::JSEscape($signedParams)?>',
			siteId: '<?=$component->getSiteId()?>',
			ajaxUrl: '<?=CUtil::JSEscape($component->getPath().'/ajax.php')?>',
			templateFolder: '<?=CUtil::JSEscape($templateFolder)?>'
		});
	</script>
	<?
	if ($arParams['USE_GIFTS'] === 'Y' && $arParams['GIFTS_PLACE'] === 'BOTTOM')
	{
		$APPLICATION->IncludeComponent(
			'bitrix:sale.gift.basket',
			'.default',
			$giftParameters,
			$component
		);
	}
}
else
{/*ShowError($arResult['ERROR_MESSAGE']);*/
?>
	
<div class="bx-sbb-empty-cart-container">
<div class="bx-sbb-empty-cart-image">
<img src="" alt="">
</div>
<div class="bx-sbb-empty-cart-text">Ваша корзина пуста</div>
<div class="bx-sbb-empty-cart-desc">
	<a href="/catalog/">Нажмите здесь</a>, чтобы продолжить покупки	</div>
</div>
<?

}

/* Лента «Может пригодиться» под корзиной (макет Figma «Чистовик»).
   Это то же самое, что «С этим товаром покупают» на детальной: свойство
   EXPANDABLES, фильтр по ID через arrFilterAccess и bitrix:catalog.section
   с шаблоном карточки catalog_blockcolors_newdesign. Разница только в том,
   что связанные берутся не у одного товара, а у всех, что лежат в корзине,
   и сами товары корзины из ленты исключаются.

   Стили .nd-related и лента со стрелками — в css/newdesign-catalog.css и
   js/newdesign-catalog.js, их тянет сам шаблон карточки. */
$ndBasketProductIds = array();

if (!empty($arResult['GRID']['ROWS']) && is_array($arResult['GRID']['ROWS']))
{
	foreach ($arResult['GRID']['ROWS'] as $ndRow)
	{
		if ((int)$ndRow['PRODUCT_ID'] > 0)
		{
			$ndBasketProductIds[] = (int)$ndRow['PRODUCT_ID'];
		}
	}
}

$ndRelatedIds = array();
$ndRelatedIblockId = 0;
$ndRelatedBySource = array();

if ($ndBasketProductIds && CModule::IncludeModule('iblock'))
{
	$ndBasketProductIds = array_unique($ndBasketProductIds);

	/* В корзине лежат торговые предложения, а EXPANDABLES заполняют у товара —
	   поэтому сначала поднимаемся от предложения к его товару. Для обычных
	   товаров без SKU GetProductList ничего не вернёт, они идут как есть. */
	$ndParentIds = $ndBasketProductIds;
	/* Кто кого притянул: товар корзины → его товар в каталоге. Нужно, чтобы
	   при удалении позиции убрать из ленты именно её связанные. */
	$ndParentToBasket = array();

	foreach ($ndBasketProductIds as $ndBasketProductId)
	{
		$ndParentToBasket[$ndBasketProductId][] = $ndBasketProductId;
	}

	if (CModule::IncludeModule('catalog'))
	{
		$ndSkuParents = CCatalogSku::GetProductList($ndBasketProductIds);

		if (is_array($ndSkuParents))
		{
			foreach ($ndSkuParents as $ndSkuId => $ndParent)
			{
				$ndKey = array_search((int)$ndSkuId, $ndParentIds);

				if ($ndKey !== false && (int)$ndParent['ID'] > 0)
				{
					$ndParentIds[$ndKey] = (int)$ndParent['ID'];

					unset($ndParentToBasket[(int)$ndSkuId]);
					$ndParentToBasket[(int)$ndParent['ID']][] = (int)$ndSkuId;
				}
			}
		}
	}

	$ndParentIds = array_unique($ndParentIds);

	/* Товары могут быть из разных каталогов, а свойство выбирается по
	   инфоблоку — раскладываем по инфоблокам. GetPropertyValues здесь
	   значения не отдаёт, поэтому берём свойство прямо в select GetList:
	   на множественном EXPANDABLES выборка вернёт строку на каждое значение. */
	$ndByIblock = array();
	$ndElementIterator = CIBlockElement::GetList(
		array(),
		array('ID' => $ndParentIds, 'ACTIVE' => 'Y'),
		false,
		false,
		array('ID', 'IBLOCK_ID')
	);

	while ($ndElement = $ndElementIterator->Fetch())
	{
		$ndByIblock[(int)$ndElement['IBLOCK_ID']][] = (int)$ndElement['ID'];
	}

	$ndRelatedByIblock = array();
	$ndRelatedBySourceByIblock = array();

	foreach ($ndByIblock as $ndIblockId => $ndIds)
	{
		$ndPropIterator = CIBlockElement::GetList(
			array(),
			array('IBLOCK_ID' => $ndIblockId, 'ID' => $ndIds, 'ACTIVE' => 'Y'),
			false,
			false,
			array('ID', 'IBLOCK_ID', 'PROPERTY_EXPANDABLES')
		);

		while ($ndProp = $ndPropIterator->Fetch())
		{
			$ndRelatedId = (int)$ndProp['PROPERTY_EXPANDABLES_VALUE'];

			if ($ndRelatedId <= 0)
			{
				continue;
			}

			$ndRelatedByIblock[$ndIblockId][] = $ndRelatedId;

			$ndSources = isset($ndParentToBasket[(int)$ndProp['ID']])
				? $ndParentToBasket[(int)$ndProp['ID']]
				: array((int)$ndProp['ID']);

			foreach ($ndSources as $ndSourceId)
			{
				$ndRelatedBySourceByIblock[$ndIblockId][$ndSourceId][] = $ndRelatedId;
			}
		}
	}

	/* Лента одна, поэтому берём инфоблок, где связанных больше всего.
	   На практике каталог один и ветка с выбором не срабатывает. */
	foreach ($ndRelatedByIblock as $ndIblockId => $ndIds)
	{
		/* То, что уже в корзине, предлагать незачем. */
		$ndIds = array_diff(array_unique($ndIds), $ndParentIds, $ndBasketProductIds);

		if (count($ndIds) > count($ndRelatedIds))
		{
			$ndRelatedIds = $ndIds;
			$ndRelatedIblockId = (int)$ndIblockId;
		}
	}

	/* Карта для JS: товар корзины → что он притянул в ленту. */
	if ($ndRelatedIblockId && isset($ndRelatedBySourceByIblock[$ndRelatedIblockId]))
	{
		foreach ($ndRelatedBySourceByIblock[$ndRelatedIblockId] as $ndSourceId => $ndIds)
		{
			$ndIds = array_values(array_intersect(array_unique($ndIds), $ndRelatedIds));

			if ($ndIds)
			{
				$ndRelatedBySource[$ndSourceId] = $ndIds;
			}
		}
	}
}

if ($ndRelatedIds && $ndRelatedIblockId):
	$GLOBALS['arrFilterBasketRelated'] = array('ID' => array_values($ndRelatedIds));
?>
<script>
	/* Товар корзины → товары, которые он притянул в ленту. Удаление позиции
	   идёт ajax'ом, страница не перезагружается — ленту подрезаем на месте. */
	window.__ndBasketRelated = <?=CUtil::PhpToJSObject($ndRelatedBySource)?>;
</script>
<div class="nd-related nd-related--basket">
	<div class="nd-related__head">
		<h2 class="nd-related__title"><?=Loc::getMessage('SBB_ND_RELATED_TITLE')?></h2>
		<div class="nd-related__nav">
			<span class="nd-related__counter"></span>
			<button class="nd-related__arrow nd-related__arrow--prev" type="button" aria-label="Предыдущие товары">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<button class="nd-related__arrow nd-related__arrow--next" type="button" aria-label="Следующие товары">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		</div>
	</div>
	<div class="nd-related__body">
		<?$APPLICATION->IncludeComponent(
			'bitrix:catalog.section',
			'catalog_blockcolors_newdesign',
			array(
				'IBLOCK_TYPE' => 'aspro_next_catalog',
				'IBLOCK_ID' => $ndRelatedIblockId,
				'ELEMENT_SORT_FIELD' => 'SORT',
				'ELEMENT_SORT_ORDER' => 'asc',
				'ELEMENT_SORT_FIELD2' => 'id',
				'ELEMENT_SORT_ORDER2' => 'desc',
				'FILTER_NAME' => 'arrFilterBasketRelated',
				'SHOW_ALL_WO_SECTION' => 'Y',
				'SECTION_ID' => '',
				'SECTION_CODE' => '',
				'STORES' => '',
				'SHOW_UNABLE_SKU_PROPS' => 'Y',
				'AJAX_REQUEST' => 'N',
				'INCLUDE_SUBSECTIONS' => 'N',
				'PAGE_ELEMENT_COUNT' => '20',
				'LINE_ELEMENT_COUNT' => '4',
				'SHOW_ARTICLE_SKU' => 'Y',
				'SHOW_MEASURE_WITH_RATIO' => 'N',
				'OFFERS_SORT_FIELD' => 'sort',
				'OFFERS_SORT_ORDER' => 'asc',
				'OFFERS_SORT_FIELD2' => 'name',
				'OFFERS_SORT_ORDER2' => 'asc',
				'OFFERS_LIMIT' => '300',
				/* Те же свойства предложений, что и на странице раздела
				   (catalog/index.php, LIST_OFFERS_PROPERTY_CODE и
				   OFFER_TREE_PROPS) — иначе в карточке не соберётся выбор
				   длины и цвета. */
				'OFFERS_FIELD_CODE' => array('ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'),
				'OFFERS_PROPERTY_CODE' => array(
					'VID', 'DLINA_STR', 'WIDTH', 'THICK', 'VES', 'MATERIAL',
					'GARANTY', 'ARTICLE', 'VWS_N', 'DLINA', 'UNIT_KOEF',
					'MODEL_OP', 'MONTAZ_PAZ', 'NORM', 'VALUE_TOV', 'BASE_KOEF',
					'RAZM', 'COLOR_REF', 'COLOR_REF2', 'SVET', 'WIDTH_D',
				),
				'OFFER_TREE_PROPS' => array(
					'COLOR_REF', 'DLINA', 'COLOR_REF2', 'MONTAZ_PAZ',
					'MODEL_OP', 'VALUE_TOV', 'RAZM', 'WIDTH_D', 'SVET', 'VWS_N',
				),
				'OFFERS_CART_PROPERTIES' => array('DLINA'),
				'USE_REGION' => 'Y',
				/* Без TYPE_SKU=TYPE_1 result_modifier шаблона карточки не строит
				   дерево предложений: в карточке не появляется выбор цвета и
				   длины, а цена так и остаётся диапазоном «от …». */
				'TYPE_SKU' => 'TYPE_1',
				'DISPLAY_TYPE' => 'block',
				'SECTION_URL' => '',
				'DETAIL_URL' => '',
				'BASKET_URL' => SITE_DIR.'basket/',
				'ACTION_VARIABLE' => 'action',
				'PRODUCT_ID_VARIABLE' => 'id',
				'PRODUCT_QUANTITY_VARIABLE' => 'quantity',
				'PRODUCT_PROPS_VARIABLE' => 'prop',
				'SECTION_ID_VARIABLE' => 'SECTION_ID',
				'SET_LAST_MODIFIED' => 'N',
				'AJAX_MODE' => 'N',
				'CACHE_TYPE' => 'N',
				'CACHE_TIME' => '36000',
				'CACHE_GROUPS' => 'N',
				'CACHE_FILTER' => 'Y',
				'META_KEYWORDS' => '-',
				'META_DESCRIPTION' => '-',
				'BROWSER_TITLE' => '-',
				'ADD_SECTIONS_CHAIN' => 'N',
				'HIDE_NOT_AVAILABLE' => 'N',
				'HIDE_NOT_AVAILABLE_OFFERS' => 'N',
				'DISPLAY_COMPARE' => 'N',
				'SET_TITLE' => 'N',
				'SET_STATUS_404' => 'N',
				'SHOW_404' => 'N',
				'MESSAGE_404' => '',
				'PRICE_CODE' => array('BASE'),
				'USE_PRICE_COUNT' => 'Y',
				'SHOW_PRICE_COUNT' => '1',
				'PRICE_VAT_INCLUDE' => 'Y',
				'USE_PRODUCT_QUANTITY' => 'Y',
				'LIST_PROPERTY_CODE' => array(
					'MINIMUM_PRICE', 'MAXIMUM_PRICE', 'HIT', 'BRAND', 'PROP_2065',
					'POPUP_VIDEO', 'CML2_ARTICLE', 'ASSOCIATED', 'PROP_2052', 'SET',
					'UNIT_KOEF', 'BASE_KOEF', 'COLOR_MAIN_EL', 'USAGE_DOSKA_DPK',
					'ATTRIBUTES', 'TOLSHINA', 'PROP_2083', 'CML2_LINK',
					'DECKING_PROFILE', 'COLOR_REF2',
				),
				'DISPLAY_TOP_PAGER' => 'N',
				'DISPLAY_BOTTOM_PAGER' => 'N',
				'PAGER_TITLE' => 'Товары',
				'PAGER_SHOW_ALWAYS' => 'N',
				'PAGER_TEMPLATE' => 'main',
				'PAGER_DESC_NUMBERING' => 'N',
				'PAGER_SHOW_ALL' => 'N',
				'ADD_CHAIN_ITEM' => 'N',
				'SHOW_QUANTITY' => 'Y',
				'SHOW_QUANTITY_COUNT' => 'Y',
				'SHOW_DISCOUNT_PERCENT' => 'Y',
				'SHOW_DISCOUNT_TIME' => 'N',
				'SHOW_OLD_PRICE' => 'Y',
				'CONVERT_CURRENCY' => 'Y',
				'CURRENCY_ID' => 'RUB',
				'USE_STORE' => 'N',
				'DISPLAY_WISH_BUTTONS' => 'N',
				'LIST_DISPLAY_POPUP_IMAGE' => 'Y',
				'DEFAULT_COUNT' => '1',
				'SHOW_MEASURE' => 'Y',
				'SHOW_HINTS' => 'Y',
				'OFFER_HIDE_NAME_PROPS' => 'N',
				'ADD_PROPERTIES_TO_BASKET' => 'Y',
				'PARTIAL_PRODUCT_PROPERTIES' => 'Y',
				'PRODUCT_PROPERTIES' => array(),
				'SALE_STIKER' => 'SALE_TEXT',
				'STIKERS_PROP' => 'HIT',
				'SHOW_RATING' => 'N',
				'COMPONENT_TEMPLATE' => 'catalog_blockcolors_newdesign',
				'SEF_MODE' => 'N',
				'COMPATIBLE_MODE' => 'Y',
				'DISABLE_INIT_JS_IN_COMPONENT' => 'N',
			),
			false
		);?>
	</div>
</div>
<?endif;?>
<?/* Раскрытие плашки наличия. Делегирование на документе, потому что карточки
	 перерисовывает mustache при каждом пересчёте корзины и обработчики на самих
	 плашках после этого теряются. */?>
<script>
(function () {
	if (window.__ndBasketStores) return;
	window.__ndBasketStores = true;

	document.addEventListener('click', function (e) {
		var trigger = e.target && e.target.closest ? e.target.closest('.nd-basket-stores__trigger') : null;
		var box = trigger ? trigger.parentNode : null;

		[].forEach.call(document.querySelectorAll('.nd-basket-stores.is-open'), function (el) {
			if (el !== box) el.classList.remove('is-open');
		});

		if (!box) return;

		e.preventDefault();
		e.stopPropagation();
		box.classList.toggle('is-open');
	});
})();
</script>

<?/* Лента «Может пригодиться» следит за составом корзины: удалили позицию —
	 её связанные уходят из ленты, нажали «Восстановить» — возвращаются. */?>
<script>
(function () {
	if (window.__ndBasketRelatedSync) return;
	window.__ndBasketRelatedSync = true;

	function cardId(card) {
		var el = card.querySelector('[id^="bx_"]');
		var m = el && el.id.match(/_(\d+)$/);
		return m ? m[1] : '';
	}

	function sync() {
		var box = document.querySelector('.nd-related--basket');
		var map = window.__ndBasketRelated;
		if (!box || !map) return;

		var allowed = {};
		[].forEach.call(document.querySelectorAll('#basket-root [data-nd-product-id]'), function (row) {
			/* Удалённая позиция ещё висит в списке с кнопкой «Восстановить» —
			   её связанные уже не показываем. */
			if (row.querySelector('[data-entity="basket-item-restore-button"]')) return;
			(map[row.getAttribute('data-nd-product-id')] || []).forEach(function (id) {
				allowed[id] = 1;
			});
		});

		var shown = 0;
		[].forEach.call(box.querySelectorAll('.item_block'), function (card) {
			var visible = !!allowed[cardId(card)];
			card.classList.toggle('nd-related-hidden', !visible);
			/* Класс перебивает шаблон карточки не везде — дублируем инлайном
			   с important, иначе спрятанная карточка остаётся в ленте. */
			if (visible) card.style.removeProperty('display');
			else card.style.setProperty('display', 'none', 'important');
			if (visible) shown++;
		});

		box.classList.toggle('nd-related-hidden', !shown);
		if (shown) box.style.removeProperty('display');
		else box.style.setProperty('display', 'none', 'important');
		/* Счётчик и стрелки ленты пересчитываются по resize. */
		window.dispatchEvent(new Event('resize'));
	}

	var timer = 0;
	function schedule() {
		clearTimeout(timer);
		timer = setTimeout(sync, 200);
	}

	function observe() {
		var list = document.getElementById('basket-item-list');
		if (!list) return;
		new MutationObserver(schedule).observe(list, { childList: true, subtree: true });
		schedule();
	}

	if (document.readyState !== 'loading') observe();
	else document.addEventListener('DOMContentLoaded', observe);
})();
</script>

<?/* Товар, добавленный из ленты «Может пригодиться», в списке корзины не
	 появлялся до перезагрузки страницы. Разбор: сервер кладёт его в корзину
	 сразу, и refreshAjax компонента отдаёт новую позицию в GRID.ROWS — но
	 нарисовать под неё строку штатный JS не умеет, он обновляет только те,
	 что уже есть в разметке. Поэтому: сначала просим пересчёт (если товар в
	 корзине уже был, там поменяется количество — и перезагрузка не нужна), а
	 если позиций стало больше, чем нарисовано, перезагружаем страницу,
	 вернув прокрутку на прежнее место. */?>
<script>
(function () {
	if (window.__ndBasketAddSync) return;
	window.__ndBasketAddSync = true;

	var SCROLL_KEY = 'ndBasketScroll';

	/* Прокрутку возвращаем после перезагрузки: лента живёт внизу страницы,
	   и без этого пользователь оказывался бы наверху. */
	function restoreScroll() {
		var y;
		try {
			y = sessionStorage.getItem(SCROLL_KEY);
			sessionStorage.removeItem(SCROLL_KEY);
		} catch (e) {}
		if (!y) return;
		var scroll = function () { window.scrollTo(0, parseInt(y, 10) || 0); };
		scroll();
		/* Карточки ленты дорисовываются скриптами, высота страницы меняется —
		   поэтому повторяем, пока она не устаканится. */
		setTimeout(scroll, 400);
		setTimeout(scroll, 1200);
	}

	function reload() {
		try { sessionStorage.setItem(SCROLL_KEY, String(window.pageYOffset || 0)); } catch (e) {}
		window.location.reload();
	}

	function rowsShown() {
		return document.querySelectorAll('#basket-item-list [data-nd-product-id]').length;
	}

	function rowsKnown() {
		var bc = window.BX && BX.Sale && BX.Sale.BasketComponent;
		var rows = bc && bc.result && bc.result.GRID ? bc.result.GRID.ROWS : null;
		return rows ? Object.keys(rows).length : 0;
	}

	/* Ждём, пока пересчёт доедет: события об окончании у компонента нет,
	   поэтому просто следим за его состоянием несколько секунд. */
	function reloadIfNewItem() {
		var tries = 0;
		var timer = setInterval(function () {
			tries++;
			if (rowsKnown() > rowsShown()) {
				clearInterval(timer);
				reload();
			} else if (tries > 16) {
				clearInterval(timer);
			}
		}, 300);
	}

	var pending = 0;
	function afterAdd() {
		/* Несколько сигналов об одном добавлении — считаем за одно. */
		clearTimeout(pending);
		pending = setTimeout(function () {
			var bc = window.BX && BX.Sale && BX.Sale.BasketComponent;
			if (!bc || !bc.result) {
				reload();
				return;
			}
			bc.sendRequest('refreshAjax', {});
			reloadIfNewItem();
		}, 100);
	}

	/* Сигнал первый: сама отправка товара в корзину. Тема шлёт её на
	   /ajax/item.php с add_item=Y и получает {"STATUS":"OK"}. Это самый
	   надёжный признак — он есть всегда, когда товар действительно добавлен. */
	if (window.jQuery) {
		jQuery(document).ajaxComplete(function (e, xhr, settings) {
			var url = settings && settings.url ? String(settings.url) : '';
			var data = settings && typeof settings.data === 'string' ? settings.data : '';
			if (url.indexOf('/ajax/item.php') === -1 || data.indexOf('add_item=Y') === -1) return;
			if (xhr && xhr.responseText && xhr.responseText.indexOf('"STATUS":"OK"') === -1) return;
			afterAdd();
		});
	}

	/* Сигнал второй: штатное событие темы после getActualBasket(). На проде
	   оно не приходит — nginx разворачивает /ajax/actualBasket.php в нижний
	   регистр (301), а такого файла на диске нет, запрос падает в 404. Держим
	   как запасной: локально и на regrutest событие работает. */
	if (window.BX) {
		BX.addCustomEvent('onCompleteAction', function (eventdata) {
			var action = eventdata && eventdata.action ? String(eventdata.action) : '';
			if (action.indexOf('loadActualBasket') !== 0) return;
			afterAdd();
		});
	}

	if (document.readyState !== 'loading') restoreScroll();
	else document.addEventListener('DOMContentLoaded', restoreScroll);
})();
</script>
