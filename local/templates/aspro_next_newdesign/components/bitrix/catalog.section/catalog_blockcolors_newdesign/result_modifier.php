<?
use Bitrix\Main\Type\Collection;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Iblock;
use Bitrix\Main\Loader;


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

// Остатки Начало


// Параметры складов (можно передать через arParams)
$regionStoreId = (int)($arParams['REGION_STORE_ID'] ?? 0);
$storeOrder = !empty($arParams['STORE_ORDER']) ? (array)$arParams['STORE_ORDER'] : [];
$showOnlyStores = !empty($arParams['STORES']) ? (array)$arParams['STORES'] : [];

// Получаем все активные склады
$storesList = [];
$storeRes = CCatalogStore::GetList([], ['ACTIVE' => 'Y'], false, false, ['ID', 'TITLE', 'NAME']);
while ($store = $storeRes->Fetch()) {
    $storesList[(int)$store['ID']] = $store;
}

// Собираем ID всех товаров и их предложений
$allProductIds = [];
$skuMap = []; // товар => текущее выбранное предложение
foreach ($arResult['ITEMS'] as &$item) {
    $productId = (int)$item['ID'];
    $allProductIds[] = $productId;
    if (!empty($item['OFFERS'])) {
        // Выбираем первое доступное предложение как текущее
        $selectedOfferId = 0;
        foreach ($item['OFFERS'] as $offer) {
            $allProductIds[] = (int)$offer['ID'];
            if (!$selectedOfferId && ($offer['CAN_BUY'] || $offer['QUANTITY'] > 0)) {
                $selectedOfferId = (int)$offer['ID'];
            }
        }
        if (!$selectedOfferId) {
            $firstOffer = reset($item['OFFERS']);
            $selectedOfferId = (int)$firstOffer['ID'];
        }
        $skuMap[$productId] = $selectedOfferId;
    } else {
        $skuMap[$productId] = $productId;
    }
}
$allProductIds = array_unique($allProductIds);

// Получаем остатки по складам для всех товаров/предложений
$storeAmounts = [];
$res = CCatalogStoreProduct::GetList([], ['PRODUCT_ID' => $allProductIds], false, false, ['PRODUCT_ID', 'STORE_ID', 'AMOUNT']);
while ($row = $res->Fetch()) {
    $storeAmounts[(int)$row['PRODUCT_ID']][(int)$row['STORE_ID']] = (float)$row['AMOUNT'];
}

// Функция форматирования названия склада

if (!function_exists('formatStoreName')) {
   function formatStoreName($name) {
    $name = trim($name);
    $pos = strpos($name, '(');
    if ($pos !== false) $name = trim(substr($name, 0, $pos));
    $pos = strpos($name, ',');
    if ($pos !== false) $name = trim(substr($name, 0, $pos));
    return $name ?: GetMessage("STORE_NAME");
}
}


// Функция получения отфильтрованных и отсортированных складов для товара
if (!function_exists('getStoresForProduct')) {
function getStoresForProduct($productId, $storeAmounts, $storesList, $regionStoreId, $storeOrder, $showOnlyStores) {
    $amounts = $storeAmounts[$productId] ?? [];
    $storeIds = !empty($showOnlyStores) ? $showOnlyStores : array_keys($storesList);
    
    $stores = [];
    foreach ($storeIds as $storeId) {
        if (!isset($storesList[$storeId])) continue;
        $store = $storesList[$storeId];
        $amount = $amounts[$storeId] ?? 0;
        $stores[] = [
            'ID' => $storeId,
            'NAME' => formatStoreName($store['TITLE'] ?: $store['NAME']),
            'AMOUNT' => $amount,
            'REAL_AMOUNT' => $amount,
        ];
    }
    
    // Сортировка: региональный склад первый, затем по кастомному порядку, затем остальные
    $regionStore = null;
    $otherStores = [];
    foreach ($stores as $store) {
        if ($regionStoreId && $store['ID'] == $regionStoreId) {
            $regionStore = $store;
        } else {
            $otherStores[] = $store;
        }
    }
    if (!empty($storeOrder)) {
        $sortedOther = [];
        foreach ($storeOrder as $sid) {
            foreach ($otherStores as $k => $st) {
                if ($st['ID'] == $sid) {
                    $sortedOther[] = $st;
                    unset($otherStores[$k]);
                    break;
                }
            }
        }
        $otherStores = array_merge($sortedOther, array_values($otherStores));
    }
    $result = [];
    if ($regionStore) $result[] = $regionStore;
    return array_merge($result, $otherStores);
}
}
// Добавляем данные по складам в каждый элемент
foreach ($arResult['ITEMS'] as &$item) {
    $productId = (int)$item['ID'];
    $currentOfferId = $skuMap[$productId];
    $item['STORES_DATA'] = getStoresForProduct($currentOfferId, $storeAmounts, $storesList, $regionStoreId, $storeOrder, $showOnlyStores);
    
    // Данные по всем предложениям (для переключения)
    if (!empty($item['OFFERS'])) {
        $item['OFFERS_STORES_DATA'] = [];
        foreach ($item['OFFERS'] as $offer) {
            $offerId = (int)$offer['ID'];
            $item['OFFERS_STORES_DATA'][$offerId] = getStoresForProduct($offerId, $storeAmounts, $storesList, $regionStoreId, $storeOrder, $showOnlyStores);
        }
    }
}
unset($item);

// Остатки Конец






/*CANONICAL*/
// Собираем все параметры пагинации из URL
$urlPaginationParams = [];
foreach ($_GET as $key => $value) {
    if (preg_match('/^PAGEN_(\d+)$/', $key, $matches)) {
        $pageNum = (int)$matches[1];
        $pageValue = (int)$value;
        if ($pageValue > 0) {
            $urlPaginationParams[] = $pageNum;
        }
    }
}

// Если есть параметры пагинации в URL
if (!empty($urlPaginationParams)) {
    // Определяем, какие параметры пагинации действительно используются в компоненте
    $usedPaginationParams = [];
    
    // Анализируем навигационную строку
    if (isset($arResult['NAV_STRING']) && !empty($arResult['NAV_STRING'])) {
        preg_match_all('/PAGEN_(\d+)/', $arResult['NAV_STRING'], $matches);
        if (!empty($matches[1])) {
            $usedPaginationParams = array_map('intval', $matches[1]);
        }
    }
    
    // Если в компоненте вообще нет пагинации, но в URL есть параметры - 404
    if (empty($usedPaginationParams) && isset($arResult['NAV_RESULT']) && $arResult['NAV_RESULT']->NavPageCount <= 1) {
        $usedPaginationParams = []; // точно нет пагинации
    } elseif (empty($usedPaginationParams) && isset($arResult['NAV_RESULT']) && $arResult['NAV_RESULT']->NavPageCount > 1) {
        // Если есть постраничная навигация, но не нашли в NAV_STRING, предполагаем PAGEN_1
        $usedPaginationParams = [1];
    }
    
    // Проверяем каждый параметр из URL
    foreach ($urlPaginationParams as $pagenNum) {
        if (!in_array($pagenNum, $usedPaginationParams)) {
            $this->SetViewTarget('404');
            CHTTP::SetStatus("404 Not Found");
            define('ERROR_404', 'Y');
            
            Bitrix\Iblock\Component\Tools::process404(
                'Страница не найдена',
                true,
                true,
                true,
                false
            );
            $this->EndViewTarget();
            return;
        }
    }
}



$arSection = CIblockSection::GetById($arResult["ID"])->GetNext();
$arResult['SECTION_PAGE_URL'] = $arSection['SECTION_PAGE_URL'];
$arResult['SECTION_PAGE_TITLE'] = $arSection['SECTION_PAGE_TITLE'];
$cp = $this->__component; 
if (is_object($cp))
$cp->SetResultCacheKeys(array('SECTION_PAGE_URL'));


/*CANONICAL*/
$arDefaultParams = array(
	'TYPE_SKU' => 'Y',
	'ADD_PICT_PROP' => '-',
	'OFFER_ADD_PICT_PROP' => '-',
	'OFFER_TREE_PROPS' => array('-'),
	'ADD_TO_BASKET_ACTION' => 'ADD',
	'DEFAULT_COUNT' => '1',
);

$arSection = CIblockSection::GetById($arResult["ID"])->GetNext();
$arResult['SECTION_PAGE_URL'] = $arSection['SECTION_PAGE_URL'];
$arResult['SECTION_NAME'] = $arSection['SECTION_NAME'];
$arResult['SECTION_PAGE_TITLE'] = $arSection['SECTION_PAGE_TITLE'];
$arResult['SECTION_META_DESCRIPTION'] = $arSection['SECTION_META_DESCRIPTION'];

$cp = $this->__component;
if (is_object($cp))
$cp->SetResultCacheKeys(array('SECTION_PAGE_URL'));
$cp->SetResultCacheKeys(array('SECTION_NAME'));

$arParams = array_merge($arDefaultParams, $arParams);
if ('TYPE_1' != $arParams['TYPE_SKU'] )
	$arParams['TYPE_SKU'] = 'N';

if ('TYPE_1' == $arParams['TYPE_SKU'] && $arParams['DISPLAY_TYPE'] !='table' ){
	if (!is_array($arParams['OFFER_TREE_PROPS']))
		$arParams['OFFER_TREE_PROPS'] = array($arParams['OFFER_TREE_PROPS']);
	foreach ($arParams['OFFER_TREE_PROPS'] as $key => $value)
	{
		$value = (string)$value;
		if ('' == $value || '-' == $value)
			unset($arParams['OFFER_TREE_PROPS'][$key]);
	}
	if (empty($arParams['OFFER_TREE_PROPS']) && isset($arParams['OFFERS_CART_PROPERTIES']) && is_array($arParams['OFFERS_CART_PROPERTIES']))
	{
		$arParams['OFFER_TREE_PROPS'] = $arParams['OFFERS_CART_PROPERTIES'];
		foreach ($arParams['OFFER_TREE_PROPS'] as $key => $value)
		{
			$value = (string)$value;
			if ('' == $value || '-' == $value)
				unset($arParams['OFFER_TREE_PROPS'][$key]);
		}
	}
}else{
	$arParams['OFFER_TREE_PROPS'] = array();
}



if (!empty($arResult['ITEMS'])){
	$arConvertParams = array();
	if ('Y' == $arParams['CONVERT_CURRENCY'])
	{
		if (!CModule::IncludeModule('currency'))
		{
			$arParams['CONVERT_CURRENCY'] = 'N';
			$arParams['CURRENCY_ID'] = '';
		}
		else
		{
			$arResultModules['currency'] = true;
			if($arResult['CURRENCY_ID'])
			{
				$arConvertParams['CURRENCY_ID'] = $arResult['CURRENCY_ID'];
			}
			else
			{
				$arCurrencyInfo = CCurrency::GetByID($arParams['CURRENCY_ID']);
				if (!(is_array($arCurrencyInfo) && !empty($arCurrencyInfo)))
				{
					$arParams['CONVERT_CURRENCY'] = 'N';
					$arParams['CURRENCY_ID'] = '';
				}
				else
				{
					$arParams['CURRENCY_ID'] = $arCurrencyInfo['CURRENCY'];
					$arConvertParams['CURRENCY_ID'] = $arCurrencyInfo['CURRENCY'];
				}
			}
		}
	}

	$arEmptyPreview = false;
	$strEmptyPreview = SITE_TEMPLATE_PATH.'/images/no_photo_medium.png';
	if (file_exists($_SERVER['DOCUMENT_ROOT'].$strEmptyPreview))
	{
		$arSizes = getimagesize($_SERVER['DOCUMENT_ROOT'].$strEmptyPreview);
		if (!empty($arSizes))
		{
			$arEmptyPreview = array(
				'SRC' => $strEmptyPreview,
				'WIDTH' => intval($arSizes[0]),
				'HEIGHT' => intval($arSizes[1])
			);
		}
		unset($arSizes);
	}
	unset($strEmptyPreview);

	$arSKUPropList = array();
	$arSKUPropIDs = array();
	$arSKUPropKeys = array();
	$boolSKU = false;
	$strBaseCurrency = '';
	$boolConvert = isset($arResult['CONVERT_CURRENCY']['CURRENCY_ID']);
	$arOfferProps = implode(';', $arParams['OFFERS_CART_PROPERTIES']);

	if ($arResult['MODULES']['catalog'])
	{
		if (!$boolConvert)
			$strBaseCurrency = CCurrency::GetBaseCurrency();

		$arSKU = CCatalogSKU::GetInfoByProductIBlock($arParams['IBLOCK_ID']);
		$boolSKU = !empty($arSKU) && is_array($arSKU);
		if ($boolSKU && !empty($arParams['OFFER_TREE_PROPS']) && 'TYPE_1' == $arParams['TYPE_SKU'] && $arParams['DISPLAY_TYPE'] !='table')
		{
			$arSKUPropList = CIBlockPriceTools::getTreeProperties(
				$arSKU,
				$arParams['OFFER_TREE_PROPS'],
				array(
					//'PICT' => $arEmptyPreview,
					'NAME' => '-'
				)
			);
			$arResult["SKU_IBLOCK_ID"]=$arSKU["IBLOCK_ID"];
			$arNeedValues = array();
			CIBlockPriceTools::getTreePropertyValues($arSKUPropList, $arNeedValues);
			$arSKUPropIDs = array_keys($arSKUPropList);


			if (empty($arSKUPropIDs))
				$arParams['TYPE_SKU'] = 'N';
			else
				$arSKUPropKeys = array_fill_keys($arSKUPropIDs, false);
		}
	}

	$arNewItemsList = array();
	$arResult['PRICES_RANGE'] = array("MIN"=>0,"MAX"=>0);
	foreach ($arResult['ITEMS'] as $key => &$arItem)
	{
		$arItem['CHECK_QUANTITY'] = false;
		if (!isset($arItem['CATALOG_MEASURE_RATIO']))
			$arItem['CATALOG_MEASURE_RATIO'] = 1;
		if (!isset($arItem['CATALOG_QUANTITY']))
			$arItem['CATALOG_QUANTITY'] = 0;
		$arItem['CATALOG_QUANTITY'] = (
			0 < $arItem['CATALOG_QUANTITY'] && is_float($arItem['CATALOG_MEASURE_RATIO'])
			? floatval($arItem['CATALOG_QUANTITY'])
			: intval($arItem['CATALOG_QUANTITY'])
		);
		$arItem['CATALOG'] = false;
		if (!isset($arItem['CATALOG_SUBSCRIPTION']) || 'Y' != $arItem['CATALOG_SUBSCRIPTION'])
			$arItem['CATALOG_SUBSCRIPTION'] = 'N';

		// collect all pictures descriptions
		$arPicDescriptions = array();
		if($arItem['PREVIEW_PICTURE']){
			$arPicDescriptions[$arItem['PREVIEW_PICTURE']['ID']] = $arItem['PREVIEW_PICTURE']['DESCRIPTION'];
		}
		if($arItem['DETAIL_PICTURE']){
			$arPicDescriptions[$arItem['DETAIL_PICTURE']['ID']] = $arItem['DETAIL_PICTURE']['DESCRIPTION'];
		}
		if($arParams['ADD_PICT_PROP'] && isset($arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']]) && $arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']] && $arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']]['VALUE']){
			if(!is_array($arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']]['VALUE'])){
				$arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']]['VALUE'] = array($arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']]['VALUE']);
				$arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']]['DESCRIPTION'] = array($arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']]['DESCRIPTION']);
			}

			foreach($arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']]['VALUE'] as $i => $value){
				$arPicDescriptions[$value] = $arItem['PROPERTIES'][$arParams['ADD_PICT_PROP']]['DESCRIPTION'][$i];
			}
		}

		$productPictures = CIBlockPriceTools::getDoublePicturesForItem($arItem, $arParams['ADD_PICT_PROP']);

		// set pictures descriptions
		if (empty($productPictures['PICT'])){
			$productPictures['PICT'] = $arEmptyPreview;
		}
		else{
			$productPictures['PICT']['DESCRIPTION'] = $arPicDescriptions[$productPictures['PICT']['ID']];
		}
		if (empty($productPictures['SECOND_PICT'])){
			$productPictures['SECOND_PICT'] = $productPictures['PICT'];
		}
		else{
			$productPictures['SECOND_PICT']['DESCRIPTION'] = $arPicDescriptions[$productPictures['SECOND_PICT']['ID']];
		}

		$arItem['PREVIEW_PICTURE'] = $arItem['PRODUCT_PREVIEW'] = $productPictures['PICT'];
		$arItem['PREVIEW_PICTURE_SECOND'] = $arItem['PRODUCT_PREVIEW_SECOND'] = $productPictures['SECOND_PICT'];
		$arItem['SECOND_PICT'] = true;

		if ($arResult['MODULES']['catalog'])
		{
			$arItem['CATALOG'] = true;
			if (!isset($arItem['CATALOG_TYPE']))
				$arItem['CATALOG_TYPE'] = CCatalogProduct::TYPE_PRODUCT;
			if (
				(CCatalogProduct::TYPE_PRODUCT == $arItem['CATALOG_TYPE'] || CCatalogProduct::TYPE_SKU == $arItem['CATALOG_TYPE'])
				&& !empty($arItem['OFFERS'])
			)
			{
				$arItem['CATALOG_TYPE'] = CCatalogProduct::TYPE_SKU;
			}
			switch ($arItem['CATALOG_TYPE'])
			{
				case CCatalogProduct::TYPE_SET:
					$arItem['OFFERS'] = array();
					$arItem['CHECK_QUANTITY'] = ('Y' == $arItem['CATALOG_QUANTITY_TRACE'] && 'N' == $arItem['CATALOG_CAN_BUY_ZERO']);
					break;
				case CCatalogProduct::TYPE_SKU:
					break;
				case CCatalogProduct::TYPE_PRODUCT:
				default:
					$arItem['CHECK_QUANTITY'] = ('Y' == $arItem['CATALOG_QUANTITY_TRACE'] && 'N' == $arItem['CATALOG_CAN_BUY_ZERO']);
					break;
			}
		}
		else
		{
			$arItem['CATALOG_TYPE'] = 0;
			$arItem['OFFERS'] = array();
		}

		if ($arItem['CATALOG'] && isset($arItem['OFFERS']) && !empty($arItem['OFFERS']))
		{
			if ('TYPE_1' == $arParams['TYPE_SKU'] && $arParams['DISPLAY_TYPE'] !='table')
			{
				$arMatrixFields = $arSKUPropKeys;
				$arMatrix = array();

				$arNewOffers = array();
				$boolSKUDisplayProperties = false;
				$arItem['OFFERS_PROP'] = false;

				$arDouble = array();
				foreach ($arItem['OFFERS'] as $keyOffer => $arOffer)
				{
					$arOffer['ID'] = intval($arOffer['ID']);
					if (isset($arDouble[$arOffer['ID']]))
						continue;
					$arRow = array();
					foreach ($arSKUPropIDs as $propkey => $strOneCode)
					{
						$arCell = array(
							'VALUE' => 0,
							'SORT' => PHP_INT_MAX,
							'NA' => true
						);
						if (isset($arOffer['DISPLAY_PROPERTIES'][$strOneCode]))
						{
							$arMatrixFields[$strOneCode] = true;
							$arCell['NA'] = false;
							if ('directory' == $arSKUPropList[$strOneCode]['USER_TYPE'])
							{
								$intValue = $arSKUPropList[$strOneCode]['XML_MAP'][$arOffer['DISPLAY_PROPERTIES'][$strOneCode]['VALUE']];
								$arCell['VALUE'] = $intValue;
							}
							elseif ('L' == $arSKUPropList[$strOneCode]['PROPERTY_TYPE'])
							{
								$arCell['VALUE'] = intval($arOffer['DISPLAY_PROPERTIES'][$strOneCode]['VALUE_ENUM_ID']);
							}
							elseif ('E' == $arSKUPropList[$strOneCode]['PROPERTY_TYPE'])
							{
								$arCell['VALUE'] = intval($arOffer['DISPLAY_PROPERTIES'][$strOneCode]['VALUE']);
							}
							$arCell['SORT'] = $arSKUPropList[$strOneCode]['VALUES'][$arCell['VALUE']]['SORT'];
						}
						$arRow[$strOneCode] = $arCell;
					}
					$arMatrix[$keyOffer] = $arRow;

					CIBlockPriceTools::clearProperties($arOffer['DISPLAY_PROPERTIES'], $arParams['OFFER_TREE_PROPS']);

					CIBlockPriceTools::setRatioMinPrice($arOffer, false);

					$offerPictures = CIBlockPriceTools::getDoublePicturesForItem($arOffer, $arParams['OFFER_ADD_PICT_PROP']);

					$arOffer['OWNER_PICT'] = empty($offerPictures['PICT']);
					$arOffer['PREVIEW_PICTURE'] = false;
					$arOffer['PREVIEW_PICTURE_SECOND'] = false;
					$arOffer['SECOND_PICT'] = true;
					if (!$arOffer['OWNER_PICT'])
					{
						if (empty($offerPictures['SECOND_PICT']))
							$offerPictures['SECOND_PICT'] = $offerPictures['PICT'];
						$arOffer['PREVIEW_PICTURE'] = $offerPictures['PICT'];
						$arOffer['PREVIEW_PICTURE_SECOND'] = $offerPictures['SECOND_PICT'];
					}

					if ('' != $arParams['OFFER_ADD_PICT_PROP'] && isset($arOffer['DISPLAY_PROPERTIES'][$arParams['OFFER_ADD_PICT_PROP']]))
						unset($arOffer['DISPLAY_PROPERTIES'][$arParams['OFFER_ADD_PICT_PROP']]);

					$arDouble[$arOffer['ID']] = true;
					$arNewOffers[$keyOffer] = $arOffer;

				}


				$arItem['OFFERS'] = $arNewOffers;

				$arUsedFields = array();
				$arSortFields = array();

				$arPropSKU = $arItem['OFFERS_PROPS_JS'] = array();

				foreach ($arSKUPropIDs as $propkey => $strOneCode)
				{
					$boolExist = $arMatrixFields[$strOneCode];
					foreach ($arMatrix as $keyOffer => $arRow)
					{
						if ($boolExist)
						{
							if (!isset($arItem['OFFERS'][$keyOffer]['TREE']))
								$arItem['OFFERS'][$keyOffer]['TREE'] = array();
							$arItem['OFFERS'][$keyOffer]['TREE']['PROP_'.$arSKUPropList[$strOneCode]['ID']] = $arMatrix[$keyOffer][$strOneCode]['VALUE'];
							$arItem['OFFERS'][$keyOffer]['SKU_SORT_'.$strOneCode] = $arMatrix[$keyOffer][$strOneCode]['SORT'];
							$arUsedFields[$strOneCode] = true;
							$arSortFields['SKU_SORT_'.$strOneCode] = SORT_NUMERIC;

							$arPropSKU[$strOneCode][$arMatrix[$keyOffer][$strOneCode]["VALUE"]] = $arSKUPropList[$strOneCode]["VALUES"][$arMatrix[$keyOffer][$strOneCode]["VALUE"]];
						}
						else
						{
							unset($arMatrix[$keyOffer][$strOneCode]);
						}
					}
					if($arPropSKU[$strOneCode])
					{
						Collection::sortByColumn($arPropSKU[$strOneCode], array("SORT" => array(SORT_NUMERIC, SORT_ASC), "NAME" => array(SORT_NUMERIC, SORT_ASC))); // sort sku prop values
						$arItem['OFFERS_PROPS_JS'][$strOneCode] = array(
							"ID" => $arSKUPropList[$strOneCode]["ID"],
							"CODE" => $arSKUPropList[$strOneCode]["CODE"],
							"NAME" => $arSKUPropList[$strOneCode]["NAME"],
							"SORT" => $arSKUPropList[$strOneCode]["SORT"],
							"PROPERTY_TYPE" => $arSKUPropList[$strOneCode]["PROPERTY_TYPE"],
							"USER_TYPE" => $arSKUPropList[$strOneCode]["USER_TYPE"],
							"LINK_IBLOCK_ID" => $arSKUPropList[$strOneCode]["LINK_IBLOCK_ID"],
							"SHOW_MODE" => $arSKUPropList[$strOneCode]["SHOW_MODE"],
							"VALUES" => $arPropSKU[$strOneCode]
						);
					}
				}
				$arItem['OFFERS_PROP'] = $arUsedFields;
				// $arItem['OFFERS_PROP_CODES'] = (!empty($arUsedFields) ? base64_encode(serialize(array_keys($arUsedFields))) : '');
				$arItem['OFFERS_PROP_CODES'] = (!empty($arParams["OFFERS_CART_PROPERTIES"]) ? base64_encode(serialize(array_keys($arParams["OFFERS_CART_PROPERTIES"]))) : '');

				Collection::sortByColumn($arItem['OFFERS'], $arSortFields);

				$arMatrix = array();
				$intSelected = -1;
				$arItem['MIN_PRICE'] = false;
				$arItem['MIN_BASIS_PRICE'] = false;
				foreach ($arItem['OFFERS'] as $keyOffer => $arOffer)
				{
					//if (empty($arItem['MIN_PRICE']))
					//{
						if ($arItem['OFFER_ID_SELECTED'] > 0)
							$foundOffer = ($arItem['OFFER_ID_SELECTED'] == $arOffer['ID']);
						/*else
							$foundOffer = $arOffer['CAN_BUY'];*/
						if ($foundOffer)
						{
							$intSelected = $keyOffer;
							$arItem['MIN_PRICE'] = (isset($arOffer['RATIO_PRICE']) ? $arOffer['RATIO_PRICE'] : $arOffer['MIN_PRICE']);
							$arItem['MIN_BASIS_PRICE'] = $arOffer['MIN_PRICE'];
						}
						unset($foundOffer);
					//}
					$arSKUProps =$arSKUArticle = false;
					if (!empty($arOffer['DISPLAY_PROPERTIES']))
					{
						$boolSKUDisplayProperties = true;
						$arSKUProps = array();
						foreach ($arOffer['DISPLAY_PROPERTIES'] as &$arOneProp)
						{
							if ('F' == $arOneProp['PROPERTY_TYPE'])
								continue;
							$arSKUProps[] = array(
							'ID' => $arOneProp['ID'],
								'NAME' => $arOneProp['NAME'],
								'VALUE' => $arOneProp['DISPLAY_VALUE'],
								'CODE' => $arOneProp['CODE'],
							);
						}
						unset($arOneProp);
					}

					$totalCount = CNext::GetTotalCount($arOffer, $arParams);
					$arOffer['IS_OFFER'] = 'Y';
					$arOffer['IBLOCK_ID'] = $arResult['IBLOCK_ID'];

					$arPriceTypeID = array();
					if($arOffer['PRICES'])
					{
						foreach($arOffer['PRICES'] as $priceKey => $arOfferPrice)
						{
							if($arOfferPrice['CAN_BUY'] == 'Y')
								$arPriceTypeID[] = $arOfferPrice['PRICE_ID'];
							if($arOffer['CATALOG_GROUP_NAME_'.$arOfferPrice['PRICE_ID']])
								$arOffer['PRICES'][$priceKey]['GROUP_NAME'] = $arOffer['CATALOG_GROUP_NAME_'.$arOfferPrice['PRICE_ID']];
						}
					}

					//format offer prices when USE_PRICE_COUNT
					$sPriceMatrix = '';
					if($arParams['USE_PRICE_COUNT'] == 'Y')
					{
						if(function_exists('CatalogGetPriceTableEx') && (isset($arOffer['PRICE_MATRIX'])) && !$arOffer['PRICE_MATRIX'] && $arPriceTypeID)
						{
							$arOffer["PRICE_MATRIX"] = CatalogGetPriceTableEx($arOffer["ID"], 0, $arPriceTypeID, 'Y', $arConvertParams);
							if(count($arOffer['PRICE_MATRIX']['ROWS']) <= 1)
							{
								$arOffer['PRICE_MATRIX'] = '';
							}
						}
						$arOffer = array_merge($arOffer, CNext::formatPriceMatrix($arOffer));
						$sPriceMatrix = CNext::showPriceMatrix($arOffer, $arParams, $arOffer['~CATALOG_MEASURE_NAME']);
					}

					$arAddToBasketData = CNext::GetAddToBasketArray($arOffer, $totalCount, $arParams["DEFAULT_COUNT"], $arParams["BASKET_URL"], false, $arItemIDs["ALL_ITEM_IDS"], 'small read_more1', $arParams);
					$arAddToBasketData["HTML"] = str_replace('data-item', 'data-props="'.$arOfferProps.'" data-item', $arAddToBasketData["HTML"]);

					$arOneRow = array(
						'ID' => $arOffer['ID'],
						'NAME' => $arOffer['~NAME'],
						'TREE' => $arOffer['TREE'],
						'DISPLAY_PROPERTIES' => $arSKUProps,
						'ARTICLE' => $arSKUArticle,
						// 'PRICE' => (isset($arOffer['RATIO_PRICE']) ? $arOffer['RATIO_PRICE'] : $arOffer['MIN_PRICE']),
						'PRICE' => $arOffer['MIN_PRICE'],
						'SHOW_DISCOUNT_TIME_EACH_SKU' => $arParams['SHOW_DISCOUNT_TIME_EACH_SKU'],
						'PRICES' => $arOffer['PRICES'],
						'USE_PRICE_COUNT' => $arParams['USE_PRICE_COUNT'],
						'SHOW_ARTICLE_SKU' => $arParams['SHOW_ARTICLE_SKU'],
						'ARTICLE_SKU' => ($arParams['SHOW_ARTICLE_SKU'] == 'Y' ? (isset($arItem['PROPERTIES']['CML2_ARTICLE']['VALUE']) && $arItem['PROPERTIES']['CML2_ARTICLE']['VALUE'] ? GetMessage('ITEM_ARTICLE').$arItem['PROPERTIES']['CML2_ARTICLE']['VALUE'] : '') : ''),
						'PRICE_MATRIX' => $sPriceMatrix,
						'BASIS_PRICE' => $arOffer['MIN_PRICE'],
						'OWNER_PICT' => $arOffer['OWNER_PICT'],
						'PREVIEW_PICTURE' => $arOffer['PREVIEW_PICTURE'],
						'PREVIEW_PICTURE_SECOND' => $arOffer['PREVIEW_PICTURE_SECOND'],
						'CHECK_QUANTITY' => $arOffer['CHECK_QUANTITY'],
						'MAX_QUANTITY' => $totalCount,
						'STEP_QUANTITY' => $arOffer['CATALOG_MEASURE_RATIO'],
						'QUANTITY_FLOAT' => is_double($arOffer['CATALOG_MEASURE_RATIO']),
						'MEASURE' => $arOffer['~CATALOG_MEASURE_NAME'],
						'CAN_BUY' => ($arAddToBasketData['CAN_BUY'] ? 'Y' : $arOffer['CAN_BUY']),
						'CATALOG_SUBSCRIBE' => $arOffer['CATALOG_SUBSCRIBE'],
						'AVAILIABLE' => CNext::GetQuantityArray($totalCount),
						'URL' => $arOffer['DETAIL_PAGE_URL'],
						//'URL' => $arItem["DETAIL_PAGE_URL"],
						'SHOW_MEASURE' => ($arParams["SHOW_MEASURE"]=="Y" ? "Y" : "N"),
						'SHOW_ONE_CLICK_BUY' => "N",
						'ONE_CLICK_BUY' => GetMessage("ONE_CLICK_BUY"),
						'OFFER_PROPS' => $arOfferProps,
						'NO_PHOTO' => $arEmptyPreview,
						'CONFIG' => $arAddToBasketData,
						'HTML' => $arAddToBasketData["HTML"],
						'PRODUCT_QUANTITY_VARIABLE' => $arParams["PRODUCT_QUANTITY_VARIABLE"],
						'SUBSCRIPTION' => true,
						'ITEM_PRICE_MODE' => $arOffer['ITEM_PRICE_MODE'],
						'ITEM_PRICES' => $arOffer['ITEM_PRICES'],
						'ITEM_PRICE_SELECTED' => $arOffer['ITEM_PRICE_SELECTED'],
						'ITEM_QUANTITY_RANGES' => $arOffer['ITEM_QUANTITY_RANGES'],
						'ITEM_QUANTITY_RANGE_SELECTED' => $arOffer['ITEM_QUANTITY_RANGE_SELECTED'],
						'ITEM_MEASURE_RATIOS' => $arOffer['ITEM_MEASURE_RATIOS'],
						'ITEM_MEASURE_RATIO_SELECTED' => $arOffer['ITEM_MEASURE_RATIO_SELECTED'],
					);
					if($arOffer['PROPERTIES']['PRODUCT_VIDEO']['VALUE'] || $arItem['PROPERTIES']['POPUP_VIDEO']['VALUE']){
						$arItem['HAS_VIDEO'] = $arResult['HAS_VIDEO'] = true;
						$arOneRow['PROD_VIDEO'] = ($arOffer['PROPERTIES']['PRODUCT_VIDEO']['VALUE'])?$arOffer['PROPERTIES']['PRODUCT_VIDEO']['VALUE']:$arItem['PROPERTIES']['POPUP_VIDEO']['VALUE'];
					}


					if($arOneRow["PRICE"]["DISCOUNT_DIFF"]){
						$percent=round(($arOneRow["PRICE"]["DISCOUNT_DIFF"]/$arOneRow["PRICE"]["VALUE"])*100, 2);
						$arOneRow["PRICE"]["DISCOUNT_DIFF_PERCENT_RAW"]="-".$percent."%";
					}
					$arMatrix[$keyOffer] = $arOneRow;
				}
				if (-1 == $intSelected)
					$intSelected = 0;
				if (!$arMatrix[$intSelected]['OWNER_PICT'])
				{
					$arItem['PREVIEW_PICTURE'] = $arMatrix[$intSelected]['PREVIEW_PICTURE'];
					$arItem['PREVIEW_PICTURE_SECOND'] = $arMatrix[$intSelected]['PREVIEW_PICTURE_SECOND'];
				}
				$arItem['JS_OFFERS'] = $arMatrix;
				$arItem['OFFERS_SELECTED'] = $intSelected;
				$arItem['OFFERS_PROPS_DISPLAY'] = $boolSKUDisplayProperties;

				if(empty($arItem['OFFERS_PROP']))
				{
					$arItem['MIN_PRICE'] = CNext::getMinPriceFromOffersExt(
						$arItem['OFFERS'],
						$boolConvert ? $arResult['CONVERT_CURRENCY']['CURRENCY_ID'] : $strBaseCurrency
					);
				}
			}
			else
			{
				//set min price when USE_PRICE_COUNT
				if($arParams['USE_PRICE_COUNT'] == 'Y')
				{
					foreach ($arItem['OFFERS'] as $keyOffer => $arOffer)
					{
						if(function_exists('CatalogGetPriceTableEx') && (isset($arOffer['PRICE_MATRIX'])) && !$arOffer['PRICE_MATRIX'])
						{
							$arPriceTypeID = array();
							if($arOffer['PRICES'])
							{
								foreach($arOffer['PRICES'] as $priceKey => $arOfferPrice)
								{
									if($arOffer['CATALOG_GROUP_NAME_'.$arOfferPrice['PRICE_ID']])
									{
										$arPriceTypeID[] = $arOfferPrice['PRICE_ID'];
										$arOffer['PRICES'][$priceKey]['GROUP_NAME'] = $arOffer['CATALOG_GROUP_NAME_'.$arOfferPrice['PRICE_ID']];
									}
								}
							}
							$arOffer["PRICE_MATRIX"] = CatalogGetPriceTableEx($arOffer["ID"], 0, $arPriceTypeID, 'Y', $arConvertParams);
							if(count($arOffer['PRICE_MATRIX']['ROWS']) <= 1)
							{
								$arOffer['PRICE_MATRIX'] = '';
							}
						}
						$arOffer = array_merge($arOffer, CNext::formatPriceMatrix($arOffer));
						$arItem['OFFERS'][$keyOffer] = $arOffer;
					}
				}

				$arItem['MIN_PRICE'] = CNext::getMinPriceFromOffersExt(
					$arItem['OFFERS'],
					$boolConvert ? $arResult['CONVERT_CURRENCY']['CURRENCY_ID'] : $strBaseCurrency
				);

				/*set min_price_id*/
				$minItemPriceID = 0;
				$minItemPrice = 0;
				$minItemPriceFormat = "";
				foreach ($arItem['OFFERS'] as $keyOffer => $arOffer){

					if($arOffer["MIN_PRICE"]["CAN_ACCESS"]){
						if($arOffer["MIN_PRICE"]["DISCOUNT_VALUE"] < $arOffer["MIN_PRICE"]["VALUE"]){
							$minOfferPrice = $arOffer["MIN_PRICE"]["DISCOUNT_VALUE"];
							$minOfferPriceFormat = $arOffer["MIN_PRICE"]["PRINT_DISCOUNT_VALUE"];
							$minOfferPriceID = $arOffer["MIN_PRICE"]["PRICE_ID"];
						}
						else{
							$minOfferPrice = $arOffer["MIN_PRICE"]["VALUE"];
							$minOfferPriceFormat = $arOffer["MIN_PRICE"]["PRINT_VALUE"];
							$minOfferPriceID = $arOffer["MIN_PRICE"]["PRICE_ID"];
						}

						if($minItemPrice > 0 && $minOfferPrice < $minItemPrice){
							$minItemPrice = $minOfferPrice;
							$minItemPriceFormat = $minOfferPriceFormat;
							$minItemPriceID = $minOfferPriceID;
							$minItemID = $arOffer["ID"];
						}
						elseif($minItemPrice == 0){
							$minItemPrice = $minOfferPrice;
							$minItemPriceFormat = $minOfferPriceFormat;
							$minItemPriceID = $minOfferPriceID;
							$minItemID = $arOffer["ID"];
						}
					}
				}
				$arItem['MIN_PRICE']["MIN_PRICE_ID"]=$minItemPriceID;
				$arItem['MIN_PRICE']["MIN_ITEM_ID"]=$minItemID;
			}
		}
		else
		{
			if($arParams['USE_PRICE_COUNT'] == 'Y')
			{
				$arItem["FIX_PRICE_MATRIX"] = CNext::checkPriceRangeExt($arItem);
			}
			//format prices when USE_PRICE_COUNT
			$arItem = array_merge($arItem, CNext::formatPriceMatrix($arItem));
		}

		if (
			$arResult['MODULES']['catalog']
			&& $arItem['CATALOG']
			&&
				($arItem['CATALOG_TYPE'] == CCatalogProduct::TYPE_PRODUCT
				|| $arItem['CATALOG_TYPE'] == CCatalogProduct::TYPE_SET)
		)
		{
			CIBlockPriceTools::setRatioMinPrice($arItem, false);
			$arItem['MIN_BASIS_PRICE'] = $arItem['MIN_PRICE'];
		}
		$arItem['ARTICLE']=false;
		if (!empty($arItem['DISPLAY_PROPERTIES']))
		{
			foreach ($arItem['DISPLAY_PROPERTIES'] as $propKey => $arDispProp)
			{
				if($propKey=="CML2_ARTICLE"){
					$arItem['ARTICLE']=$arDispProp;
				}
				if ('F' == $arDispProp['PROPERTY_TYPE'])
					unset($arItem['DISPLAY_PROPERTIES'][$propKey]);
			}
		}
		$arItem['LAST_ELEMENT'] = 'N';
		$arNewItemsList[$key] = $arItem;
		if(isset($arItem['PROPERTIES']['MINIMUM_PRICE']['VALUE']) && !empty($arItem['PROPERTIES']['MINIMUM_PRICE']['VALUE'])){
			if($arResult['PRICES_RANGE']['MIN'] == 0){
				$arResult['PRICES_RANGE']['MIN'] = $arItem['PROPERTIES']['MINIMUM_PRICE']['VALUE'];
			}
			if($arItem['PROPERTIES']['MINIMUM_PRICE']['VALUE'] < $arResult['PRICES_RANGE']['MIN']){
				$arResult['PRICES_RANGE']['MIN'] = $arItem['PROPERTIES']['MINIMUM_PRICE']['VALUE'];
			}
			if($arItem['PROPERTIES']['MINIMUM_PRICE']['VALUE'] > $arResult['PRICES_RANGE']['MIN']){
				$arResult['PRICES_RANGE']['MAX'] = $arItem['PROPERTIES']['MINIMUM_PRICE']['VALUE'];
			}
		}
	}
	$arNewItemsList[$key]['LAST_ELEMENT'] = 'Y';
	$arResult['ITEMS'] = $arNewItemsList;

/* ===== Страница бренда: раскладка товаров по разделам =====
   Включается только параметром LD_GROUP_BY_SECTION=Y — его передаёт
   include/news.detail.brand_sections_newdesign.php. Страница категории, главная
   и карточка товара зовут этот шаблон без него и работают как раньше.

   Якоря над списком рисует отдельный компонент catalog.section/ankor_section.
   Он сортирует разделы ТАК ЖЕ (SORT ASC, NAME ASC) и подписывает их H1 раздела.
   Порядок обязан совпадать, иначе якорь уводит не в тот блок — на easydecking
   разошлись именно из-за разной сортировки, там осталось ID ASC.

   Сразу показываем LD_PER_SECTION карточек, остальное догружает
   /local/ajax/brand_products.php: у Millargo 295 товаров, и все они с полным
   JS карточки в разметку не помещаются. */
if (($arParams['LD_GROUP_BY_SECTION'] ?? '') === 'Y') {
	$ldSectionIds = [];
	foreach ($arResult['ITEMS'] as $ldItem) {
		if (!empty($ldItem['IBLOCK_SECTION_ID'])) {
			$ldSectionIds[(int)$ldItem['IBLOCK_SECTION_ID']] = true;
		}
	}

	$ldSections = [];
	if ($ldSectionIds) {
		$rsLdSections = CIBlockSection::GetList(
			['SORT' => 'ASC', 'NAME' => 'ASC'],
			['ID' => array_keys($ldSectionIds), 'ACTIVE' => 'Y'],
			false,
			['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'SORT']
		);
		while ($ldSection = $rsLdSections->Fetch()) {
			/* Подпись раздела — H1 из SEO-блока (SECTION_PAGE_TITLE), а не NAME:
			   у разных серий есть одноимённые разделы («Террасная доска» и в Ко-Экс,
			   и в Вуд-Икс), по имени их не различить, а в H1 серия указана.
			   InheritedProperty сам берёт нужный инфоблок и раскрывает шаблоны. */
			try {
				$ldSeo = (new \Bitrix\Iblock\InheritedProperty\SectionValues(
					(int)$ldSection['IBLOCK_ID'],
					(int)$ldSection['ID']
				))->getValues();
				if (!empty($ldSeo['SECTION_PAGE_TITLE'])) {
					$ldSection['NAME'] = trim($ldSeo['SECTION_PAGE_TITLE']);
				}
			} catch (\Throwable $e) {
				// H1 не заполнен или у инфоблока нет SEO-настроек — остаётся NAME
			}
			$ldSections[(int)$ldSection['ID']] = $ldSection;
		}
	}

	if ($ldSections) {
		$ldOrder = array_flip(array_keys($ldSections));   // порядок = SORT ASC, NAME ASC
		$ldBuckets = [];
		foreach ($arResult['ITEMS'] as $ldItem) {
			$ldSid = (int)$ldItem['IBLOCK_SECTION_ID'];
			$ldItem['LD_SECTION'] = $ldSections[$ldSid] ?? null;
			// раздел выключен или товар вне разделов — такие уходят в конец, без заголовка
			$ldBuckets[$ldOrder[$ldSid] ?? PHP_INT_MAX][] = $ldItem;
		}
		ksort($ldBuckets);

		$ldPerSection = isset($arParams['LD_PER_SECTION']) ? (int)$arParams['LD_PER_SECTION'] : 10;
		$ldItemsOnly = (($arParams['LD_ITEMS_ONLY'] ?? '') === 'Y');
		$ldOffset = isset($arParams['LD_OFFSET']) ? (int)$arParams['LD_OFFSET'] : 0;

		$arResult['LD_SECTIONS_META'] = [];
		$ldFlat = [];
		foreach ($ldBuckets as $ldBucket) {
			$ldSid = (int)$ldBucket[0]['IBLOCK_SECTION_ID'];
			$ldTotal = count($ldBucket);
			// AJAX отдаёт хвост раздела с offset, страница — первые LD_PER_SECTION
			$ldPart = $ldItemsOnly
				? array_slice($ldBucket, $ldOffset)
				: ($ldPerSection > 0 ? array_slice($ldBucket, 0, $ldPerSection) : $ldBucket);
			$arResult['LD_SECTIONS_META'][$ldSid] = [
				'TOTAL' => $ldTotal,
				'SHOWN' => count($ldPart) + ($ldItemsOnly ? $ldOffset : 0),
			];
			foreach ($ldPart as $ldPartItem) {
				$ldFlat[] = $ldPartItem;
			}
		}

		// LAST_ELEMENT проставлен выше по прежнему порядку — после среза он уехал
		foreach ($ldFlat as $ldKey => $ldFlatItem) {
			$ldFlat[$ldKey]['LAST_ELEMENT'] = 'N';
		}
		if ($ldFlat) {
			$ldFlat[count($ldFlat) - 1]['LAST_ELEMENT'] = 'Y';
		}

		$arResult['ITEMS'] = $ldFlat;
		$arResult['LD_GROUPED'] = true;
	}
}
	if($arSKUPropList)
	{
		foreach($arSKUPropList as $prop => $arProps)
		{
			unset($arSKUPropList[$prop]["USER_TYPE_SETTINGS"]);
			unset($arSKUPropList[$prop]["VALUES"]);
		}

	}

	$arResult['SKU_PROPS'] = $arSKUPropList;

	unset($arSKUPropList);

	$arResult['DEFAULT_PICTURE'] = $arEmptyPreview;

	$arResult['CURRENCIES'] = array();
	if ($arResult['MODULES']['currency'])
	{
		if ($boolConvert)
		{
			$currencyFormat = CCurrencyLang::GetFormatDescription($arResult['CONVERT_CURRENCY']['CURRENCY_ID']);
			$arResult['CURRENCIES'] = array(
				array(
					'CURRENCY' => $arResult['CONVERT_CURRENCY']['CURRENCY_ID'],
					'FORMAT' => array(
						'FORMAT_STRING' => $currencyFormat['FORMAT_STRING'],
						'DEC_POINT' => $currencyFormat['DEC_POINT'],
						'THOUSANDS_SEP' => $currencyFormat['THOUSANDS_SEP'],
						'DECIMALS' => $currencyFormat['DECIMALS'],
						'THOUSANDS_VARIANT' => $currencyFormat['THOUSANDS_VARIANT'],
						'HIDE_ZERO' => $currencyFormat['HIDE_ZERO']
					)
				)
			);
			unset($currencyFormat);
		}
		else
		{
			$currencyIterator = CurrencyTable::getList(array(
				'select' => array('CURRENCY')
			));
			while ($currency = $currencyIterator->fetch())
			{
				$currencyFormat = CCurrencyLang::GetFormatDescription($currency['CURRENCY']);
				$arResult['CURRENCIES'][] = array(
					'CURRENCY' => $currency['CURRENCY'],
					'FORMAT' => array(
						'FORMAT_STRING' => $currencyFormat['FORMAT_STRING'],
						'DEC_POINT' => $currencyFormat['DEC_POINT'],
						'THOUSANDS_SEP' => $currencyFormat['THOUSANDS_SEP'],
						'DECIMALS' => $currencyFormat['DECIMALS'],
						'THOUSANDS_VARIANT' => $currencyFormat['THOUSANDS_VARIANT'],
						'HIDE_ZERO' => $currencyFormat['HIDE_ZERO']
					)
				);
			}
			unset($currencyFormat, $currency, $currencyIterator);
		}
	}

}
$measureUnits = [];
$res_measure = CCatalogMeasure::getList(array(), array());
while ($measure = $res_measure->Fetch()) {
    $measureUnits[$measure['ID']] = $measure;
};
$arResult['MEASURE_ALL'] = $measureUnits;





foreach ($arItem['JS_OFFERS'] as $k => $item):
$ids[] = $item['ID'];
endforeach;

$arSelect = Array("ID", "NAME", "DATE_ACTIVE_FROM", "MAX_QUANTITY");
$arFilter = Array("IBLOCK_ID" => 20, "ACTIVE" => "Y", "=ID" => $ids);
$res = CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
while ($ob = $res->GetNextElement()) {
$arFields = $ob->GetFields();
$ar_predl[$arFields['ID']] = $arFields;
}

foreach ($arItem['JS_OFFERS'] as $k => $item):
$arItem['JS_OFFERS'][$k]['MAX_QUANTITY'] = $ar_predl[$item['ID']]['MAX_QUANTITY'];
endforeach;





?>
