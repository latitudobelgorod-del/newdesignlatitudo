<?
	if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
	__IncludeLang($_SERVER["DOCUMENT_ROOT"].$templateFolder."/lang/".LANGUAGE_ID."/template.php");

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

global $arTheme, $arRegion;
?>

<?/* Канонический адрес карточки. У товара один адрес, но открыть его можно
     по-разному: ?pid= у торговых предложений, короткие ссылки, utm-метки,
     привязка к нескольким разделам. Без canonical поисковик выбирает главную
     версию сам (проверка микроразметки, 4 сентября 2026).

     Хост берём текущий: у региональных поддоменов канониклом должна быть их
     собственная страница, иначе регионы выпадут из индекса.

     DETAIL_PAGE_URL кладёт в результат result_modifier, в блоке с пометкой
     canonical. */?>
<? if ($arResult['DETAIL_PAGE_URL']): ?>
	<? $APPLICATION->AddHeadString('<link href="https://'.$_SERVER['HTTP_HOST'].$arResult['DETAIL_PAGE_URL'].'" rel="canonical" />', true); ?>
<? endif; ?>

<?if($arResult["ID"]):?>
	<?
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

	$bUseBigData = (ModuleManager::isModuleInstalled("sale") && (!isset($arParams['USE_BIG_DATA']) || $arParams['USE_BIG_DATA'] != 'N'));

	$defaultBlockOrder = 'tizers,complect,nabor,offers,desc,char,galery,video,stores,exp_goods,reviews,gifts,ask,services,docs,custom_tab,goods,recomend_goods,podborki,blog,assoc_goods';
	$strBlockOrder = isset($arParams["DETAIL_BLOCKS_ALL_ORDER"]) ? $arParams["DETAIL_BLOCKS_ALL_ORDER"] : $defaultBlockOrder;
	$arBlockOrder = explode(",", $strBlockOrder);
	
	$blockViewType = ($arParams['LIST_VIEW'] === 'block');
	?>

	<?$arConfig = [];
	if ($blockViewType) {
		$arConfig[] = "bitrix:catalog.section";
		$arConfig[] = "catalog_block";
	} else {
		$arConfig[] = "bitrix:catalog.top";
		$arConfig[] = "main";
	}?>



	<?if( $templateData['BRAND_ITEM'] || $bUseBigData ):?>
	<div class="row wdesc">
		<div class="col-md-9 ">
<?endif;?>

<?/*Тут закроем*/?>

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
				"DATE_ACTIVE_TO",
				"PROPERTY_REDIRECT",
				"MIN_PRICE",
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
				
				
				
 <?
global $arFilterActiya; 
$arFilterActiya = array("PROPERTY_LINK_SECT_CATALOG" => $arSection["ID"],"PROPERTY_LINK_REGION" => $arRegion['ID'],"PROPERTY_LINK_GOODS" => $arResult['ID'], "ACTIVE" => "Y", "ACTIVE_DATE" => "Y",">=DATE_ACTIVE_TO" => ConvertDateTime(date('d.m.Y'), "DD.MM.YYYY"),
                                        "<=DATE_ACTIVE_FROM" => array(false, ConvertTimeStamp(false, "FULL")));
 ?> 
 		
			<?$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"akciya_detail_el", 
	[
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"AJAX_MODE" => "Y",
		"IBLOCK_TYPE" => "aspro_next_content",
		"IBLOCK_ID" => "17",
		"NEWS_COUNT" => "4",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_ORDER1" => "DESC",
		"SORT_BY2" => "SORT",
		"SORT_ORDER2" => "ASC",
		"FILTER_NAME" => "arFilterActiya",
		"FIELD_CODE" => [
			0 => "ID",
			1 => "NAME",
			2 => "PREVIEW_PICTURE",
			3 => "PREVIEW_TEXT",
			4 => "DATE_ACTIVE_TO",
			5 => "DATE_ACTIVE_FROM",
			6 => "ACTIVE_FROM",
			7 => "",
		],
		"PROPERTY_CODE" => [
			0 => "",
			1 => "DESCRIPTION",
			2 => "LINK_SECT_CATALOG",
			3 => "LINK_REGION",
			4 => "LINK_GOODS",
			5 => "PERIOD",
		],
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"SET_TITLE" => "Y",
		"SET_BROWSER_TITLE" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_META_DESCRIPTION" => "Y",
		"SET_LAST_MODIFIED" => "Y",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
		"ADD_SECTIONS_CHAIN" => "Y",
		"HIDE_LINK_WHEN_NO_DETAIL" => "Y",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"INCLUDE_SUBSECTIONS" => "Y",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"CACHE_FILTER" => "Y",
		"CACHE_GROUPS" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"PAGER_TITLE" => "Новости",
		"PAGER_SHOW_ALWAYS" => "Y",
		"PAGER_TEMPLATE" => "",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"SET_STATUS_404" => "Y",
		"SHOW_404" => "Y",
		"MESSAGE_404" => "",
		"PAGER_BASE_LINK" => "",
		"PAGER_PARAMS_NAME" => "arrPager",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"COMPONENT_TEMPLATE" => "akciya",
		"STRICT_SECTION_CHECK" => "N",
		"FILE_404" => "",
		"IMAGE_POSITION" => "left",
		"SHOW_DETAIL_LINK" => "Y",
		"USE_SHARE" => "N",
		"S_ASK_QUESTION" => "",
		"S_ORDER_SERVICE" => "",
		"T_GALLERY" => "",
		"T_DOCS" => "",
		"T_GOODS" => "",
		"T_SERVICES" => "",
		"T_PROJECTS" => "",
		"T_REVIEWS" => "",
		"T_STAFF" => ""
	],
	false
);?>

				
				
				<?/*
				
				<div class="row">
							
					<?foreach($arResult["STOCK"] as $key => $arStock):?>						
						<?	
						$ID_PICTYRE = $arStock["PREVIEW_PICTURE"];
						$URLf = CFile::GetPath($ID_PICTYRE);
						$DATE_SALE = $arStock["DATE_ACTIVE_TO"];
						?>
						<?$convers1 = ConvertDateTime($arStock["DATE_ACTIVE_TO"], "DD.MM.YYYY", "ru");
						$date_activeto1 = MakeTimeStamp($arStock["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
						$convers = ConvertDateTime($arStock["DATE_ACTIVE_TO"], "YYYY-MM-DD HH:MI:SS", "ru");
						$date_activeto = MakeTimeStamp($arStock["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
						
						$objDateTime = new DateTime();
						$time_now = $objDateTime->getTimestamp();

						?>

						<div class="col-md-6">
						<a  href="<?=$arStock["PROPERTY_REDIRECT_VALUE"]?>"><img class="preview_picture" width="280" height="183" src="<?=$URLf?>"  /></a>
						<div class="stock_board <?=($arStock["PREVIEW_TEXT"] ? '' : 'nt');?>">
							<div class="title">
								<?if(isset($arStock["PROPERTY_REDIRECT_VALUE"]) && strlen($arStock["PROPERTY_REDIRECT_VALUE"])):?>
									<a class="dark_link" href="<?=$arStock["PROPERTY_REDIRECT_VALUE"]?>"><?=$arStock["NAME"];?></a>
								<?else:?>
									<a class="dark_link" href="<?=$arStock["DETAIL_PAGE_URL"]?>"><?=$arStock["NAME"];?></a>
								<?endif;?>
							</div>
				<div class="txt">
				
				<?if (($date_activeto) > ($time_now)):?>		
					Акция действительна до <?=$convers1?>
				<?else:?>
					Акция завершена
				<?endif;?>							
			</div>
</div>
</div>
						
					<?endforeach;?>
				</div>

*/?>

				</div>
			<?endif;?>
			<?
		}
		// end sale stock

?>



<?/*



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

				<div class="gifts drag_block_detail">
					<?if ($templateData['GIFTS_PARAMS']['CATALOG'] && $arParams['USE_GIFTS_DETAIL'] == 'Y' && \Bitrix\Main\ModuleManager::isModuleInstalled("sale"))
					{
						$APPLICATION->IncludeComponent("bitrix:sale.gift.product", "main", array(
								//"USE_REGION" => $arParams['USE_REGION'] !== 'N' ? $arParams['USE_REGION'] : 'N',
								"USE_REGION" => ($arRegion ? ($GLOBALS['arTheme']['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y' ? $arRegion['ID'] : "Y") : "N"),
								"STORES" => $arParams['STORES'],
								"SHOW_UNABLE_SKU_PROPS"=>$arParams["SHOW_UNABLE_SKU_PROPS"],
								'PRODUCT_ID_VARIABLE' => $arParams['PRODUCT_ID_VARIABLE'],
								'ACTION_VARIABLE' => $arParams['ACTION_VARIABLE'],
								'BUY_URL_TEMPLATE' => $templateData['GIFTS_PARAMS']['BUY_URL_TEMPLATE'],
								'ADD_URL_TEMPLATE' => $templateData['GIFTS_PARAMS']['ADD_URL_TEMPLATE'],
								'SUBSCRIBE_URL_TEMPLATE' => $templateData['GIFTS_PARAMS']['SUBSCRIBE_URL_TEMPLATE'],
								'COMPARE_URL_TEMPLATE' => $templateData['GIFTS_PARAMS']['COMPARE_URL_TEMPLATE'],
								"OFFER_HIDE_NAME_PROPS" => $arParams["OFFER_HIDE_NAME_PROPS"],

								"SHOW_DISCOUNT_PERCENT" => $arParams['GIFTS_SHOW_DISCOUNT_PERCENT'],
								"SHOW_OLD_PRICE" => $arParams['GIFTS_SHOW_OLD_PRICE'],
								"PAGE_ELEMENT_COUNT" => $arParams['GIFTS_DETAIL_PAGE_ELEMENT_COUNT'],
								"LINE_ELEMENT_COUNT" => $arParams['GIFTS_DETAIL_PAGE_ELEMENT_COUNT'],
								"HIDE_BLOCK_TITLE" => $arParams['GIFTS_DETAIL_HIDE_BLOCK_TITLE'],
								"BLOCK_TITLE" => $arParams['GIFTS_DETAIL_BLOCK_TITLE'],
								"TEXT_LABEL_GIFT" => $arParams['GIFTS_DETAIL_TEXT_LABEL_GIFT'],
								"SHOW_NAME" => $arParams['GIFTS_SHOW_NAME'],
								"SHOW_IMAGE" => $arParams['GIFTS_SHOW_IMAGE'],
								"MESS_BTN_BUY" => $arParams['GIFTS_MESS_BTN_BUY'],

								"SHOW_PRODUCTS_{$arParams['IBLOCK_ID']}" => "Y",
								"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
								"PRODUCT_SUBSCRIPTION" => $arParams["PRODUCT_SUBSCRIPTION"],
								"MESS_BTN_DETAIL" => $arParams["MESS_BTN_DETAIL"],
								"MESS_BTN_SUBSCRIBE" => $arParams["MESS_BTN_SUBSCRIBE"],
								"TEMPLATE_THEME" => $arParams["TEMPLATE_THEME"],
								"PRICE_CODE" => $arParams["PRICE_CODE"],
								"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
								"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
								"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
								"CURRENCY_ID" => $arParams["CURRENCY_ID"],
								"BASKET_URL" => $arParams["BASKET_URL"],
								"ADD_PROPERTIES_TO_BASKET" => $arParams["ADD_PROPERTIES_TO_BASKET"],
								"PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
								"PARTIAL_PRODUCT_PROPERTIES" => $arParams["PARTIAL_PRODUCT_PROPERTIES"],
								"USE_PRODUCT_QUANTITY" => 'N',
								"OFFER_TREE_PROPS_{$templateData['OFFERS_INFO']["OFFERS_IBLOCK"]}" => $arParams['OFFER_TREE_PROPS'],
								"CART_PROPERTIES_{$templateData['OFFERS_INFO']["OFFERS_IBLOCK"]}" => $arParams['OFFERS_CART_PROPERTIES'],
								"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
								"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
								"SHOW_DISCOUNT_TIME" => $arParams["SHOW_DISCOUNT_TIME"],
								"SHOW_DISCOUNT_PERCENT_NUMBER" => $arParams["SHOW_DISCOUNT_PERCENT_NUMBER"],
								"SALE_STIKER" => $arParams["SALE_STIKER"],
								"STIKERS_PROP" => $arParams["STIKERS_PROP"],
								"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
								"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
								"DISPLAY_TYPE" => "block",
								"SHOW_RATING" => $arParams["SHOW_RATING"],
								"DISPLAY_COMPARE" => ($arParams["DISPLAY_COMPARE"] ? "Y" : "N"),
								"DISPLAY_WISH_BUTTONS" => $arParams["DISPLAY_WISH_BUTTONS"],
								"DEFAULT_COUNT" => $arParams["DEFAULT_COUNT"],
								"TYPE_SKU" => "Y",
								"REVIEWS_VIEW" => $arParams["REVIEWS_VIEW"] == 'EXTENDED',

								"POTENTIAL_PRODUCT_TO_BUY" => array(
									'ID' => isset($arResult['ID']) ? $arResult['ID'] : null,
									'MODULE' => $templateData['GIFTS_PARAMS']['MODULE'],
									'PRODUCT_PROVIDER_CLASS' => $templateData['GIFTS_PARAMS']['PRODUCT_PROVIDER_CLASS'],
									'QUANTITY' => $templateData['GIFTS_PARAMS']['QUANTITY'],
									'IBLOCK_ID' => $templateData['GIFTS_PARAMS']['IBLOCK_ID'],

									'PRIMARY_OFFER_ID' => (
										isset($templateData['OFFERS_INFO']['OFFERS']) && is_array($templateData['OFFERS_INFO']['OFFERS'])
											? array_key_first($templateData['OFFERS_INFO']['OFFERS'])
											: ''
									),
									'SECTION' => array(
										'ID' => $templateData['GIFTS_PARAMS']['SECTION_ID'],
										'IBLOCK_ID' => $templateData['GIFTS_PARAMS']['SECTION_IBLOCK_ID'],
										'LEFT_MARGIN' => $templateData['GIFTS_PARAMS']['SECTION_LEFT_MARGIN'],
										'RIGHT_MARGIN' => $templateData['GIFTS_PARAMS']['SECTION_RIGHT_MARGIN'],
									),
								)
							), $component, array("HIDE_ICONS" => "Y"));
					}
					if ($templateData['GIFTS_PARAMS']['CATALOG'] && $arParams['USE_GIFTS_MAIN_PR_SECTION_LIST'] == 'Y' && \Bitrix\Main\ModuleManager::isModuleInstalled("sale"))
					{
						$APPLICATION->IncludeComponent(
								"bitrix:sale.gift.main.products",
								"main",
								array(
									//"USE_REGION" => $arParams['USE_REGION'] !== 'N' ? $arParams['USE_REGION'] : 'N',
									"USE_REGION" => ($arRegion ? ($GLOBALS['arTheme']['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y' ? $arRegion['ID'] : "Y") : "N"),
									"STORES" => $arParams['STORES'],
									"SHOW_UNABLE_SKU_PROPS"=>$arParams["SHOW_UNABLE_SKU_PROPS"],
									"PAGE_ELEMENT_COUNT" => $arParams['GIFTS_MAIN_PRODUCT_DETAIL_PAGE_ELEMENT_COUNT'],
									"BLOCK_TITLE" => $arParams['GIFTS_MAIN_PRODUCT_DETAIL_BLOCK_TITLE'],

									"OFFERS_FIELD_CODE" => $arParams["OFFERS_FIELD_CODE"],
									"OFFERS_PROPERTY_CODE" => $arParams["OFFERS_PROPERTY_CODE"],

									"AJAX_MODE" => $arParams["AJAX_MODE"],
									"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
									"IBLOCK_ID" => $arParams["IBLOCK_ID"],

									"ELEMENT_SORT_FIELD" => 'ID',
									"ELEMENT_SORT_ORDER" => 'DESC',
									//"ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
									//"ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],
									"FILTER_NAME" => 'searchFilter',
									"SECTION_URL" => $arParams["SECTION_URL"],
									"DETAIL_URL" => $arParams["DETAIL_URL"],
									"BASKET_URL" => $arParams["BASKET_URL"],
									"ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
									"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
									"SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],

									"CACHE_TYPE" => $arParams["CACHE_TYPE"],
									"CACHE_TIME" => $arParams["CACHE_TIME"],

									"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
									"SET_TITLE" => $arParams["SET_TITLE"],
									"PROPERTY_CODE" => $arParams["PROPERTY_CODE"],
									"PRICE_CODE" => $arParams["PRICE_CODE"],
									"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
									"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],

									"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
									"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
									"CURRENCY_ID" => $arParams["CURRENCY_ID"],
									"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
									"TEMPLATE_THEME" => (isset($arParams["TEMPLATE_THEME"]) ? $arParams["TEMPLATE_THEME"] : ""),

									"ADD_PICT_PROP" => (isset($arParams["ADD_PICT_PROP"]) ? $arParams["ADD_PICT_PROP"] : ""),

									"LABEL_PROP" => (isset($arParams["LABEL_PROP"]) ? $arParams["LABEL_PROP"] : ""),
									"OFFER_ADD_PICT_PROP" => (isset($arParams["OFFER_ADD_PICT_PROP"]) ? $arParams["OFFER_ADD_PICT_PROP"] : ""),
									"OFFER_TREE_PROPS" => (isset($arParams["OFFER_TREE_PROPS"]) ? $arParams["OFFER_TREE_PROPS"] : ""),
									"SHOW_DISCOUNT_PERCENT" => (isset($arParams["SHOW_DISCOUNT_PERCENT"]) ? $arParams["SHOW_DISCOUNT_PERCENT"] : ""),
									"SHOW_OLD_PRICE" => (isset($arParams["SHOW_OLD_PRICE"]) ? $arParams["SHOW_OLD_PRICE"] : ""),
									"MESS_BTN_BUY" => (isset($arParams["MESS_BTN_BUY"]) ? $arParams["MESS_BTN_BUY"] : ""),
									"MESS_BTN_ADD_TO_BASKET" => (isset($arParams["MESS_BTN_ADD_TO_BASKET"]) ? $arParams["MESS_BTN_ADD_TO_BASKET"] : ""),
									"MESS_BTN_DETAIL" => (isset($arParams["MESS_BTN_DETAIL"]) ? $arParams["MESS_BTN_DETAIL"] : ""),
									"MESS_NOT_AVAILABLE" => (isset($arParams["MESS_NOT_AVAILABLE"]) ? $arParams["MESS_NOT_AVAILABLE"] : ""),
									'ADD_TO_BASKET_ACTION' => (isset($arParams["ADD_TO_BASKET_ACTION"]) ? $arParams["ADD_TO_BASKET_ACTION"] : ""),
									'SHOW_CLOSE_POPUP' => (isset($arParams["SHOW_CLOSE_POPUP"]) ? $arParams["SHOW_CLOSE_POPUP"] : ""),
									'DISPLAY_COMPARE' => (isset($arParams['DISPLAY_COMPARE']) ? $arParams['DISPLAY_COMPARE'] : ''),
									'COMPARE_PATH' => (isset($arParams['COMPARE_PATH']) ? $arParams['COMPARE_PATH'] : ''),
									"SHOW_DISCOUNT_TIME" => $arParams["SHOW_DISCOUNT_TIME"],
									"SHOW_DISCOUNT_PERCENT_NUMBER" => $arParams["SHOW_DISCOUNT_PERCENT_NUMBER"],
									"SALE_STIKER" => $arParams["SALE_STIKER"],
									"STIKERS_PROP" => $arParams["STIKERS_PROP"],
									"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
									"DISPLAY_TYPE" => "block",
									"SHOW_RATING" => $arParams["SHOW_RATING"],
									"DISPLAY_WISH_BUTTONS" => $arParams["DISPLAY_WISH_BUTTONS"],
									"DEFAULT_COUNT" => $arParams["DEFAULT_COUNT"],
									"REVIEWS_VIEW" => $arParams["REVIEWS_VIEW"] == 'EXTENDED',
								)
								+ array(
									'OFFER_ID' => $templateData['GIFTS_PARAMS']['OFFER_ID'],
									'SECTION_ID' => $templateData['GIFTS_PARAMS']['SECTION_ID'],
									'ELEMENT_ID' => $arResult['ID'],
								),
								$component,
								array("HIDE_ICONS" => "Y")
						);
					}
					?>
				</div>

			<?//tizers?>
			<?elseif($code == 'tizers' && $arBlockOrder[0] != 'tizers'):?>
				<?$APPLICATION->ShowViewContent('TIZERS_BLOCK')?>

			<?//services?>
			<?elseif( $code == 'services' ):?>
				<?if($arResult['SERVICES']):?>
					<?
					global $arrSaleFilter;
					$arrSaleFilter = array('ID' => array_keys($arResult['SERVICES']));
					?>
					<?$APPLICATION->IncludeComponent(
						"bitrix:news.list",
						"items-services",
						array(
							"IBLOCK_TYPE" => "aspro_next_content",
							"IBLOCK_ID" => $servicesIblockId,
							"NEWS_COUNT" => "20",
							"SORT_BY1" => "SORT",
							"SORT_ORDER1" => "ASC",
							"SORT_BY2" => "ID",
							"SORT_ORDER2" => "DESC",
							"FILTER_NAME" => "arrSaleFilter",
							"FIELD_CODE" => array(
								0 => "NAME",
								1 => "PREVIEW_TEXT",
								3 => "PREVIEW_PICTURE",
								4 => "",
							),
							"PROPERTY_CODE" => array(
								0 => "PERIOD",
								1 => "REDIRECT",
								2 => "",
							),
							"CHECK_DATES" => "Y",
							"DETAIL_URL" => "",
							"AJAX_MODE" => "N",
							"AJAX_OPTION_JUMP" => "N",
							"AJAX_OPTION_STYLE" => "Y",
							"AJAX_OPTION_HISTORY" => "N",
							"CACHE_TYPE" => "N",
							"CACHE_TIME" => "36000000",
							"CACHE_FILTER" => "Y",
							"CACHE_GROUPS" => "N",
							"PREVIEW_TRUNCATE_LEN" => "",
							"ACTIVE_DATE_FORMAT" => "d.m.Y",
							"SET_TITLE" => "N",
							"SET_STATUS_404" => "N",
							"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
							"ADD_SECTIONS_CHAIN" => "N",
							"HIDE_LINK_WHEN_NO_DETAIL" => "N",
							"PARENT_SECTION" => "",
							"PARENT_SECTION_CODE" => "",
							"INCLUDE_SUBSECTIONS" => "Y",
							"PAGER_TEMPLATE" => ".default",
							"DISPLAY_TOP_PAGER" => "N",
							"DISPLAY_BOTTOM_PAGER" => "Y",
							"PAGER_TITLE" => "",
							"PAGER_SHOW_ALWAYS" => "N",
							"PAGER_DESC_NUMBERING" => "N",
							"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
							"PAGER_SHOW_ALL" => "N",
							"VIEW_TYPE" => "list",
							"BIG_BLOCK" => "Y",
							"IMAGE_POSITION" => "left",
							"COUNT_IN_LINE" => 1,
							"TITLE" => ($arParams["BLOCK_SERVICES_NAME"] ? $arParams["BLOCK_SERVICES_NAME"] : GetMessage("SERVICES_TITLE")),
						),
						$component, array("HIDE_ICONS" => "Y")
					);?>
				<?endif;?>

			<?//goods?>
			<?elseif($code == 'goods' && !$bViewBlock):?>

					<?if(
						$bAccessories ||
						$bSimilar
					):?>
						<div class="<?=($blockViewType ? '' : 'bottom_slider');?> specials tab_slider_wrapp drag_block_detail <?=$code?>">
							<div class="top_blocks">
								<ul class="tabs">
									<?$i = 1;?>
									<?foreach($arTab as $code => $arValue):?>
										<li data-code="<?=$code?>"  <?=($i == 1 ? 'class="cur"' : '')?>><span><?=$arValue['TITLE']?></span></li>
										<?$i++;?>
									<?endforeach;?>
									<li class="stretch"></li>
								</ul>
								<ul class="slider_navigation top custom_flex border">
									<?$i = 1;?>
									<?foreach($arTab as $code => $arValue):?>
										<li class="tabs_slider_navigation <?=$code?>_nav <?=($i == 1 ? 'cur' : '')?>" data-code="<?=$code?>"></li>
										<?$i++;?>
									<?endforeach;?>
								</ul>
							</div>
							<ul class="tabs_content">
								<?foreach($arTab as $code => $arValue):?>
									<li class="tab <?=$code?>_wrapp" data-code="<?=$code?>">
										<?if ($blockViewType):?>
											<div class="wraps goods-block with-padding block ajax_load catalog">
										<?else:?>
										<div class="flexslider loading_state shadow border custom_flex top_right" data-plugin-options='{"animation": "slide", "animationSpeed": 600, "directionNav": true, "controlNav" :false, "animationLoop": true, "slideshow": false, "controlsContainer": ".tabs_slider_navigation.<?=$code?>_nav", "counts": [4,3,3,2,1]}'>
											<ul class="tabs_slider <?=$code?>_slides slides">
										<?endif;?>
												<?
												if(array_key_exists($code, $arAllValues) && $arAllValues[$code]){
													$GLOBALS['arrFilter'.$code] = array('ID' => $arAllValues[$code]);
												}

												$GLOBALS['arrFilter'.$code]['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
												CNext::makeElementFilterInRegion($GLOBALS['arrFilter'.$code], false, $bSetLinkRegionFilter = $arParams['FILTER_NAME'] === 'arRegionLink');

												if($arValue['FILTER']){
													$GLOBALS['arrFilter'.$code][] = $arValue['FILTER'];
												}
												?>
												<?$APPLICATION->IncludeComponent(
													$arConfig[0],
													$arConfig[1],
													array(
														"USE_REGION" => $arParams['USE_REGION'] !== 'N' ? 'Y' : 'N',
														"STORES" => $arParams['STORES'],
														"TITLE_BLOCK" => $arParams["SECTION_TOP_BLOCK_TITLE"],
														"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
														"IBLOCK_ID" => $arParams["IBLOCK_ID"],
														"SALE_STIKER" => $arParams["SALE_STIKER"],
														"STIKERS_PROP" => $arParams["STIKERS_PROP"],
														"SHOW_RATING" => $arParams["SHOW_RATING"],
														"DISPLAY_TOP_PAGER" => "N",
														"DISPLAY_BOTTOM_PAGER" => "N",
														"FILTER_NAME" => 'arrFilter'.$code,
														"CUSTOM_FILTER" => "",
														"ELEMENT_SORT_FIELD" => $arParams["ELEMENT_SORT_FIELD"],
														"ELEMENT_SORT_ORDER" => $arParams["ELEMENT_SORT_ORDER"],
														"ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
														"ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],
														"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
														"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
														"BASKET_URL" => $arParams["BASKET_URL"],
														"ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
														"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
														"SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
														"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
														"PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
														"DISPLAY_COMPARE" => ($arParams["DISPLAY_COMPARE"] ? "Y" : "N"),
														"DISPLAY_WISH_BUTTONS" => $arParams["DISPLAY_WISH_BUTTONS"],
														"ELEMENT_COUNT" => $displayElementSlider,
														"SHOW_MEASURE_WITH_RATIO" => $arParams["SHOW_MEASURE_WITH_RATIO"],
														"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
														"LINE_ELEMENT_COUNT" => $arParams["TOP_LINE_ELEMENT_COUNT"],
														"PROPERTY_CODE" => $arParams["PROPERTY_CODE"],
														"PRICE_CODE" => $arParams['PRICE_CODE'],
														"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
														"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
														"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
														"PRICE_VAT_SHOW_VALUE" => $arParams["PRICE_VAT_SHOW_VALUE"],
														"USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
														"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
														"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
														"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
														"CACHE_TYPE" => $arParams["CACHE_TYPE"],
														"CACHE_TIME" => $arParams["CACHE_TIME"],
														"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
														"CACHE_FILTER" => $arParams["CACHE_FILTER"],
														"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
														"OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
														"OFFERS_PROPERTY_CODE" => $arParams["OFFERS_PROPERTY_CODE"],
														"OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
														"OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
														"OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
														"OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
														"OFFERS_LIMIT" => $arParams["LIST_OFFERS_LIMIT"],
														'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
														'CURRENCY_ID' => $arParams['CURRENCY_ID'],
														'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
														'HIDE_NOT_AVAILABLE_OFFERS' => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
														'VIEW_MODE' => (isset($arParams['TOP_VIEW_MODE']) ? $arParams['TOP_VIEW_MODE'] : ''),
														'ROTATE_TIMER' => (isset($arParams['TOP_ROTATE_TIMER']) ? $arParams['TOP_ROTATE_TIMER'] : ''),
														'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
														'LABEL_PROP' => $arParams['LABEL_PROP'],
														'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
														'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
														'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
														'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
														'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
														'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
														'SHOW_DISCOUNT_PERCENT_NUMBER' => $arParams['SHOW_DISCOUNT_PERCENT_NUMBER'],
														'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
														'MESS_BTN_BUY' => $arParams['MESS_BTN_BUY'],
														'MESS_BTN_ADD_TO_BASKET' => $arParams['MESS_BTN_ADD_TO_BASKET'],
														'MESS_BTN_SUBSCRIBE' => $arParams['MESS_BTN_SUBSCRIBE'],
														'MESS_BTN_DETAIL' => $arParams['MESS_BTN_DETAIL'],
														'MESS_NOT_AVAILABLE' => $arParams['MESS_NOT_AVAILABLE'],
														'ADD_TO_BASKET_ACTION' => $basketAction,
														"ADD_PICT_PROP" => ($arParams["ADD_PICT_PROP"] ? $arParams["ADD_PICT_PROP"] : 'MORE_PHOTO'),
													"OFFER_ADD_PICT_PROP" => ($arParams["OFFER_ADD_PICT_PROP"] ? $arParams["OFFER_ADD_PICT_PROP"] : 'MORE_PHOTO'),
													"GALLERY_ITEM_SHOW" => $GLOBALS["arTheme"]["GALLERY_ITEM_SHOW"]["VALUE"],
													"MAX_GALLERY_ITEMS" => $GLOBALS["arTheme"]["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["MAX_GALLERY_ITEMS"]["VALUE"],
													"ADD_DETAIL_TO_GALLERY_IN_LIST" => $GLOBALS["arTheme"]["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["ADD_DETAIL_TO_GALLERY_IN_LIST"]["VALUE"],
														'SHOW_CLOSE_POPUP' => isset($arParams['COMMON_SHOW_CLOSE_POPUP']) ? $arParams['COMMON_SHOW_CLOSE_POPUP'] : '',
														'COMPARE_PATH' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['compare'],
														"REVIEWS_VIEW" => $arParams["REVIEWS_VIEW"] == 'EXTENDED',
														"COMPATIBLE_MODE" => "Y",
														"SHOW_ALL_WO_SECTION" => "Y",
													),
													false, array("HIDE_ICONS"=>"Y")
												);?>
										<?if ($blockViewType):?>
											</div>
										<?else:?>
											</ul>
										</div>
										<?endif;?>
									</li>
								<?endforeach;?>
							</ul>
						</div>

					<?endif;?>

			<?//exp_goods?>
			<?elseif($code == 'exp_goods' && $bViewBlock):?>
				<?if($templateData['EXPANDABLES'] || $templateData['EXPANDABLES_FILTER']):?>
					<div class="wraps hidden_print addon_type drag_block_detail separate_block" id="element_expandables">
						<hr>
						<h4><?=($arParams["DETAIL_EXPANDABLES_TITLE"] ? $arParams["DETAIL_EXPANDABLES_TITLE"] : GetMessage("DETAIL_EXPANDABLES_TITLE"))?></h4>
						<div class="<?=($blockViewType ? '' : 'bottom_slider');?> specials tab_slider_wrapp custom_type">
							<?if ($blockViewType):?>
								<div class="wraps goods-block with-padding block ajax_load catalog">
							<?else:?>
							<ul class="slider_navigation top custom_flex border">
								<li class="tabs_slider_navigation access_nav cur" data-code="access"></li>
							</ul>
							<ul class="tabs_content">
								<li class="tab access_wrapp cur" data-code="access">
									<div class="flexslider loading_state shadow border custom_flex top_right " data-plugin-options='{"animation": "slide", "animationSpeed": 600, "directionNav": true, "controlNav" :false, "animationLoop": true, "slideshow": false, "controlsContainer": ".tabs_slider_navigation.access_nav", "counts": [4,3,3,2,1]}'>
										<ul class="tabs_slider access_slides slides">
							<?endif;?>
											<?
											if($templateData['EXPANDABLES']){
												$GLOBALS['arrFilterAccess'] = array('ID' => $templateData['EXPANDABLES']);
											}

											$GLOBALS['arrFilterAccess']['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
											CNext::makeElementFilterInRegion($GLOBALS['arrFilterAccess'], false, $bSetLinkRegionFilter = $arParams['FILTER_NAME'] === 'arRegionLink');

											if($templateData['EXPANDABLES_FILTER']){
												$GLOBALS['arrFilterAccess'][] = $templateData['EXPANDABLES_FILTER'];
											}
											?>
											<?$APPLICATION->IncludeComponent(
												$arConfig[0],
												$arConfig[1],
												array(
													"USE_REGION" => $arParams['USE_REGION'] !== 'N' ? 'Y' : 'N',
													"STORES" => $arParams['STORES'],
													"TITLE_BLOCK" => $arParams["SECTION_TOP_BLOCK_TITLE"],
													"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
													"IBLOCK_ID" => $arParams["IBLOCK_ID"],
													"SALE_STIKER" => $arParams["SALE_STIKER"],
													"STIKERS_PROP" => $arParams["STIKERS_PROP"],
													"SHOW_RATING" => $arParams["SHOW_RATING"],
													"DISPLAY_TOP_PAGER" => "N",
													"DISPLAY_BOTTOM_PAGER" => "N",
													"FILTER_NAME" => 'arrFilterAccess',
													"ELEMENT_SORT_FIELD" => $arParams["ELEMENT_SORT_FIELD"],
													"ELEMENT_SORT_ORDER" => $arParams["ELEMENT_SORT_ORDER"],
													"ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
													"ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],
													"CUSTOM_FILTER" => '',
													"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
													"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
													"BASKET_URL" => $arParams["BASKET_URL"],
													"ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
													"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
													"SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
													"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
													"PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
													"DISPLAY_COMPARE" => ($arParams["DISPLAY_COMPARE"] ? "Y" : "N"),
													"DISPLAY_WISH_BUTTONS" => $arParams["DISPLAY_WISH_BUTTONS"],
													"ELEMENT_COUNT" => $displayElementSlider,
													"SHOW_MEASURE_WITH_RATIO" => $arParams["SHOW_MEASURE_WITH_RATIO"],
													"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
													"LINE_ELEMENT_COUNT" => $arParams["TOP_LINE_ELEMENT_COUNT"],
													"PROPERTY_CODE" => $arParams["PROPERTY_CODE"],
													"PRICE_CODE" => $arParams['PRICE_CODE'],
													"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
													"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
													"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
													"PRICE_VAT_SHOW_VALUE" => $arParams["PRICE_VAT_SHOW_VALUE"],
													"USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
													"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
													"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
													"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
													"CACHE_TYPE" => $arParams["CACHE_TYPE"],
													"CACHE_TIME" => $arParams["CACHE_TIME"],
													"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
													"CACHE_FILTER" => $arParams["CACHE_FILTER"],
													"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
													"OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
													"OFFERS_PROPERTY_CODE" => $arParams["OFFERS_PROPERTY_CODE"],
													"OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
													"OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
													"OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
													"OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
													"OFFERS_LIMIT" => $arParams["LIST_OFFERS_LIMIT"],
													'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
													'CURRENCY_ID' => $arParams['CURRENCY_ID'],
													'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
													'HIDE_NOT_AVAILABLE_OFFERS' => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
													'VIEW_MODE' => (isset($arParams['TOP_VIEW_MODE']) ? $arParams['TOP_VIEW_MODE'] : ''),
													'ROTATE_TIMER' => (isset($arParams['TOP_ROTATE_TIMER']) ? $arParams['TOP_ROTATE_TIMER'] : ''),
													'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
													'LABEL_PROP' => $arParams['LABEL_PROP'],
													'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
													'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
													'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
													'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
													'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
													'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
													'SHOW_DISCOUNT_PERCENT_NUMBER' => $arParams['SHOW_DISCOUNT_PERCENT_NUMBER'],
													'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
													'MESS_BTN_BUY' => $arParams['MESS_BTN_BUY'],
													'MESS_BTN_ADD_TO_BASKET' => $arParams['MESS_BTN_ADD_TO_BASKET'],
													'MESS_BTN_SUBSCRIBE' => $arParams['MESS_BTN_SUBSCRIBE'],
													'MESS_BTN_DETAIL' => $arParams['MESS_BTN_DETAIL'],
													'MESS_NOT_AVAILABLE' => $arParams['MESS_NOT_AVAILABLE'],
													'ADD_TO_BASKET_ACTION' => $basketAction,
													"ADD_PICT_PROP" => ($arParams["ADD_PICT_PROP"] ? $arParams["ADD_PICT_PROP"] : 'MORE_PHOTO'),
													"OFFER_ADD_PICT_PROP" => ($arParams["OFFER_ADD_PICT_PROP"] ? $arParams["OFFER_ADD_PICT_PROP"] : 'MORE_PHOTO'),
													"GALLERY_ITEM_SHOW" => $GLOBALS["arTheme"]["GALLERY_ITEM_SHOW"]["VALUE"],
													"MAX_GALLERY_ITEMS" => $GLOBALS["arTheme"]["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["MAX_GALLERY_ITEMS"]["VALUE"],
													"ADD_DETAIL_TO_GALLERY_IN_LIST" => $GLOBALS["arTheme"]["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["ADD_DETAIL_TO_GALLERY_IN_LIST"]["VALUE"],
													'SHOW_CLOSE_POPUP' => isset($arParams['COMMON_SHOW_CLOSE_POPUP']) ? $arParams['COMMON_SHOW_CLOSE_POPUP'] : '',
													'COMPARE_PATH' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['compare'],
													"REVIEWS_VIEW" => $arParams["REVIEWS_VIEW"] == 'EXTENDED',
													"COMPATIBLE_MODE" => "Y",
													"SHOW_ALL_WO_SECTION" => "Y",
												),
												false, array("HIDE_ICONS"=>"Y")
											);?>
							<?if ($blockViewType):?>
								</div>
							<?else:?>
										</ul>
									</div>
								</li>
							</ul>
							<?endif;?>
						</div>
					</div>
				<?endif;?>

			<?//assoc_goods?>
			<?elseif($code == 'assoc_goods' && $bViewBlock):?>
				<?if($templateData['ASSOCIATED'] || $templateData['ASSOCIATED_FILTER']):?>
					<div class="wraps hidden_print addon_type drag_block_detail separate_block">
						<hr>
						<h4><?=($arParams["DETAIL_ASSOCIATED_TITLE"] ? $arParams["DETAIL_ASSOCIATED_TITLE"] : GetMessage("DETAIL_ASSOCIATED_TITLE"))?></h4>
						<div class="<?=($blockViewType ? '' : 'bottom_slider');?> specials tab_slider_wrapp custom_type">
							<?if ($blockViewType):?>
								<div class="wraps goods-block with-padding block ajax_load catalog">
							<?else:?>
							<ul class="slider_navigation top custom_flex border">
								<li class="tabs_slider_navigation accos_nav cur" data-code="accos"></li>
							</ul>
							<ul class="tabs_content">
								<li class="tab accos_wrapp cur" data-code="accos">
									<div class="flexslider loading_state shadow border custom_flex top_right" data-plugin-options='{"animation": "slide", "animationSpeed": 600, "directionNav": true, "controlNav" :false, "animationLoop": true, "slideshow": false, "controlsContainer": ".tabs_slider_navigation.accos_nav", "counts": [4,3,3,2,1]}'>
										<ul class="tabs_slider accos_slides slides">
							<?endif;?>
											<?
											if($templateData['ASSOCIATED']){
												$GLOBALS['arrFilterAssoc'] = array('ID' => $templateData['ASSOCIATED']);
											}

											$GLOBALS['arrFilterAssoc']['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
											CNext::makeElementFilterInRegion($GLOBALS['arrFilterAssoc'], false, $bSetLinkRegionFilter = $arParams['FILTER_NAME'] === 'arRegionLink');

											if($templateData['ASSOCIATED_FILTER']){
												$GLOBALS['arrFilterAssoc'][] = $templateData['ASSOCIATED_FILTER'];
											}
											?>
											<?$APPLICATION->IncludeComponent(
												$arConfig[0],
												$arConfig[1],
												array(
													"USE_REGION" => $arParams['USE_REGION'] !== 'N' ? 'Y' : 'N',
													"STORES" => $arParams['STORES'],
													"TITLE_BLOCK" => $arParams["SECTION_TOP_BLOCK_TITLE"],
													"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
													"IBLOCK_ID" => $arParams["IBLOCK_ID"],
													"SALE_STIKER" => $arParams["SALE_STIKER"],
													"STIKERS_PROP" => $arParams["STIKERS_PROP"],
													"SHOW_RATING" => $arParams["SHOW_RATING"],
													"DISPLAY_TOP_PAGER" => "N",
													"DISPLAY_BOTTOM_PAGER" => "N",
													"FILTER_NAME" => 'arrFilterAssoc',
													"CUSTOM_FILTER" => '',
													"ELEMENT_SORT_FIELD" => $arParams["ELEMENT_SORT_FIELD"],
													"ELEMENT_SORT_ORDER" => $arParams["ELEMENT_SORT_ORDER"],
													"ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
													"ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],
													"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
													"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
													"BASKET_URL" => $arParams["BASKET_URL"],
													"ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
													"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
													"SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
													"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
													"PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
													"DISPLAY_COMPARE" => ($arParams["DISPLAY_COMPARE"] ? "Y" : "N"),
													"DISPLAY_WISH_BUTTONS" => $arParams["DISPLAY_WISH_BUTTONS"],
													"ELEMENT_COUNT" => $displayElementSlider,
													"SHOW_MEASURE_WITH_RATIO" => $arParams["SHOW_MEASURE_WITH_RATIO"],
													"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
													"LINE_ELEMENT_COUNT" => $arParams["TOP_LINE_ELEMENT_COUNT"],
													"PROPERTY_CODE" => $arParams["PROPERTY_CODE"],
													"PRICE_CODE" => $arParams['PRICE_CODE'],
													"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
													"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
													"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
													"PRICE_VAT_SHOW_VALUE" => $arParams["PRICE_VAT_SHOW_VALUE"],
													"USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
													"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
													"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
													"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
													"CACHE_TYPE" => $arParams["CACHE_TYPE"],
													"CACHE_TIME" => $arParams["CACHE_TIME"],
													"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
													"CACHE_FILTER" => $arParams["CACHE_FILTER"],
													"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
													"OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
													"OFFERS_PROPERTY_CODE" => $arParams["OFFERS_PROPERTY_CODE"],
													"OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
													"OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
													"OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
													"OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
													"OFFERS_LIMIT" => $arParams["LIST_OFFERS_LIMIT"],
													'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
													'CURRENCY_ID' => $arParams['CURRENCY_ID'],
													'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
													'HIDE_NOT_AVAILABLE_OFFERS' => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
													'VIEW_MODE' => (isset($arParams['TOP_VIEW_MODE']) ? $arParams['TOP_VIEW_MODE'] : ''),
													'ROTATE_TIMER' => (isset($arParams['TOP_ROTATE_TIMER']) ? $arParams['TOP_ROTATE_TIMER'] : ''),
													'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
													'LABEL_PROP' => $arParams['LABEL_PROP'],
													'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
													'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
													'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
													'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
													'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
													'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
													'SHOW_DISCOUNT_PERCENT_NUMBER' => $arParams['SHOW_DISCOUNT_PERCENT_NUMBER'],
													'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
													'MESS_BTN_BUY' => $arParams['MESS_BTN_BUY'],
													'MESS_BTN_ADD_TO_BASKET' => $arParams['MESS_BTN_ADD_TO_BASKET'],
													'MESS_BTN_SUBSCRIBE' => $arParams['MESS_BTN_SUBSCRIBE'],
													'MESS_BTN_DETAIL' => $arParams['MESS_BTN_DETAIL'],
													'MESS_NOT_AVAILABLE' => $arParams['MESS_NOT_AVAILABLE'],
													'ADD_TO_BASKET_ACTION' => $basketAction,
													"ADD_PICT_PROP" => ($arParams["ADD_PICT_PROP"] ? $arParams["ADD_PICT_PROP"] : 'MORE_PHOTO'),
													"OFFER_ADD_PICT_PROP" => ($arParams["OFFER_ADD_PICT_PROP"] ? $arParams["OFFER_ADD_PICT_PROP"] : 'MORE_PHOTO'),
													"GALLERY_ITEM_SHOW" => $GLOBALS["arTheme"]["GALLERY_ITEM_SHOW"]["VALUE"],
													"MAX_GALLERY_ITEMS" => $GLOBALS["arTheme"]["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["MAX_GALLERY_ITEMS"]["VALUE"],
													"ADD_DETAIL_TO_GALLERY_IN_LIST" => $GLOBALS["arTheme"]["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["ADD_DETAIL_TO_GALLERY_IN_LIST"]["VALUE"],
													'SHOW_CLOSE_POPUP' => isset($arParams['COMMON_SHOW_CLOSE_POPUP']) ? $arParams['COMMON_SHOW_CLOSE_POPUP'] : '',
													'COMPARE_PATH' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['compare'],
													"REVIEWS_VIEW" => $arParams["REVIEWS_VIEW"] == 'EXTENDED',
													"COMPATIBLE_MODE" => "Y",
													"SHOW_ALL_WO_SECTION" => "Y",
												),
												false, array("HIDE_ICONS"=>"Y")
											);?>
							<?if ($blockViewType):?>
								</div>
							<?else:?>
										</ul>
									</div>
								</li>
							</ul>
							<?endif;?>
						</div>
					</div>
				<?endif;?>

			<?//stores?>
			<?elseif($code == 'stores'):?>
				<?if( $bShowStores ):?>
					<div class="wraps stores_wrapper">
						<hr>
						<h4><?=($arParams["TAB_STOCK_NAME"] ? $arParams["TAB_STOCK_NAME"] : GetMessage("STORES_TAB"));?></h4>
						<div class="stores_tab" id="stores">
						</div>
					</div>
				<?endif;?>

			<?//ask?>
			<?elseif($code == 'ask'):?>
				<?if(($arParams["SHOW_ASK_BLOCK"] == "Y") && (intVal($arParams["ASK_FORM_ID"]))):?>
					<div class="wraps hidden_print drag_block_detail <?=$code?>">
						<hr>
						<h4><?=($arParams["TAB_FAQ_NAME"] ? $arParams["TAB_FAQ_NAME"] : GetMessage('ASK_TAB'))?></h4>
						<div id="ask" class="tab-pane">
							<div class="row">
								<div class="col-md-3 hidden-sm text_block">
									<?$APPLICATION->IncludeFile(SITE_DIR."include/ask_tab_detail_description.php", array(), array("MODE" => "html", "NAME" => GetMessage('CT_BCE_CATALOG_ASK_DESCRIPTION')));?>
								</div>
								<div class="col-md-9 form_block">
									<div id="ask_block">
										<div id="ask_block_content">
											<?$APPLICATION->IncludeComponent(
												"bitrix:form.result.new",
												"inline",
												Array(
													"WEB_FORM_ID" => $arParams["ASK_FORM_ID"],
													"IGNORE_CUSTOM_TEMPLATE" => "N",
													"USE_EXTENDED_ERRORS" => "N",
													"SEF_MODE" => "N",
													"CACHE_TYPE" => "A",
													"CACHE_TIME" => "3600000",
													"LIST_URL" => "",
													"EDIT_URL" => "",
													"SUCCESS_URL" => "?send=ok",
													"CHAIN_ITEM_TEXT" => "",
													"CHAIN_ITEM_LINK" => "",
													"VARIABLE_ALIASES" => Array("WEB_FORM_ID" => "WEB_FORM_ID", "RESULT_ID" => "RESULT_ID"),
													"AJAX_MODE" => "Y",
													"AJAX_OPTION_JUMP" => "N",
													"AJAX_OPTION_STYLE" => "Y",
													"AJAX_OPTION_HISTORY" => "N",
													"SHOW_LICENCE" => CNext::GetFrontParametrValue('SHOW_LICENCE'),
												)
											);?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?endif;?>

			<?//reviews?>
			<?elseif($code == 'reviews'):?>
				<?if($arParams["USE_REVIEW"] == "Y"):?>
					<div class="wraps product_reviews_tab hidden_print <?=$arParams['REVIEWS_VIEW']?>">
						<hr>
						<h4><?=($arParams["TAB_REVIEW_NAME"] ? $arParams["TAB_REVIEW_NAME"] : GetMessage("REVIEW_TAB"))?> <?$APPLICATION->ShowViewContent('PRODUCT_REVIEWS_COUNT_INFO')?></h4>
						<?if( IsModuleInstalled("blog") && $arParams['REVIEWS_VIEW'] == 'EXTENDED' && ($arParams['USE_REVIEW'] == 'Y' || $arParams["DETAIL_USE_COMMENTS"] == 'Y') ):?>
							<div class="right_reviews_info">
								<div class="rating-wrapper">
									<div class="votes_block nstar with-text">
										<div class="ratings">
											<div class="inner_rating">
												<?for($i=1;$i<=5;$i++):?>
													<div class="item-rating"></div>
												<?endfor;?>
											</div>
										</div>
									</div>
									<div class="rating-value">
										<span class="count"></span>
										<span class="maximum_value"><?=GetMessage("VOTES_RESULT_NONE")?></span>
									</div>
								</div>
								<div class="show-comment btn btn-default">
									<?=GetMessage('ADD_REVIEW')?>
								</div>
							</div>
						<?endif;?>
						<?if($templateData["YM_ELEMENT_ID"]):?>
							<div id="reviews_content">
								<?$APPLICATION->IncludeComponent(
									"aspro:api.yamarket.reviews_model.next",
									"main",
									Array(
										"YANDEX_MODEL_ID" => $templateData["YM_ELEMENT_ID"]
									)
								);?>
							</div>
						<?elseif(IsModuleInstalled("forum") && $arParams['REVIEWS_VIEW'] == 'STANDART'):?>
							<div id="reviews_content">
								<?if($_POST['AJAX'] && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'):?>
									<?$APPLICATION->RestartBuffer();?>
								<?endif;?>

								<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID("area");?>
									<?$APPLICATION->IncludeComponent(
										"bitrix:forum.topic.reviews",
										"main",
										Array(
											"CACHE_TYPE" => $arParams["CACHE_TYPE"],
											"CACHE_TIME" => $arParams["CACHE_TIME"],
											"MESSAGES_PER_PAGE" => $arParams["MESSAGES_PER_PAGE"],
											"USE_CAPTCHA" => $arParams["USE_CAPTCHA"],
											"FORUM_ID" => $arParams["FORUM_ID"],
											"ELEMENT_ID" => $arResult["ID"],
											"IBLOCK_ID" => $arParams["IBLOCK_ID"],
											"AJAX_POST" => $arParams["REVIEW_AJAX_POST"],
											"SHOW_RATING" => "N",
											"SHOW_MINIMIZED" => "Y",
											"SECTION_REVIEW" => "Y",
											"POST_FIRST_MESSAGE" => "Y",
											"MINIMIZED_MINIMIZE_TEXT" => GetMessage("HIDE_FORM"),
											"MINIMIZED_EXPAND_TEXT" => GetMessage("ADD_REVIEW"),
											"SHOW_AVATAR" => "N",
											"SHOW_LINK_TO_FORUM" => "N",
											"PATH_TO_SMILE" => "/bitrix/images/forum/smile/",
										),	false
									);?>
								<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID("area", "");?>

								<?if($_POST['AJAX'] && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'):?>
									<?die();?>
								<?endif;?>
							</div>
						<?elseif(IsModuleInstalled("blog") && $arParams['REVIEWS_VIEW'] == 'EXTENDED' && ($arParams['USE_REVIEW'] == 'Y' || $arParams["DETAIL_USE_COMMENTS"] == 'Y') ):?>
							<div id="reviews_content" class="extended_reviews">
								<?$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/js/rating_likes.js"); ?>
								<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID("area");?>
								<?ob_start()?>
									<? $commentsPerPage = $arParams["MESSAGES_PER_PAGE"] ?? $arParams['COMMENTS_COUNT']; ?>
									<?$APPLICATION->IncludeComponent(
										"bitrix:catalog.comments",
										"catalog",
										array(
											'CACHE_TYPE' => $arParams['CACHE_TYPE'],
											'CACHE_TIME' => $arParams['CACHE_TIME'],
											'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
											"COMMENTS_COUNT" => $commentsPerPage,
											"ELEMENT_CODE" => "",
											"ELEMENT_ID" => $arResult["ID"],
											"IBLOCK_ID" => $arParams["IBLOCK_ID"],
											"IBLOCK_TYPE" => "aspro_max_catalog",
											"SHOW_DEACTIVATED" => "N",
											"TEMPLATE_THEME" => "blue",
											"URL_TO_COMMENT" => "",
											"AJAX_POST" => "Y",
											"WIDTH" => "",
											"COMPONENT_TEMPLATE" => ".default",
											"BLOG_USE" => 'Y',
											"PATH_TO_SMILE" => '/bitrix/images/blog/smile/',
											"EMAIL_NOTIFY" => $arParams["DETAIL_BLOG_EMAIL_NOTIFY"],
											"SHOW_SPAM" => "Y",
											"SHOW_RATING" => "Y",
											"RATING_TYPE" => "like_graphic_catalog_reviews",
											"MAX_IMAGE_SIZE" => $arParams["MAX_IMAGE_SIZE"],
											"MAX_IMAGE_COUNT" => $arParams["MAX_IMAGE_COUNT"],
											"BLOG_URL" => $arParams["BLOG_URL"],
											"REVIEW_COMMENT_REQUIRED" => $arParams["REVIEW_COMMENT_REQUIRED"],
											"REVIEW_FILTER_BUTTONS" => $arParams["REVIEW_FILTER_BUTTONS"],
											"REAL_CUSTOMER_TEXT" => $arParams["REAL_CUSTOMER_TEXT"],
										),
										false, array("HIDE_ICONS" => "Y")
									);?>
									<? \Aspro\Functions\CAsproNext::showComments($commentsPerPage, $arResult['NAME']); ?>
									<?$html=ob_get_clean();?>
									<?if($html && strpos($html, 'error') === false):?>
										<div class="ordered-block comments-block">
											<?=$html;?>
										</div>
										<div class="line-after"></div>
									<?endif;?>

								<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID("area", "");?>
							</div>
						<?endif;?>
					</div>
				<?endif;?>

			<?//blog?>
			<?elseif($code == 'blog'):?>

					<?$GLOBALS['arrFilterBlog'] = array("PROPERTY_LINK_GOODS" => $arResult["ID"]);?>
					<div class="wraps podborki"><?$APPLICATION->IncludeComponent(
						"bitrix:news.list",
						"news5",
						array(
							"IBLOCK_TYPE" => "aspro_next_content",
							"IBLOCK_ID" => $arParams["BLOG_IBLOCK_ID"],
							"NEWS_COUNT" => "20",
							"SORT_BY1" => "SORT",
							"SORT_ORDER1" => "ASC",
							"SORT_BY2" => "ID",
							"SORT_ORDER2" => "DESC",
							"FILTER_NAME" => "arrFilterBlog",
							"FIELD_CODE" => array(
								0 => "NAME",
								1 => "PREVIEW_PICTURE",
								2 => "",
							),
							"PROPERTY_CODE" => array(
								0 => "PERIOD",
								1 => "REDIRECT",
								2 => "",
							),
							"CHECK_DATES" => "Y",
							"DETAIL_URL" => "",
							"AJAX_MODE" => "N",
							"AJAX_OPTION_JUMP" => "N",
							"AJAX_OPTION_STYLE" => "Y",
							"AJAX_OPTION_HISTORY" => "N",
							"CACHE_TYPE" => "N",
							"CACHE_TIME" => "36000000",
							"CACHE_FILTER" => "Y",
							"CACHE_GROUPS" => "N",
							"PREVIEW_TRUNCATE_LEN" => "",
							"ACTIVE_DATE_FORMAT" => "j F Y",
							"SET_TITLE" => "N",
							"SET_STATUS_404" => "N",
							"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
							"ADD_SECTIONS_CHAIN" => "N",
							"HIDE_LINK_WHEN_NO_DETAIL" => "N",
							"PARENT_SECTION" => "",
							"PARENT_SECTION_CODE" => "",
							"INCLUDE_SUBSECTIONS" => "Y",
							"PAGER_TEMPLATE" => ".default",
							"DISPLAY_TOP_PAGER" => "N",
							"DISPLAY_BOTTOM_PAGER" => "Y",
							"PAGER_TITLE" => "",
							"PAGER_SHOW_ALWAYS" => "N",
							"PAGER_DESC_NUMBERING" => "N",
							"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
							"PAGER_SHOW_ALL" => "N",
							"VIEW_TYPE" => "block",
							"BIG_BLOCK" => "Y",
							"SHOW_MORE" => "N",
							"IMAGE_POSITION" => "left",
							"COUNT_IN_LINE" => "3",
							"TITLE" => ($arParams["BLOCK_BLOG_NAME"] == "N" ? GetMessage("BLOCK_BLOG_NAME") : $arParams["BLOCK_BLOG_NAME"]),
						),
						$component, array("HIDE_ICONS" => "Y")
					);?></div>


			<?//podborki?>
			<?elseif($code == 'podborki'):?>
				<?if($templateData['LINK_PODBORKI']["VALUE"]):?>
					<div class="wraps podborki">
						<?
						$GLOBALS['arrFilterLanding'] = array("ID" => $templateData['LINK_PODBORKI']["VALUE"]);

						if(
							$arTheme['USE_REGIONALITY']['VALUE'] === 'Y' &&
							$arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y' &&
							$arParams['USE_REGION'] === 'Y'
						){
							$GLOBALS['arrFilterLanding']['PROPERTY_LINK_REGION'] = $arRegion['ID'];
						}
						?>
						<?$APPLICATION->IncludeComponent(
							"bitrix:news.list",
							"news-project",
							array(
								"IBLOCK_TYPE" => "aspro_next_content",
								"IBLOCK_ID" => $templateData['LINK_PODBORKI']["LINK_IBLOCK_ID"],
								"NEWS_COUNT" => "20",
								"SORT_BY1" => "SORT",
								"SORT_ORDER1" => "ASC",
								"SORT_BY2" => "ID",
								"SORT_ORDER2" => "DESC",
								"FILTER_NAME" => "arrFilterLanding",
								"FIELD_CODE" => array(
									0 => "NAME",
									1 => "PREVIEW_PICTURE",
									2 => "",
								),
								"PROPERTY_CODE" => array(
									0 => "PERIOD",
									1 => "REDIRECT",
									2 => "",
								),
								"CHECK_DATES" => "Y",
								"DETAIL_URL" => "",
								"AJAX_MODE" => "N",
								"AJAX_OPTION_JUMP" => "N",
								"AJAX_OPTION_STYLE" => "Y",
								"AJAX_OPTION_HISTORY" => "N",
								"CACHE_TYPE" => "N",
								"CACHE_TIME" => "36000000",
								"CACHE_FILTER" => "Y",
								"CACHE_GROUPS" => "N",
								"PREVIEW_TRUNCATE_LEN" => "",
								"ACTIVE_DATE_FORMAT" => "d.m.Y",
								"SET_TITLE" => "N",
								"SET_STATUS_404" => "N",
								"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
								"ADD_SECTIONS_CHAIN" => "N",
								"HIDE_LINK_WHEN_NO_DETAIL" => "N",
								"PARENT_SECTION" => "",
								"PARENT_SECTION_CODE" => "",
								"INCLUDE_SUBSECTIONS" => "Y",
								"PAGER_TEMPLATE" => ".default",
								"DISPLAY_TOP_PAGER" => "N",
								"DISPLAY_BOTTOM_PAGER" => "Y",
								"PAGER_TITLE" => "",
								"PAGER_SHOW_ALWAYS" => "N",
								"PAGER_DESC_NUMBERING" => "N",
								"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
								"PAGER_SHOW_ALL" => "N",
								"VIEW_TYPE" => "block",
								"BIG_BLOCK" => "Y",
								"SHOW_MORE" => "N",
								"IMAGE_POSITION" => "left",
								"COUNT_IN_LINE" => "3",
								"TITLE" => ($arParams["BLOCK_LANDINGS_NAME"] == "N" ? GetMessage("BLOCK_LANDINGS_NAME") : $arParams["BLOCK_LANDINGS_NAME"]),
							),
							$component, array("HIDE_ICONS" => "Y")
						);?>
					</div>
				<?endif;?>


			<?//offers?>
			<?elseif($code == 'offers'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_OFFERS_INFO')?>

			<?//desc?>
			<?elseif($code == 'desc'):?>
				<?$APPLICATION->ShowViewContent('PRODUCT_DETAIL_TEXT_INFO')?>

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






*/?>

	</div>
	
	<?/*Тут закроем-------*/?>
	
	
	
	
	
<?if($templateData['BRAND_ITEM'] || (\Bitrix\Main\ModuleManager::isModuleInstalled("sale") && (!isset($arParams['USE_BIG_DATA']) || $arParams['USE_BIG_DATA'] != 'N'))):?>
	</div>
<?endif;?>












	<?if($templateData['BRAND_ITEM'] || $bUseBigData):?>
		<?/*Тут закроем-------*/?>
		<?/*<div class="col-md-3">
		
			
			<div class="right_info_block">
				<?if($templateData['BRAND_ITEM']):?>
					<div class="brand">
						<?if($templateData['BRAND_ITEM']["IMAGE"]):?>
							<div class="image"><a href="<?=$templateData['BRAND_ITEM']["DETAIL_PAGE_URL"];?>"><img src="<?=$templateData['BRAND_ITEM']["IMAGE"]["src"];?>" alt="<?=$templateData['BRAND_ITEM']["NAME"];?>" title="<?=$templateData['BRAND_ITEM']["NAME"];?>" itemprop="image"></a></div>
						<?endif;?>
						<div class="preview">
							<?if($templateData['BRAND_ITEM']["PREVIEW_TEXT"]):?>
								<div class="text"><?=$templateData['BRAND_ITEM']["PREVIEW_TEXT"];?></div>
							<?endif;?>
							<?if($arResult['SECTION']):?>
								<div class="link icons_fa"><a href="<?=$arResult['SECTION']['SECTION_PAGE_URL']?>filter/brand-is-<?=$templateData['BRAND_ITEM']['CODE'];?>/apply/" target="_blank"><?=GetMessage("ITEMS_BY_SECTION")?></a></div>
							<?endif;?>
							<div class="link icons_fa"><a href="<?=$templateData['BRAND_ITEM']["DETAIL_PAGE_URL"];?>" target="_blank"><?=GetMessage("ITEMS_BY_BRAND", array("#BRAND#" => $templateData['BRAND_ITEM']["NAME"]))?></a></div>
						</div>
					</div>
				<?endif;?>
				<?//bigdata?>
				<?if($arParams['USE_BIG_DATA'] == 'Y'):?>
					<?
					$GLOBALS['CATALOG_CURRENT_ELEMENT_ID'] = $GLOBALS['arrFilterBigData']['!ID'] = $arResult['ID'];
					$GLOBALS['arrFilterBigData']['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
					CNext::makeElementFilterInRegion($GLOBALS['arrFilterBigData']);	?>
					<div class="bigdata-wrapper"><?include_once($_SERVER["DOCUMENT_ROOT"].$arParams["BIGDATA_PATH_TEMPLATE"]);?></div>
				<?endif;?>
			</div>
		
			
			
		</div>*/?>
		<?/*Тут закроем-------*/?>
	</div>
	<?endif;?>

	<script type="text/javascript">
		// if($('#element_expandables').length && $('#element_expandables_place').length){
		// 	$('#element_expandables').insertAfter($('#element_expandables_place'));
		// 	$('#element_expandables .flexslider').removeClass('flexslider-init');
		// 	InitFlexSlider();
		// 	$('#element_expandables').removeClass('hidden');
		// }
		// $('#element_expandables_place').remove();

		// if($(".wraps.product_reviews_tab").length && $("#reviews_content").length){
		// 	$("#reviews_content").insertAfter($(".wraps.product_reviews_tab h4"));
		// }
		// if($("#ask_block_content").length && $("#ask_block").length){
		// 	$("#ask_block_content").appendTo($("#ask_block"));
		// }
		// if($(".gifts").length && $("#reviews_content").length){
		// 	$(".gifts").insertAfter($("#reviews_content"));
		// }
		// if($("#reviews_content").length && !$(".tabs .tab-content .active").length){
		// 	$(".shadow.common").hide();
		// 	$("#reviews_content").show();
		// }
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


