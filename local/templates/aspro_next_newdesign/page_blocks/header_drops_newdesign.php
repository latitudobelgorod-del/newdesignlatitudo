<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/**
 * Выпадающие панели шапки нового дизайна (кроме каталога — у него своя,
 * catalog_wide_newdesign).
 *
 * Макет Figma «Чистовик»:
 *   «Наши работы»   — фрейм 20738:46104 (плитки: фото 152×101 + название);
 *   «Производители» — фрейм 21034:65643 (плитки с логотипами);
 *   «Услуги»        — фрейм 20566:27852 (чипы одной строкой с переносом).
 * Для «Партнерам» отдельного фрейма в макете нет — рисуем чипами, как «Услуги».
 *
 * Общее у панели (из макета): белая подложка, контейнер 1440 с полями 40/52,
 * заголовок Nunito Sans 36/39.6 800 и отступ 24 до содержимого.
 *
 * Панель привязана к пункту шапки не идентификатором, а адресом: скрипт
 * ищет в шапке ссылки с таким же href (js/newdesign-header.js). Поэтому
 * «Производители» открывается от пункта нижней строки меню, а если пункт
 * переедет в другой ряд — панель поедет с ним, править ничего не нужно.
 *
 * Стили — css/newdesign-header.css (префикс .nd-drop).
 *
 * Вывод целиком кладём в кэш: тут четыре выборки по инфоблокам и под сотню
 * CFile::ResizeImageGet. Кэш помечен тегами инфоблоков (12 бренды, 15 услуги,
 * 18 портфолио, 25 информация) — правка элемента сбрасывает его сразу.
 */

global $arRegion;

$ndDropsCacheDir = '/nd/header_drops';
$ndDropsCacheKey = 'nd_drops_'.SITE_ID.'_'.LANGUAGE_ID.'_'.($arRegion ? $arRegion['ID'] : '');
$ndDropsCache    = \Bitrix\Main\Data\Cache::createInstance();

if($ndDropsCache->initCache(3600, $ndDropsCacheKey, $ndDropsCacheDir))
{
	$arCached = $ndDropsCache->getVars();
	echo $arCached['HTML'];
	return;
}

$ndDropsCache->startDataCache();

$ndDropsTagged = \Bitrix\Main\Application::getInstance()->getTaggedCache();
$ndDropsTagged->startTagCache($ndDropsCacheDir);
$ndDropsTagged->registerTag('iblock_id_12');
$ndDropsTagged->registerTag('iblock_id_15');
$ndDropsTagged->registerTag('iblock_id_18');
$ndDropsTagged->registerTag('iblock_id_25');

ob_start();

/**
 * Услуги (ИБ 15). Фильтр по региону берём тот же, что и страница /services/ —
 * глобальный arRegionLink из defines.php шаблона: иначе в панели окажутся
 * услуги чужих регионов, которых на самой странице нет.
 */
$ndDropServices = function() {
	$arFilter = array('IBLOCK_ID' => 15, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y');
	if(!empty($GLOBALS['arRegionLink']) && is_array($GLOBALS['arRegionLink']))
		$arFilter = array_merge($arFilter, $GLOBALS['arRegionLink']);

	$arItems = array();
	$res = CIBlockElement::GetList(
		array('SORT' => 'ASC', 'NAME' => 'ASC'),
		$arFilter,
		false,
		false,
		array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
	);
	while($arItem = $res->GetNext())
	{
		$arItems[] = array('TEXT' => $arItem['NAME'], 'LINK' => $arItem['DETAIL_PAGE_URL']);
	}

	return $arItems;
};

/**
 * Партнерам (/info/) — весь раздел «Информация» (ИБ 25) в том же порядке,
 * что и левое меню страниц /info/ (просьба Ирины, 2026-08-05): там пункты
 * даёт `.left_infoblock_menu.menu_ext.php` тем же запросом — все активные
 * элементы инфоблока по SORT ASC. Короткого списка «Партнерам»
 * (`.partneram_company.menu.php`) для панели мало.
 */
$ndDropPartners = function() {
	$arItems = array();
	$res = CIBlockElement::GetList(
		array('SORT' => 'ASC', 'ID' => 'DESC'),
		array('IBLOCK_ID' => 25, 'ACTIVE' => 'Y'),
		false,
		false,
		array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
	);
	while($arItem = $res->GetNext())
	{
		$arItems[] = array('TEXT' => $arItem['NAME'], 'LINK' => $arItem['DETAIL_PAGE_URL']);
	}

	return $arItems;
};

/**
 * Наши работы — разделы портфолио первого уровня (ИБ 18), та же выборка, что
 * у плиток на /projects/ (page_blocks/sections_list_newdesign.php шаблона
 * news/projects_newdesign). Картинка в макете 152×101 — режем с запасом
 * под ретину.
 */
$ndDropWorks = function() {
	$arItems = array();
	$res = CIBlockSection::GetList(
		array('SORT' => 'ASC', 'NAME' => 'ASC'),
		array('IBLOCK_ID' => 18, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'DEPTH_LEVEL' => 1),
		false,
		array('ID', 'NAME', 'SECTION_PAGE_URL', 'PICTURE', 'DETAIL_PICTURE')
	);
	while($arSection = $res->GetNext())
	{
		$picId = (int)($arSection['PICTURE'] ? $arSection['PICTURE'] : $arSection['DETAIL_PICTURE']);
		$src = '';
		if($picId > 0)
		{
			$img = CFile::ResizeImageGet($picId, array('width' => 304, 'height' => 203), BX_RESIZE_IMAGE_EXACT, true);
			if(is_array($img) && $img['src'])
				$src = $img['src'];
		}
		$arItems[] = array('TEXT' => $arSection['NAME'], 'LINK' => $arSection['SECTION_PAGE_URL'], 'IMG' => $src);
	}

	return $arItems;
};

/**
 * Производители (ИБ 12) — те же элементы, что в ленте брендов на главной
 * и на /brands/. Логотип вписываем целиком, поэтому режем пропорционально.
 */
$ndDropBrands = function() {
	$arItems = array();
	$res = CIBlockElement::GetList(
		array('SORT' => 'ASC', 'NAME' => 'ASC'),
		array('IBLOCK_ID' => 12, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'),
		false,
		false,
		array('ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL')
	);
	while($arItem = $res->GetNext())
	{
		$picId = (int)($arItem['PREVIEW_PICTURE'] ? $arItem['PREVIEW_PICTURE'] : $arItem['DETAIL_PICTURE']);
		$src = '';
		if($picId > 0)
		{
			// с запасом под ретину: плитка ~156×103, поля 24/17
			$img = CFile::ResizeImageGet($picId, array('width' => 320, 'height' => 200), BX_RESIZE_IMAGE_PROPORTIONAL, true);
			if(is_array($img) && $img['src'])
				$src = $img['src'];
		}
		$arItems[] = array('TEXT' => $arItem['NAME'], 'LINK' => $arItem['DETAIL_PAGE_URL'], 'IMG' => $src);
	}

	return $arItems;
};

// Панели: ключ — адрес пункта шапки, от которого панель открывается.
$arDrops = array(
	array('KEY' => SITE_DIR.'services/', 'TITLE' => 'Услуги',         'TYPE' => 'chips',  'ITEMS' => $ndDropServices()),
	array('KEY' => SITE_DIR.'info/',     'TITLE' => 'Партнерам',      'TYPE' => 'chips',  'ITEMS' => $ndDropPartners()),
	array('KEY' => SITE_DIR.'projects/', 'TITLE' => 'Наши работы',    'TYPE' => 'tiles',  'ITEMS' => $ndDropWorks()),
	array('KEY' => SITE_DIR.'brands/',   'TITLE' => 'Производители',  'TYPE' => 'brands', 'ITEMS' => $ndDropBrands()),
);
?>
<?foreach($arDrops as $arDrop):?>
	<?if(!$arDrop['ITEMS']) continue;?>
	<div class="nd-drop" data-nd-drop="<?=htmlspecialcharsbx($arDrop['KEY'])?>">
		<div class="nd-drop__inner">
			<a class="nd-drop__title" href="<?=htmlspecialcharsbx($arDrop['KEY'])?>"><?=htmlspecialcharsbx($arDrop['TITLE'])?></a>

			<?if($arDrop['TYPE'] === 'chips'):?>
				<div class="nd-drop__chips">
					<?foreach($arDrop['ITEMS'] as $arItem):?>
						<a class="nd-drop__chip" href="<?=htmlspecialcharsbx($arItem['LINK'])?>"><?=htmlspecialcharsbx($arItem['TEXT'])?></a>
					<?endforeach;?>
				</div>

			<?elseif($arDrop['TYPE'] === 'tiles'):?>
				<div class="nd-drop__tiles">
					<?foreach($arDrop['ITEMS'] as $arItem):?>
						<a class="nd-drop__tile" href="<?=htmlspecialcharsbx($arItem['LINK'])?>">
							<span class="nd-drop__tile-pic">
								<?// Картинки подставляет скрипт при первом открытии панели: до этого
								// панель скрыта, и браузер откладывает загрузку — плитки открывались
								// пустыми (та же грабля, что у выпадающего каталога).?>
								<?if($arItem['IMG']):?>
									<img data-nd-src="<?=htmlspecialcharsbx($arItem['IMG'])?>" alt="<?=htmlspecialcharsbx($arItem['TEXT'])?>" width="152" height="101">
								<?endif;?>
							</span>
							<span class="nd-drop__tile-name"><?=htmlspecialcharsbx($arItem['TEXT'])?></span>
						</a>
					<?endforeach;?>
				</div>

			<?else:?>
				<div class="nd-drop__brands">
					<?foreach($arDrop['ITEMS'] as $arItem):?>
						<a class="nd-drop__brand" href="<?=htmlspecialcharsbx($arItem['LINK'])?>" title="<?=htmlspecialcharsbx($arItem['TEXT'])?>">
							<?if($arItem['IMG']):?>
								<img data-nd-src="<?=htmlspecialcharsbx($arItem['IMG'])?>" alt="<?=htmlspecialcharsbx($arItem['TEXT'])?>">
							<?else:?>
								<span class="nd-drop__brand-name"><?=htmlspecialcharsbx($arItem['TEXT'])?></span>
							<?endif;?>
						</a>
					<?endforeach;?>
				</div>
			<?endif;?>
		</div>
	</div>
<?endforeach;?>
<?
$ndDropsHtml = ob_get_clean();
$ndDropsTagged->endTagCache();
$ndDropsCache->endDataCache(array('HTML' => $ndDropsHtml));
echo $ndDropsHtml;
?>
