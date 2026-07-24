<?php
// /local/components/bitrix/catalog/templates/.default/component_epilog.php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();



// Проверяем, существует ли запрошенная страница
/*
if (isset($arResult['NAV_RESULT'])) {
    $currentPage = $arResult['NAV_RESULT']->NavPageNomer;
    $totalPages = $arResult['NAV_RESULT']->NavPageCount;
    
    if ($currentPage > $totalPages) {
        CHTTP::setStatus("404 Not Found");
        
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        require $_SERVER['DOCUMENT_ROOT'] . '/404.php';
        die();
    }
} */