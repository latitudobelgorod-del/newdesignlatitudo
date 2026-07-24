<?php

if (isset($arParams['SORT_BY_FILTER_ID']) && isset($arParams['FILTER_NAME'])) {
    $filter = $arParams['FILTER_NAME'];
    if (isset($GLOBALS[$filter])) {
        $filter = $GLOBALS[$filter];
        if (isset($filter['=ID']) && is_array($filter['=ID'])) {
            $unsorted = array();
            foreach ($arResult['ITEMS'] as $aItem) {
                $unsorted[$aItem['ID']] = $aItem;
            }
            $arResult['ITEMS'] = array();
            foreach ($filter['=ID'] as $id) {
                if (isset($unsorted[$id])) {
                    $arResult['ITEMS'][] = $unsorted[$id];
                }
            }
        }
    }
}