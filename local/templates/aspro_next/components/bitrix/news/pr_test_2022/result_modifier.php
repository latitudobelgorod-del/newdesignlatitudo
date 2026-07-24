<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// Получаем массив $arResult['GROUP'] в котором содержатся данные об элементах свойства-справочника
if (CModule::IncludeModule('highloadblock')) {
    $ID_GROUP = '7'; // ID highload-блока справочника
    $hldata = Bitrix\Highloadblock\HighloadBlockTable::getById($ID_GROUP)->fetch();
    $hlentity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
    $hlDataClass = $hldata["NAME"] . "Table";
	
    $result = $hlDataClass::getList(array(
    "select" => array("ID", "UF_NAME", "UF_XML_ID", "UF_DESCRIPTION", "UF_FILE"), // Поля для выборки
    "order" => array("UF_SORT" => "ASC"),
    "filter" => array(),
	));
    while ($res = $result->fetch()) {
	$arResult['GROUP'][] = $res;
    }
}

// Функция для получения количества элементов в указанной рубрике
function getElementsCount($xml_id){
    $rsData = CIBlockElement::GetList(
	array('SORT' => 'ASC'),
	array(
	'IBLOCK_ID' => $arParams['IBLOCK_ID'],
	'=PROPERTY_THEME_SPR' => $xml_id // SECTION - символьный код свойства
	),
	false,
	false,
	array('NAME', 'IBLOCK_ID', 'CODE')
    );
    return $rsData->SelectedRowsCount();
}