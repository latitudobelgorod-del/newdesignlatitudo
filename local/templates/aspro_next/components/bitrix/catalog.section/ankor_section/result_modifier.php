<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $DB;

// Собираем ID ТОЛЬКО основных разделов элементов
$mainSectionIds = [];
foreach ($arResult["ITEMS"] as $item) {
    if (!empty($item["IBLOCK_SECTION_ID"])) {
        $mainSectionIds[(int)$item["IBLOCK_SECTION_ID"]] = true;
    }
}

$arResult['MAIN_SECTIONS'] = [];

if (!empty($mainSectionIds)) {
    $ids = implode(',', array_keys($mainSectionIds));
    
    // Получаем информацию об основных разделах с сортировкой по ID
    $result = $DB->query("
        SELECT ID, NAME, CODE, SORT 
        FROM b_iblock_section 
        WHERE ID IN ($ids) 
        AND ACTIVE = 'Y'
        ORDER BY ID ASC
    ");
    
    while ($row = $result->fetch()) {
        $arResult['MAIN_SECTIONS'][] = [
            'ID' => $row['ID'],
            'NAME' => $row['NAME'],
            'CODE' => $row['CODE'],
            'SORT' => $row['SORT']
        ];
    }
}