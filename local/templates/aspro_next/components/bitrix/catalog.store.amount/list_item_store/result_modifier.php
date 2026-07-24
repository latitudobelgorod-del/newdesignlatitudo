<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!empty($arParams['ELEMENT_ID'])) {
    $productId = (int)$arParams['ELEMENT_ID'];
    CModule::IncludeModule('catalog');
    
    // Получаем остатки
    $storeAmounts = [];
    $dbStoreProduct = CCatalogStoreProduct::GetList([], ['PRODUCT_ID' => $productId], false, false, ['STORE_ID', 'AMOUNT']);
    while ($arStoreProduct = $dbStoreProduct->Fetch()) {
        $storeAmounts[$arStoreProduct['STORE_ID']] = $arStoreProduct['AMOUNT'];
    }
    
    // Обновляем массив STORES
    if (!empty($storeAmounts)) {
        foreach ($arResult['STORES'] as &$store) {
            $storeId = $store['ID'];
            $store['AMOUNT'] = isset($storeAmounts[$storeId]) ? (float)$storeAmounts[$storeId] : 0;
            $store['REAL_AMOUNT'] = $store['AMOUNT'];
        }
        unset($store);
    }
    
    // Региональный склад
    $regionStoreId = isset($arParams['REGION_STORE_ID']) ? (int)$arParams['REGION_STORE_ID'] : 0;
    $regionStore = null;
    $otherStores = [];
    
    if ($regionStoreId > 0 && !empty($arResult['STORES'])) {
        foreach ($arResult['STORES'] as $store) {
            if ($store['ID'] == $regionStoreId) {
                $store['IS_REGION_STORE'] = true;
                $regionStore = $store;
            } else {
                $store['IS_REGION_STORE'] = false;
                $otherStores[] = $store;
            }
        }
    } else {
        foreach ($arResult['STORES'] as &$store) {
            $store['IS_REGION_STORE'] = false;
        }
        unset($store);
    }
    
    // Сортируем остальные склады по желанию
    $customStoreOrder = [3, 1, 4, 2];
    if (!empty($customStoreOrder) && !empty($otherStores)) {
        $sortedOtherStores = [];
        foreach ($customStoreOrder as $storeId) {
            foreach ($otherStores as $key => $store) {
                if ($store['ID'] == $storeId) {
                    $sortedOtherStores[] = $store;
                    unset($otherStores[$key]);
                    break;
                }
            }
        }
        if (!empty($otherStores)) {
            $sortedOtherStores = array_merge($sortedOtherStores, array_values($otherStores));
        }
        $otherStores = $sortedOtherStores;
    }
    
    // Итоговый массив
    if ($regionStore) {
        $arResult['STORES'] = array_merge([$regionStore], $otherStores);
    } else {
        $arResult['STORES'] = $otherStores;
    }
}
?>