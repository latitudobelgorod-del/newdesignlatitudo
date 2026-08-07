<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/* Якоря на разделы бренда. Порядок и подписи считает LdBrandSections — тот же
   класс зовёт список товаров (catalog_blockcolors_newdesign). Пока каждый
   считал сам, якорь уводил не в тот блок: здесь было ORDER BY ID ASC и NAME,
   а список шёл по SORT и подписывал разделы их H1.

   Разделы берём не из $arResult['ITEMS'], а отдельным запросом по фильтру
   компонента: сюда приходит только сотня элементов (ELEMENT_COUNT в
   include/news.detail.ankor_section.php), а у Millargo товаров 295 — часть
   разделов в эту сотню не попадает и якорей на них не было. */

require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/brand_sections.php';

$filterName = $arParams['FILTER_NAME'] ?? '';
$filter = ($filterName && is_array($GLOBALS[$filterName] ?? null)) ? $GLOBALS[$filterName] : [];

$arResult['MAIN_SECTIONS'] = $filter
	? array_values(LdBrandSections::fromFilter($arParams['IBLOCK_ID'], $filter))
	: array_values(LdBrandSections::fromItems($arResult['ITEMS']));
