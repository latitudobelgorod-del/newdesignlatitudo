<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
/**
 * Фото офисов для карточек городов в окне «Выберите город» (новый дизайн).
 *
 * Компонент aspro:regionality.list.next картинок не отдаёт, а в макете
 * (Figma «Чистовик», узлы 20517:154683 и 20517:155668) у каждого города
 * фото шоурума.
 *
 * Берём картинку у элемента инфоблока «Магазины» (код aspro_next_shops),
 * привязанного к региону свойством LINK_REGION — это карточки
 * «Контакты Латитудо в …», и у них в PREVIEW_PICTURE лежат именно снимки
 * офисов (contact_msk.jpg и т. п.).
 *
 * PREVIEW_PICTURE самого региона не годится: там портреты менеджеров
 * 150×150, они уходят в другие блоки сайта.
 */

if(!$arResult['FAVORITS'])
	return;

$arIDs = array();
foreach($arResult['FAVORITS'] as $arItem)
{
	if($arItem['ID'])
		$arIDs[] = (int)$arItem['ID'];
}

if(!$arIDs)
	return;

$cache = new CPHPCache();
$cacheID = 'nd_city_photos_'.md5(implode(',', $arIDs));
$cachePath = '/nd/city_photos/';

if($cache->InitCache(3600, $cacheID, $cachePath))
{
	$vars = $cache->GetVars();
	$arPhotos = $vars['PHOTOS'];
}
else
{
	$arPhotos = array();

	// ID инфоблока магазинов по символьному коду — на разных средах он может
	// отличаться, поэтому не прошиваем число.
	$shopsIblockID = 0;
	$rsIblock = CIBlock::GetList(array(), array('CODE' => 'aspro_next_shops', 'CHECK_PERMISSIONS' => 'N'));
	if($arIblock = $rsIblock->Fetch())
		$shopsIblockID = (int)$arIblock['ID'];

	if($shopsIblockID)
	{
		$rsShops = CIBlockElement::GetList(
			array(),
			array(
				'IBLOCK_ID'            => $shopsIblockID,
				'ACTIVE'               => 'Y',
				'PROPERTY_LINK_REGION' => $arIDs,
				'!PREVIEW_PICTURE'     => false,
			),
			false,
			false,
			array('ID', 'PREVIEW_PICTURE', 'PROPERTY_LINK_REGION')
		);
		while($arShop = $rsShops->Fetch())
		{
			$regionID = (int)$arShop['PROPERTY_LINK_REGION_VALUE'];
			// У региона может быть несколько магазинов — берём первый.
			if(!$regionID || isset($arPhotos[$regionID]) || !$arShop['PREVIEW_PICTURE'])
				continue;

			// Кадр карточки: 217×102 на десктопе и 110×177 на мобильном —
			// режем под больший из них, по месту обрезает object-fit.
			$arFile = CFile::ResizeImageGet(
				$arShop['PREVIEW_PICTURE'],
				array('width' => 440, 'height' => 360),
				BX_RESIZE_IMAGE_EXACT,
				true
			);
			if($arFile['src'])
				$arPhotos[$regionID] = $arFile['src'];
		}
	}

	// Тегированный кеш не вешаем: ID инфоблока регионов сюда не приезжает,
	// а фото офисов меняются раз в год — хватит часа по времени.
	if($cache->StartDataCache())
		$cache->EndDataCache(array('PHOTOS' => $arPhotos));
}

foreach($arResult['FAVORITS'] as $key => $arItem)
{
	$arResult['FAVORITS'][$key]['ND_PHOTO'] = isset($arPhotos[$arItem['ID']]) ? $arPhotos[$arItem['ID']] : '';
}
