<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// 1. Обновляем реальные остатки из базы (если передан ELEMENT_ID)
if (!empty($arParams['ELEMENT_ID'])) {
    $productId = (int)$arParams['ELEMENT_ID'];
    CModule::IncludeModule('catalog');

    $storeAmounts = [];
    $dbStoreProduct = CCatalogStoreProduct::GetList([], ['PRODUCT_ID' => $productId], false, false, ['STORE_ID', 'AMOUNT']);
    while ($arStoreProduct = $dbStoreProduct->Fetch()) {
        $storeAmounts[$arStoreProduct['STORE_ID']] = $arStoreProduct['AMOUNT'];
    }

    if (!empty($storeAmounts)) {
        foreach ($arResult['STORES'] as &$store) {
            $storeId = $store['ID'];
            $store['AMOUNT'] = isset($storeAmounts[$storeId]) ? (float)$storeAmounts[$storeId] : 0;
            $store['REAL_AMOUNT'] = $store['AMOUNT'];
        }
        unset($store);
    }
}

// 2. Сортировка складов строго в порядке 3,4,2,1 (остальные в конец)
$customOrder = [3, 4, 2, 1];

if (!empty($arResult['STORES']) && !empty($customOrder)) {
    $sorted = [];
    $remaining = $arResult['STORES'];

    foreach ($customOrder as $needId) {
        foreach ($remaining as $idx => $store) {
            if ((int)$store['ID'] === $needId) {
                $sorted[] = $store;
                unset($remaining[$idx]);
                break;
            }
        }
    }

    // Добавляем все склады, которых нет в кастомном порядке
    if (!empty($remaining)) {
        $sorted = array_merge($sorted, array_values($remaining));
    }

    $arResult['STORES'] = $sorted;
}