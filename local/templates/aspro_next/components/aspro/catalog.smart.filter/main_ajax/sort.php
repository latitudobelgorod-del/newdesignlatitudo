<?
$sort = array(
	'ASPRO_FILTER_SORT' => array(
		'NAME' => GetMessage('ASPRO_FILTER_SORT'),
		'DISPLAY_TYPE' => 'ASPRO_FILTER_SORT',
		'DISPLAY_EXPANDED' => 'Y',
		'CODE' => 'ASPRO_FILTER_SORT',
		'ID' => 'ASPRO_FILTER_SORT',
		'ASPRO_FILTER_SORT' => 'Y',
		'VALUES' => array(),
	)
);

$arResult['ITEMS'] = $sort + $arResult['ITEMS'];

if($arParams['AVAILABLE_SORT'] && $arParams['SORT'] && $arParams['SORT_ORDER']){
	$arAvailableSort = $arParams['AVAILABLE_SORT'];
	$sort = $arParams['SORT'];
	$sort_order = $arParams['SORT_ORDER'];
}
else{
	$arAvailableSort = array();
	$arSorts = $arParams['SORT_BUTTONS'];

	if(in_array('POPULARITY', $arSorts)){
		$arAvailableSort['SHOWS'] = array('SHOWS', 'desc');
	}

	if(in_array('NAME', $arSorts)){
		$arAvailableSort['NAME'] = array('NAME', 'asc');
	}

	if(in_array('PRICE', $arSorts)){
		$arSortPrices = $arParams['SORT_PRICES'];
		if($arSortPrices === 'MINIMUM_PRICE' || $arSortPrices === 'MAXIMUM_PRICE'){
			$arAvailableSort['PRICE'] = array('PROPERTY_'.$arSortPrices, 'desc');
		}
		else{
			if($arSortPrices === 'REGION_PRICE'){
				global $arRegion;
				if($arRegion){
					if(!$arRegion['PROPERTY_SORT_REGION_PRICE_VALUE'] || $arRegion['PROPERTY_SORT_REGION_PRICE_VALUE'] === 'component'){
						$price = CCatalogGroup::GetList(array(), array('NAME' => $arParams['SORT_REGION_PRICE']), false, false, array('ID', 'NAME'))->GetNext();
						$arAvailableSort['PRICE'] = array('CATALOG_PRICE_'.$price['ID'], 'desc');
					}
					else
					{
						$arAvailableSort['PRICE'] = array('CATALOG_PRICE_'.$arRegion['PROPERTY_SORT_REGION_PRICE_VALUE'], 'desc');
					}
				}
				else
				{
					$price_name = ($arParams['SORT_REGION_PRICE'] ? $arParams['SORT_REGION_PRICE'] : 'BASE');
					$price = CCatalogGroup::GetList(array(), array('NAME' => $price_name), false, false, array('ID', 'NAME'))->GetNext();
					$arAvailableSort['PRICE'] = array('CATALOG_PRICE_'.$price['ID'], 'desc');
				}
			}
			else
			{
				$price = CCatalogGroup::GetList(array(), array('NAME' => $arParams['SORT_PRICES']), false, false, array('ID', 'NAME'))->GetNext();
				$arAvailableSort['PRICE'] = array('CATALOG_PRICE_'.$price['ID'], 'desc');
			}
		}
	}

	if(in_array('QUANTITY', $arSorts)){
		$arAvailableSort['CATALOG_AVAILABLE'] = array('QUANTITY', 'desc');
	}

	if($arParams['SHOW_SORT_RANK_BUTTON'] === 'Y'){
		$arAvailableSort['RANK'] = array('RANK', 'desc');
	}

	$sort = 'SHOWS';
	if((array_key_exists('sort', $_REQUEST) && array_key_exists(ToUpper($_REQUEST['sort']), $arAvailableSort)) || (array_key_exists('sort', $_SESSION) && array_key_exists(ToUpper($_SESSION['sort']), $arAvailableSort)) || $arParams['ELEMENT_SORT_FIELD']){
		if($_REQUEST['sort']){
			$sort = ToUpper($_REQUEST['sort']);

			if(
				$sort === 'RANK' &&
				$arParams['SHOW_SORT_RANK_BUTTON'] === 'Y'
			){
				$_SESSION["rank_sort"] = 'Y';
			}
			else{
				$_SESSION["rank_sort"] = 'N';
				$_SESSION['sort'] = ToUpper($_REQUEST['sort']);
			}
		}
		elseif(
			$_SESSION["rank_sort"] === 'Y' &&
			$arParams['SHOW_SORT_RANK_BUTTON'] === 'Y'
		){
			$sort = 'RANK';
		}
		elseif($_SESSION['sort']){
			$sort = ToUpper($_SESSION['sort']);
		}
		else{
			$sort = ToUpper($arParams['ELEMENT_SORT_FIELD']);
		}
	}

	$sort_order = $arAvailableSort[$sort][1];
		if((array_key_exists('order', $_REQUEST) && in_array(ToLower($_REQUEST['order']), array('asc', 'desc'))) || (array_key_exists('order', $_REQUEST) && in_array(ToLower($_REQUEST['order']), array('asc', 'desc')) ) || $arParams['ELEMENT_SORT_ORDER']){
		if($sort === 'RANK'){
				$sort_order = 'desc';
			}
		elseif($_REQUEST['order']){
			$sort_order = $_REQUEST['order'];
			$_SESSION['order'] = $_REQUEST['order'];
		}
		elseif($_SESSION['order']){
			$sort_order = $_SESSION['order'];
		}
		else{
			$sort_order = ToLower($arParams['ELEMENT_SORT_ORDER']);
		}
	}
}

if($arAvailableSort && !in_array($sort, array_keys($arAvailableSort))){
	$arFirstSort = reset($arAvailableSort);
	$sort = $arFirstSort[0];
}

foreach($arAvailableSort as $key => $val){
	$name = ($arParams['PROPS_NAME'] && $arParams['PROPS_NAME'][$key] ? $arParams['PROPS_NAME'][$key] : GetMessage('SECT_SORT_'.$key));
	if($key !== 'RANK'){
		$newSortOrder = 'asc';
		$current_url = $APPLICATION->GetCurPageParam('sort='.$key.'&order='.$newSortOrder, array('sort', 'order'));
		$url = str_replace('+', '%2B', $current_url);
		$bSelected = ($sort == $key && $newSortOrder == $sort_order);
		$arResult['ITEMS']['ASPRO_FILTER_SORT']['VALUES'][] = array(
			'CONTROL_HTML' => '<a href="'.$url.'" class="sort_btn '.($bSelected ? 'current' : '').' '.$newSortOrder.' '.$key.'" rel="nofollow"><span>'.$name.' ('.GetMessage($newSortOrder).')'.'</span></a>',
			'CHECKED' => $bSelected ? 'Y' : 'N',
			'VALUE' => $name.' ('.GetMessage($newSortOrder).')',
		);
	}

	$newSortOrder = 'desc';
	$current_url = $APPLICATION->GetCurPageParam('sort='.$key.($key !== 'RANK' ? '&order='.$newSortOrder : ''), array('sort', 'order'));
	$url = str_replace('+', '%2B', $current_url);
	$bSelected = ($sort == $key && ($newSortOrder == $sort_order || $key === 'RANK'));
	$arResult['ITEMS']['ASPRO_FILTER_SORT']['VALUES'][] = array(
		'CONTROL_HTML' => '<a href="'.$url.'" class="sort_btn '.($bSelected ? 'current' : '').' '.$newSortOrder.' '.$key.'" rel="nofollow"><span>'.$name.($key !== 'RANK' ? ' ('.GetMessage($newSortOrder).')' : '').'</span></a>',
		'CHECKED' => $bSelected ? 'Y' : 'N',
		'VALUE' => $name.($key !== 'RANK' ? ' ('.GetMessage($newSortOrder).')' : ''),
	);
}
?>