<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
\Bitrix\Main\Loader::includeModule('iblock');

// get iblock properties and group by types
$arAllPropList = array();
$arFilePropList = array(
	'-' => GetMessage('CP_BC_TPL_PROP_EMPTY')
);
$arListPropList = array(
	'-' => GetMessage('CP_BC_TPL_PROP_EMPTY')
);
$arHighloadPropList = array(
	'-' => GetMessage('CP_BC_TPL_PROP_EMPTY')
);
$rsProps = CIBlockProperty::GetList(
	array('SORT' => 'ASC', 'ID' => 'ASC'),
	array('IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'], 'ACTIVE' => 'Y')
);
while ($arProp = $rsProps->Fetch())
{
	$strPropName = '['.$arProp['ID'].']'.('' != $arProp['CODE'] ? '['.$arProp['CODE'].']' : '').' '.$arProp['NAME'];
	if ('' == $arProp['CODE'])
		$arProp['CODE'] = $arProp['ID'];
	$arAllPropList[$arProp['CODE']] = $strPropName;
	if ('F' == $arProp['PROPERTY_TYPE'])
		$arFilePropList[$arProp['CODE']] = $strPropName;
	if ('L' == $arProp['PROPERTY_TYPE'])
		$arListPropList[$arProp['CODE']] = $strPropName;
	if ('S' == $arProp['PROPERTY_TYPE'] && 'directory' == $arProp['USER_TYPE'] && CIBlockPriceTools::checkPropDirectory($arProp))
		$arHighloadPropList[$arProp['CODE']] = $strPropName;
}

$boolSKU = false;
if ((isset($arCurrentValues['IBLOCK_ID']) && (int)$arCurrentValues['IBLOCK_ID']) > 0 && \Bitrix\Main\Loader::includeModule('catalog'))
{
	$arSKU = CCatalogSKU::GetInfoByProductIBlock($arCurrentValues['IBLOCK_ID']);
	$boolSKU = !empty($arSKU) && is_array($arSKU);
}

if ($boolSKU)
{
	$arAllOfferPropList = array();
	$arFileOfferPropList = array(
		'-' => GetMessage('CP_BC_TPL_PROP_EMPTY')
	);
	$arTreeOfferPropList = $arShowPreviewPictuteTreeOfferPropList = array(
		'-' => GetMessage('CP_BC_TPL_PROP_EMPTY')
	);
	$rsProps = CIBlockProperty::GetList(
		array('SORT' => 'ASC', 'ID' => 'ASC'),
		array('IBLOCK_ID' => $arSKU['IBLOCK_ID'], 'ACTIVE' => 'Y')
	);
	while ($arProp = $rsProps->Fetch())
	{
		if ($arProp['ID'] == $arSKU['SKU_PROPERTY_ID'])
			continue;
		$arProp['USER_TYPE'] = (string)$arProp['USER_TYPE'];
		$strPropName = '['.$arProp['ID'].']'.('' != $arProp['CODE'] ? '['.$arProp['CODE'].']' : '').' '.$arProp['NAME'];
		if ('' == $arProp['CODE'])
			$arProp['CODE'] = $arProp['ID'];
		if ('F' == $arProp['PROPERTY_TYPE'])
			$arFileOfferPropList[$arProp['CODE']] = $strPropName;
		if ('N' != $arProp['MULTIPLE'])
			continue;
	}
}

$arTemplateParametersParts[] = array(
	'ADD_PICT_PROP' => array(
		'PARENT' => 'VISUAL',
		'NAME' => GetMessage('CP_BC_TPL_ADD_PICT_PROP'),
		'TYPE' => 'LIST',
		'MULTIPLE' => 'N',
		'ADDITIONAL_VALUES' => 'N',
		'REFRESH' => 'N',
		'DEFAULT' => '-',
		'VALUES' => $arFilePropList
	)
);

if ($boolSKU) {
	$arTemplateParametersParts[] = array(
		'OFFER_ADD_PICT_PROP' => array(
			'PARENT' => 'VISUAL',
			'NAME' => GetMessage('CP_BC_TPL_OFFER_ADD_PICT_PROP'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'N',
			'REFRESH' => 'N',
			'DEFAULT' => '-',
			'VALUES' => $arFileOfferPropList
		)
	);
}

//merge parameters to one array
$arTemplateParameters = array();
foreach($arTemplateParametersParts as $i => $part) { $arTemplateParameters = array_merge($arTemplateParameters, $part); }
?>
