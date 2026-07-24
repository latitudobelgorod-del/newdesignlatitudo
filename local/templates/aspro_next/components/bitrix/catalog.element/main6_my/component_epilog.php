<?
	if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
	__IncludeLang($_SERVER["DOCUMENT_ROOT"].$templateFolder."/lang/".LANGUAGE_ID."/template.php");

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

global $arTheme, $arRegion;

?>

<?/*canonical*/?>
<?$APPLICATION->AddHeadString('<link href="https://'.SITE_SERVER_NAME.$arResult['DETAIL_PAGE_URL'].'" rel="canonical" />',true);?>
<?/*canonical*/?>


<?if($arResult["ID"]):?>

	<?
	$arTab = $arAllValues = $arSimilar = $arAssociated = $arNeedSelect = array();
	$bSimilar = $bAccessories = $bBigData = false;

	// cross sales for product
	$oCrossSales = new \Aspro\Next\CrossSales($arResult['ID'], $arParams);
	$arRules = $oCrossSales->getRules();

	// accessories goods from cross sales
	$arExpValues = $arRules['EXPANDABLES'] ? $oCrossSales->getItems('EXPANDABLES') : false;
	if($arRules['EXPANDABLES']){
		$templateData['EXPANDABLES'] = $arExpValues;
		$templateData['EXPANDABLES_FILTER'] = '';
	}

	// similar goods from cross sales
	$arAssociated = $arRules['ASSOCIATED'] ? $oCrossSales->getItems('ASSOCIATED') : false;
	if($arRules['ASSOCIATED']){
		$templateData['ASSOCIATED'] = $arAssociated;
		$templateData['ASSOCIATED_FILTER'] = '';
	}

	if(	$bAccessories = $templateData['EXPANDABLES'] || $templateData['EXPANDABLES_FILTER']	){
		$arTab['EXPANDABLES']['TITLE'] = ($arParams['DETAIL_EXPANDABLES_TITLE'] ? $arParams['DETAIL_EXPANDABLES_TITLE'] : GetMessage('EXPANDABLES_TITLE'));

		if($templateData['EXPANDABLES']){
			$arAllValues['EXPANDABLES'] = $templateData['EXPANDABLES'];
		}
		else{
			$arTab['EXPANDABLES']['FILTER'] = $templateData['EXPANDABLES_FILTER'];
		}
	}

	if( $bSimilar = $templateData['ASSOCIATED'] || $templateData['ASSOCIATED_FILTER'] ){
		$arTab['ASSOCIATED']['TITLE'] = ($arParams['DETAIL_ASSOCIATED_TITLE'] ? $arParams['DETAIL_ASSOCIATED_TITLE'] : GetMessage('ASSOCIATED_TITLE'));

		if($templateData['ASSOCIATED']){
			$arAllValues['ASSOCIATED'] = $templateData['ASSOCIATED'];
		}
		else{
			$arTab['ASSOCIATED']['FILTER'] = $templateData['ASSOCIATED_FILTER'];
		}
	}


	$bViewBlock = ($arParams['VIEW_BLOCK_TYPE'] === 'Y');

	$displayElementSlider = ($arParams['DISPLAY_ELEMENT_SLIDER'] ? $arParams['DISPLAY_ELEMENT_SLIDER'] : 10);
	$blockViewType = ($arParams['LIST_VIEW'] === 'block');	

	$bUseBigData = (ModuleManager::isModuleInstalled("sale") && (!isset($arParams['USE_BIG_DATA']) || $arParams['USE_BIG_DATA'] != 'N'));
	
	$disply_elements=($arParams["DISPLAY_ELEMENT_SLIDER"] ? $arParams["DISPLAY_ELEMENT_SLIDER"] : 10);
	$defaultBlockOrder = 'tizers,complect,nabor,offers,desc,char,galery,video,stores,exp_goods,reviews,gifts,ask,services,docs,custom_tab,goods,recomend_goods,podborki,blog,assoc_goods';
	$strBlockOrder = isset($arParams["DETAIL_BLOCKS_ALL_ORDER"]) ? $arParams["DETAIL_BLOCKS_ALL_ORDER"] : $defaultBlockOrder;
	$arBlockOrder = explode(",", $strBlockOrder);
	?>

	<?$arConfig = [];
	if ($blockViewType) {
		$arConfig[] = "bitrix:catalog.section";
		$arConfig[] = "catalog_block";
	} else {
		$arConfig[] = "bitrix:catalog.top";
		$arConfig[] = "main";
	}
	?>



<?if( $templateData['BRAND_ITEM'] || $bUseBigData ):?>
	<div class="row wdesc">
		<div class="col-md-9 ">
<?endif;?>
	<div class="tabs_section type_more">
		<?
		// sale stock
		$arResult['STOCK'] = array();

		$stockIblockId = $arParams["IBLOCK_STOCK_ID"];
		if(
			!$stockIblockId &&
			!empty($templateData["LINK_SALE"]["VALUE"]) &&
			$templateData["LINK_SALE"]["LINK_IBLOCK_ID"]
		){
			$stockIblockId = $templateData["LINK_SALE"]["LINK_IBLOCK_ID"];
		}

		if(
			$stockIblockId &&
			CNextCache::$arIBlocksInfo[$stockIblockId] &&
			CNextCache::$arIBlocksInfo[$stockIblockId]['ACTIVE'] === 'Y'
		){
			$arSelect = array(
				"ID",
				"IBLOCK_ID",
				"IBLOCK_SECTION_ID",
				"NAME",
				"PREVIEW_PICTURE",
				"PREVIEW_TEXT",
				"DETAIL_PAGE_URL",
				"PROPERTY_REDIRECT",
			);

			$arFilterStock = array(
				"IBLOCK_ID" => $stockIblockId,
				"ACTIVE" => "Y",
				"ACTIVE_DATE" => "Y",
				'!PROPERTY_LINK_GOODS_FILTER_VALUE' => false,
			);

			if(
				$arTheme['USE_REGIONALITY']['VALUE'] === 'Y' &&
				$arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y' &&
				$arParams['USE_REGION'] === 'Y'
			){
				$arFilterStock['PROPERTY_LINK_REGION'] = $arRegion['ID'];
			}

			$arStocksWithFilterGoods = CNextCache::CIBLockElement_GetList(
				array(
					'CACHE' => array(
						"TAG" => CNextCache::GetIBlockCacheTag($stockIblockId),
						"GROUP" => "ID"
					)
				),
				$arFilterStock,
				false,
				false,
				array_merge(
					$arSelect,
					array(
						'PROPERTY_LINK_GOODS_FILTER',
						'PROPERTY_LINK_GOODS',
					)
				)
			);

			if($arStocksWithFilterGoods){
				foreach($arStocksWithFilterGoods as $key => $arStock){
					$cond = new CNextCondition();
					try{
					    $arTmpGoods = \Bitrix\Main\Web\Json::decode($arStock['~PROPERTY_LINK_GOODS_FILTER_VALUE']);
					    $arGoodsFilter = $cond->parseCondition($arTmpGoods, $arParams);
					}
					catch(\Exception $e){
					    $arGoodsFilter = array();
					}
					unset($cond);

					if(
						$arTmpGoods["CHILDREN"] &&
						$arGoodsFilter
					){
						$arFilterStock = array(
							"LOGIC" => "AND",
							array(
								"IBLOCK_ID" => $arParams["IBLOCK_ID"],
								"ACTIVE" => "Y",
								'ID' => $arResult['ID'],
							),
							array($arGoodsFilter),
						);

						$cnt = CNextCache::CIBLockElement_GetList(
							array(
								'CACHE' => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))
							),
							$arFilterStock,
							array()
						);
						if($cnt){
							$arResult['STOCK'][$arStock['ID']] = $arStock;
						}
					}
					else{
						unset($arStocksWithFilterGoods[$key]);
					}
				}
			}

			$arFilterStock = array(
				'PROPERTY_LINK_GOODS' => $arResult['ID'],
			);
			if($arStocksWithFilterGoods){
				$arFilterStock['!ID'] = array_column($arStocksWithFilterGoods, 'ID');
			}

			if(
				!empty($templateData["LINK_SALE"]["VALUE"]) &&
				$templateData["LINK_SALE"]["LINK_IBLOCK_ID"]
			){
				$arFilterStock = array(
					array(
						'LOGIC' => 'OR',
						array('ID' => $templateData['LINK_SALE']['VALUE']),
						array($arFilterStock),
					)
				);
			}

			$arFilterStock["IBLOCK_ID"] = $stockIblockId;
			$arFilterStock["ACTIVE"] = "Y";
			$arFilterStock["ACTIVE_DATE"] = "Y";

			if(
				$arTheme['USE_REGIONALITY']['VALUE'] === 'Y' &&
				$arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y' &&
				$arParams['USE_REGION'] === 'Y'
			){
				$arFilterStock['PROPERTY_LINK_REGION'] = $arRegion['ID'];
			}

			$arResult['STOCK'] += CNextCache::CIBLockElement_GetList(
				array(
					'CACHE' => array(
						"TAG" => CNextCache::GetIBlockCacheTag($stockIblockId),
						"GROUP" => "ID"
					)
				),
				$arFilterStock,
				false,
				false,
				$arSelect
			);
			?>
			<?if(is_array($arResult["STOCK"]) && $arResult["STOCK"]):?>
				<div class="stock_wrapper" style="display:none;">
					<?foreach($arResult["STOCK"] as $key => $arStock):?>
						<div class="stock_board <?=($arStock["PREVIEW_TEXT"] ? '' : 'nt');?>">
							<div class="title">
								<?if(isset($arStock["PROPERTY_REDIRECT_VALUE"]) && strlen($arStock["PROPERTY_REDIRECT_VALUE"])):?>
									<a class="dark_link" href="<?=$arStock["PROPERTY_REDIRECT_VALUE"]?>"><?=$arStock["NAME"];?></a>
								<?else:?>
									<a class="dark_link" href="<?=$arStock["DETAIL_PAGE_URL"]?>"><?=$arStock["NAME"];?></a>
								<?endif;?>
							</div>
							<div class="txt"><?=$arStock["PREVIEW_TEXT"]?></div>
						</div>
					<?endforeach;?>
				</div>
			<?endif;?>
			<?
		}
		// end sale stock

		// services
		$arResult['SERVICES'] = array();

		$servicesIblockId = CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_services"][0];
		if(
			!$servicesIblockId &&
			!empty($templateData["LINK_SERVICES"]["VALUE"]) &&
			$templateData["LINK_SERVICES"]["LINK_IBLOCK_ID"]
		){
			$servicesIblockId = $templateData["LINK_SERVICES"]["LINK_IBLOCK_ID"];
		}

		if(
			$servicesIblockId &&
			CNextCache::$arIBlocksInfo[$servicesIblockId] &&
			CNextCache::$arIBlocksInfo[$servicesIblockId]['ACTIVE'] === 'Y'
		){
			$arSelect = array(
				"ID",
				"IBLOCK_ID",
			);

			$arFilterService = array(
				"IBLOCK_ID" => $servicesIblockId,
				"ACTIVE" => "Y",
				"ACTIVE_DATE" => "Y",
				'!PROPERTY_LINK_GOODS_FILTER_VALUE' => false,
			);

			if(
				$arTheme['USE_REGIONALITY']['VALUE'] === 'Y' &&
				$arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y' &&
				$arParams['USE_REGION'] === 'Y'
			){
				$arFilterService['PROPERTY_LINK_REGION'] = $arRegion['ID'];
			}

			$arServicesWithFilterGoods = CNextCache::CIBLockElement_GetList(
				array(
					'CACHE' => array(
						"TAG" => CNextCache::GetIBlockCacheTag($servicesIblockId),
						"GROUP" => "ID"
					)
				),
				$arFilterService,
				false,
				false,
				array_merge(
					$arSelect,
					array(
						'PROPERTY_LINK_GOODS_FILTER',
						'PROPERTY_LINK_GOODS',
					)
				)
			);

			if($arServicesWithFilterGoods){
				foreach($arServicesWithFilterGoods as $key => $arService){
					$cond = new CNextCondition();
					try{
					    $arTmpGoods = \Bitrix\Main\Web\Json::decode($arService['PROPERTY_LINK_GOODS_FILTER_VALUE']);
					    $arGoodsFilter = $cond->parseCondition($arTmpGoods, $arParams);
					}
					catch(\Exception $e){
					    $arGoodsFilter = array();
					}
					unset($cond);

					if(
						$arTmpGoods["CHILDREN"] &&
						$arGoodsFilter
					){
						$arFilterService = array(
							"LOGIC" => "AND",
							array(
								"IBLOCK_ID" => $arParams["IBLOCK_ID"],
								"ACTIVE" => "Y",
								'ID' => $arResult['ID'],
							),
							array($arGoodsFilter),
						);

						$cnt = CNextCache::CIBLockElement_GetList(
							array(
								'CACHE' => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))
							),
							$arFilterService,
							array()
						);
						if($cnt){
							$arResult['SERVICES'][$arService['ID']] = $arService;
						}
					}
					else{
						unset($arServicesWithFilterGoods[$key]);
					}
				}
			}

			$arFilterService = array(
				'PROPERTY_LINK_GOODS' => $arResult['ID'],
			);
			if($arServicesWithFilterGoods){
				$arFilterService['!ID'] = array_column($arServicesWithFilterGoods, 'ID');
			}

			if(
				!empty($templateData["LINK_SERVICES"]["VALUE"]) &&
				$templateData["LINK_SERVICES"]["LINK_IBLOCK_ID"]
			){
				$arFilterService = array(
					array(
						'LOGIC' => 'OR',
						array('ID' => $templateData['LINK_SERVICES']['VALUE']),
						array($arFilterService),
					)
				);
			}

			$arFilterService["IBLOCK_ID"] = $servicesIblockId;
			$arFilterService["ACTIVE"] = "Y";
			$arFilterService["ACTIVE_DATE"] = "Y";

			if(
				$arTheme['USE_REGIONALITY']['VALUE'] === 'Y' &&
				$arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y' &&
				$arParams['USE_REGION'] === 'Y'
			){
				$arFilterService['PROPERTY_LINK_REGION'] = $arRegion['ID'];
			}

			if($arServices = CNextCache::CIBLockElement_GetList(
				array(
					'CACHE' => array(
						"TAG" => CNextCache::GetIBlockCacheTag($servicesIblockId),
						"GROUP" => "ID"
					)
				),
				$arFilterService,
				false,
				false,
				$arSelect
			)){
				$arResult['SERVICES'] += $arServices;
			}
		}
		// end services

		$templateData["STORES"]["SITE_ID"] = SITE_ID;
		$bShowStores = ( $templateData["STORES"]['USE_STORES'] && $templateData["STORES"]["STORES"] );
		?>
		<?foreach($arBlockOrder as $code):?>
			<?//nabor?>
			<?if($code == 'nabor'):?>
				<?if($templateData['OFFERS_INFO']['OFFERS']):?>
					<?if($templateData['OFFERS_INFO']['OFFER_GROUP']):?>
						<?foreach($templateData['OFFERS_INFO']['OFFERS'] as $offerId => $arOfferGroup):?>
							<?if(!$arOfferGroup) continue;?>
							<span id="<?=$templateData['ID_OFFER_GROUP'].$offerId?>"  style="display: none;">
								<?$APPLICATION->IncludeComponent("bitrix:catalog.set.constructor", "",
									array(
										"IBLOCK_ID" => $templateData['OFFERS_INFO']["OFFERS_IBLOCK"],
										"ELEMENT_ID" => $offerId,
										"PRICE_CODE" => $arParams["PRICE_CODE"],
										"BASKET_URL" => $arParams["BASKET_URL"],
										"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
										"CACHE_TYPE" => $arParams["CACHE_TYPE"],
										"CACHE_TIME" => $arParams["CACHE_TIME"],
										"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
										"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
										"BUNDLE_ITEMS_COUNT" => $arParams["BUNDLE_ITEMS_COUNT"],
										"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
										"SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
										"CONVERT_CURRENCY" => $arParams['CONVERT_CURRENCY'],
										"CURRENCY_ID" => $arParams["CURRENCY_ID"]
									), $component, array("HIDE_ICONS" => "Y")
								);?>
							</span>
						<?endforeach;?>
					<?endif;?>
				<?else:?>
					<?$APPLICATION->IncludeComponent("bitrix:catalog.set.constructor", "",
						array(
							"IBLOCK_ID" => $arParams["IBLOCK_ID"],
							"ELEMENT_ID" => $arResult["ID"],
							"PRICE_CODE" => $arParams["PRICE_CODE"],
							"BASKET_URL" => $arParams["BASKET_URL"],
							"CACHE_TYPE" => $arParams["CACHE_TYPE"],
							"CACHE_TIME" => $arParams["CACHE_TIME"],
							"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
							"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
							"BUNDLE_ITEMS_COUNT" => $arParams["BUNDLE_ITEMS_COUNT"],
							"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
							"SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
							"CONVERT_CURRENCY" => $arParams['CONVERT_CURRENCY'],
							"CURRENCY_ID" => $arParams["CURRENCY_ID"]
						), $component, array("HIDE_ICONS" => "Y")
					);?>
				<?endif;?>

			<?//complect?>
			<?elseif($code == 'complect'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_KIT_BLOCK')?>

			<?//gifts?>
			<?elseif($code == 'gifts'):?>

				

			<?//tizers?>
			<?elseif($code == 'tizers' && $arBlockOrder[0] != 'tizers'):?>
				<?$APPLICATION->ShowViewContent('TIZERS_BLOCK')?>

			<?//services?>
			<?elseif( $code == 'services' ):?>
		

			<?//goods?>
			<?elseif($code == 'goods' && !$bViewBlock):?>

					<?if(
						$bAccessories ||
						$bSimilar
					):?>
				

					<?endif;?>

			<?//exp_goods?>
			<?elseif($code == 'exp_goods' && $bViewBlock):?>
		

			<?//assoc_goods?>
			<?elseif($code == 'assoc_goods' && $bViewBlock):?>
				

			<?//stores?>
			<?elseif($code == 'stores'):?>
			

			<?//ask?>
			<?elseif($code == 'ask'):?>
				

			<?//reviews?>
			<?elseif($code == 'reviews'):?>
			

			<?//blog?>
			<?elseif($code == 'blog'):?>

					<?$GLOBALS['arrFilterBlog'] = array("PROPERTY_LINK_GOODS" => $arResult["ID"]);?>



			<?//podborki?>
			<?elseif($code == 'podborki'):?>



			<?//offers?>
			<?elseif($code == 'offers'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_OFFERS_INFO')?>

			<?//desc?>
			<?elseif($code == 'desc'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_DETAIL_TEXT_INFO')?>

			<?//char?>
			<?elseif($code == 'char'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_PROPS_INFO')?>

			<?//video?>
			<?elseif($code == 'video'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_VIDEO_INFO')?>

			<?//show buy block?>
			<?elseif($code == 'buy'):?>
			<?$APPLICATION->ShowViewContent('PRODUCT_HOW_BUY_INFO')?>
			
			<?//show delivery block?>
			<?elseif($code == 'delivery'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_DELIVERY_INFO')?>

			<?//show payment block?>
			<?elseif($code == 'payment'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_PAYMENT_INFO')?>

			<?//docs?>
			<?elseif($code == 'docs'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_FILES_INFO')?>

			<?//galery?>
			<?elseif($code == 'galery'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_ADDITIONAL_GALLERY_INFO')?>

			<?//custom_tab?>
			<?elseif($code == 'custom_tab'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_CUSTOM_TAB_INFO')?>

			<?endif;?>

		<?endforeach;?>








	</div>
<?if($templateData['BRAND_ITEM'] || (\Bitrix\Main\ModuleManager::isModuleInstalled("sale") && (!isset($arParams['USE_BIG_DATA']) || $arParams['USE_BIG_DATA'] != 'N'))):?>
	</div>
<?endif;?>




	<?if($templateData['BRAND_ITEM'] || $bUseBigData):?>

	</div>
	<?endif;?>




	<script type="text/javascript">
		//if($('#element_expandables').length && $('#element_expandables_place').length){
			//$('#element_expandables').insertAfter($('#element_expandables_place'));
			//$('#element_expandables .flexslider').removeClass('flexslider-init');
			//InitFlexSlider();
			//$('#element_expandables').removeClass('hidden');
		//}
		//$('#element_expandables_place').remove();

		//if($(".wraps.product_reviews_tab").length && $("#reviews_content").length){
			//$("#reviews_content").insertAfter($(".wraps.product_reviews_tab h4"));
		//}
		//if($("#ask_block_content").length && $("#ask_block").length){
			//$("#ask_block_content").appendTo($("#ask_block"));
		//}
		//if($(".gifts").length && $("#reviews_content").length){
			//$(".gifts").insertAfter($("#reviews_content"));
		//}
		if($("#reviews_content").length){
		 	$("#reviews_content").show();
		}
		if(!$(".stores_tab").length){
			$('.item-stock .store_view').removeClass('store_view');
		}
		viewItemCounter('<?=$arResult["ID"];?>','<?=current($arParams["PRICE_CODE"]);?>');
	</script>
<?endif;?>
<?if (isset($templateData['TEMPLATE_LIBRARY']) && !empty($templateData['TEMPLATE_LIBRARY'])){
	$loadCurrency = false;
	if (!empty($templateData['CURRENCIES']))
		$loadCurrency = Loader::includeModule('currency');
	CJSCore::Init($templateData['TEMPLATE_LIBRARY']);
	if ($loadCurrency){?>
		<script type="text/javascript">
			BX.Currency.setCurrencies(<? echo $templateData['CURRENCIES']; ?>);
		</script>
	<?}
}?>
<script type="text/javascript">
var viewedCounter = {
	path: '/bitrix/components/bitrix/catalog.element/ajax.php',
	params: {
		AJAX: 'Y',
		SITE_ID: "<?= SITE_ID ?>",
		PRODUCT_ID: "<?= $arResult['ID'] ?>",
		PARENT_ID: "<?= $arResult['ID'] ?>"
	}
};
BX.ready(
	BX.defer(function(){
		$('body').addClass('detail_page');
		BX.ajax.post(
			viewedCounter.path,
			viewedCounter.params
		);
	})
);
</script>
<?$des = new \Bitrix\Main\Page\FrameStatic('des');$des->startDynamicArea();?>
<script>
	insertElementStoreBlock = function(html){
		if(
			typeof map === 'object' &&
			map && typeof map.destroy === 'function'
		){
			// there is a map on the page
			map.destroy();
		}

		html = html.replace('this.parentNode.removeChild(script);', 'try{this.parentNode.removeChild(script);} catch(e){}');
		html = html.replace('(document.head || document.documentElement).appendChild(script);', '(typeof ymaps === \'undefined\') && (document.head || document.documentElement).appendChild(script);');

		var ob = BX.processHTML(html)
		$('.tabs_section .stores_tab').html(ob.HTML);
		BX.ajax.processScripts(ob.SCRIPT);


		if($('.stores_wrapper .stores_tab').siblings('h4').length){
			if($('.stores_wrapper > h4 .stores-title').length){
				$('.stores_wrapper > h4 .stores-title').remove();
			}

			$('.stores_wrapper .stores_tab .stores-title').appendTo($('.stores_wrapper .stores_tab').siblings('h4'));
		}
	}

	setElementStore = function(check, oid){
		if(typeof check !== 'undefined' && check == "Y")
			return;

		if($('.stores_tab').length )
		{
			var objUrl = parseUrlQuery(),
				oidValue = '',
				add_url = '';
			if('clear_cache' in objUrl)
			{
				if(objUrl.clear_cache == 'Y')
					add_url = '?clear_cache=Y';
			}
			if('oid' in objUrl)
			{
				if(parseInt(objUrl.oid)>0)
					oidValue = objUrl.oid;
			}
			if(typeof oid !== 'undefined' && parseInt(oid)>0)
			{
				oidValue = oid;
			}
			if(oidValue)
			{
				if(add_url)
					add_url +='&oid='+oidValue;
				else
					add_url ='?oid='+oidValue;
			}

			$.ajax({
				type:"POST",
				url:arNextOptions['SITE_DIR']+"ajax/productStoreAmount.php"+add_url,
				data:<?=CUtil::PhpToJSObject($templateData["STORES"], false, true, true)?>,
				success: function(html){
					if(html.indexOf('new ymaps.Map') !== -1){
						// there is a map in response
						if(typeof setElementStore.mapListner === 'undefined'){
							setElementStore.wait = false;

							window.addEventListener('message', setElementStore.mapListner = function(event){
								if(typeof event.data === 'string'){
									if(
										event.data.indexOf('ready') !== -1 &&
										event.origin.indexOf('maps.ya') !== -1
									){
										// message ready recieved from yandex maps
										setTimeout(function(){
											if(typeof setElementStore.lastHtml !== 'undefined'){
												// insert the last
												insertElementStoreBlock(setElementStore.lastHtml);
												delete setElementStore.lastHtml;
											}
											else{
												setElementStore.wait = false;
											}
										}, 50);
									}
								}
							});
						}

						if(setElementStore.wait){
							// save response until not ready
							setElementStore.lastHtml = html;
						}
						else{
							// insert the first
							setElementStore.wait = true;
							insertElementStoreBlock(html);
						}
					}
					else{
						// there is no a map on the page
						insertElementStoreBlock(html);
					}
				}
			});
		}
	}
BX.ready(
	BX.defer(function(){
		setElementStore('<?=$templateData["STORES"]["OFFERS"];?>');
	})
);
</script>
<?$des->finishDynamicArea();?>
<?if(isset($_GET["RID"])){?>
	<?if($_GET["RID"]){?>
		<script>
			$(document).ready(function() {
				$("<div class='rid_item' data-rid='<?=htmlspecialcharsbx($_GET["RID"]);?>'></div>").appendTo($('body'));
			});
		</script>
	<?}?>
<?}?>

<?
$arExt = [];

if ($arParams['USE_REVIEW'])
	array_push($arExt, 'swiper', 'swiper_init');

if($templateData['SHOW_VIDEO']){
	$arExt[] = 'grid_list';
	\CJSCore::init(['player']);
}

\Aspro\Next\Functions\Extensions::init($arExt);
?>