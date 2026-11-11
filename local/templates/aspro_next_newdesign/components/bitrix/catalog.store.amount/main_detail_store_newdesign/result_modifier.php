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

// 1а. Единица товара для подписи остатка (11.11.2026): садовая плитка в упаковках —
// «518 уп.», а не «518 шт.». Штуки (код ОКЕИ 796) и не заданная единица — «шт.» из языкового файла.
$arResult['ND_MEASURE'] = '';
if (!empty($arParams['ELEMENT_ID']) && CModule::IncludeModule('catalog')) {
    $ndProd = CCatalogProduct::GetByID((int)$arParams['ELEMENT_ID']);
    if ($ndProd && (int)$ndProd['MEASURE'] > 0) {
        $ndMeasure = CCatalogMeasure::getList([], ['ID' => (int)$ndProd['MEASURE']], false, false, ['ID', 'CODE', 'SYMBOL_RUS'])->Fetch();
        if ($ndMeasure && (int)$ndMeasure['CODE'] !== 796 && trim((string)$ndMeasure['SYMBOL_RUS']) !== '') {
            $arResult['ND_MEASURE'] = trim($ndMeasure['SYMBOL_RUS']);
        }
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