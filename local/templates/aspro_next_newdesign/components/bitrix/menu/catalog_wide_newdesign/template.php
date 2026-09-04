<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Выпадающий каталог шапки нового дизайна — две панели.
 *
 * Переписан с боевого шаблона aspro_next/components/bitrix/menu/top_new2
 * (там это ветка `elseif ($arItem['LINK'] == '/catalog/')`, блок megamenu-*).
 * Источник данных и фильтрация — как на боевом, чтобы список совпадал:
 * тип меню `top_content_multilevel`, дети раздела /catalog/ приходят из
 * `catalog/.top_menu_new.menu_ext.php` (CCustomNext::getSectionChilds), дерево
 * строит result_modifier.php через CNext::getChilds.
 *
 * Размеры из макета Figma «Чистовик», фрейм «Каталог» (узел 21349:56357):
 * контейнер 1440 с полями 40/52 и зазором 52 между колонками; слева чипы
 * 377×64 с шагом 12 (картинка 64, подпись 18px/25.2, шеврон 24);
 * справа заголовок 36px/39.6 (800) + кнопка «Смотреть все», ниже сетка
 * по 4 плитки 220×108 с зазором 8 (картинка 56, подпись 12px/14.4).
 *
 * Стили — css/newdesign-header.css (префикс .nd-cat), переключение групп —
 * js/newdesign-header.js.
 *
 * ВАЖНО про производительность: на разделы и подразделы приходится под сотню
 * CFile::ResizeImageGet плюс запрос к посадочным (ИБ 21). Считать это на каждый
 * хит дорого, поэтому готовую разметку кладём в кэш. Ключ — от самого дерева
 * меню, так что правка разделов подхватывается сразу: меню пересобирается,
 * дерево меняется, ключ становится другим. Картинку посадочной кэш подхватит
 * в пределах TTL (час).
 */

global $arRegion;

// Находим пункт каталога: его дети — разделы, которые и рисуем.
$arCatalog = null;
foreach($arResult as $arItem)
{
	if($arItem['LINK'] === SITE_DIR.'catalog/' || $arItem['LINK'] === '/catalog/')
	{
		$arCatalog = $arItem;
		break;
	}
}

if(!$arCatalog || empty($arCatalog['CHILD']))
	return;

/**
 * Пункт показываем, если это раздел с галкой «показывать в меню»
 * (UF_SECTION_IN_MENU), либо пункт, добавленный в menu_ext руками — у такого
 * ключа SECTION_IN_MENU нет вовсе (так в список попадают «Перголы»).
 */
$ndShowItem = function($arItem) {
	$arParams = isset($arItem['PARAMS']) && is_array($arItem['PARAMS']) ? $arItem['PARAMS'] : array();
	if(!array_key_exists('SECTION_IN_MENU', $arParams))
		return true;
	return !empty($arParams['SECTION_IN_MENU']);
};

$arSections = array();
foreach($arCatalog['CHILD'] as $arSection)
{
	if($ndShowItem($arSection))
		$arSections[] = $arSection;
}

if(!$arSections)
	return;

// ---------------------------------------------------------------- кэш вывода
$cacheDir = '/nd/catalog_wide';
$cacheKey = 'nd_cat_'.md5(serialize($arSections).'|'.($arRegion ? $arRegion['ID'] : '').'|'.SITE_ID.'|'.LANGUAGE_ID);
$cache = \Bitrix\Main\Data\Cache::createInstance();

if($cache->initCache(3600, $cacheKey, $cacheDir))
{
	$arCached = $cache->getVars();
	echo $arCached['HTML'];
	return;
}

$cache->startDataCache();
ob_start();

/**
 * Иконка раздела из пользовательского поля UF_ND_ICON («фото-иконка раздела»).
 * Это главный источник: картинку заводит контент-менеджер прямо в разделе.
 * В параметрах пункта меню пользовательских полей нет, поэтому один раз
 * строим карту «адрес раздела → файл». Поля может не быть вовсе (на локальной
 * копии базы оно появится только после переливки) — тогда молча пропускаем.
 */
$ndSectionUfIcon = function($link, $size) {
	static $arMap;

	if(!isset($arMap))
	{
		$arMap = array();
		$catalogId = (int)CNextCache::$arIBlocks[SITE_ID]['aspro_next_catalog']['aspro_next_catalog'][0];

		global $USER_FIELD_MANAGER;
		$arFields = $catalogId > 0 && $USER_FIELD_MANAGER
			? $USER_FIELD_MANAGER->GetUserFields('IBLOCK_'.$catalogId.'_SECTION')
			: array();

		if(isset($arFields['UF_ND_ICON']))
		{
			$res = CIBlockSection::GetList(
				array('LEFT_MARGIN' => 'ASC'),
				array('IBLOCK_ID' => $catalogId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', '!UF_ND_ICON' => false),
				false,
				array('ID', 'SECTION_PAGE_URL', 'UF_ND_ICON')
			);
			while($arSect = $res->GetNext())
			{
				$fileId = (int)$arSect['UF_ND_ICON'];
				if($fileId > 0)
					$arMap[rtrim($arSect['SECTION_PAGE_URL'], '/').'/'] = $fileId;
			}
		}
	}

	$key = rtrim((string)$link, '/').'/';
	if(empty($arMap[$key]))
		return null;

	// В поле лежит либо вырезка товара (квадратная, её нельзя кадрировать),
	// либо скопированное фото раздела (широкое, его наоборот надо вписать
	// в квадрат). Различаем по пропорции самой картинки.
	$arFile = CFile::GetFileArray($arMap[$key]);
	$w = (int)($arFile['WIDTH'] ?? 0);
	$h = (int)($arFile['HEIGHT'] ?? 0);
	$bSquare = ($w > 0 && $h > 0 && abs($w / $h - 1) <= 0.25);

	$img = CFile::ResizeImageGet(
		$arMap[$key],
		array('width' => $size, 'height' => $size),
		$bSquare ? BX_RESIZE_IMAGE_PROPORTIONAL : BX_RESIZE_IMAGE_EXACT,
		true
	);

	if(!is_array($img) || !$img['src'])
		return null;

	return array('SRC' => $img['src'], 'ICON' => $bSquare);
};

/**
 * Запасная иконка — отрисованная вырезка товара из макета, та же, что
 * в плитках каталога на /catalog/ (шаблон list_catalog_sections_newdesign).
 * Файлы лежат в шаблоне под именем символьного кода раздела:
 * images/newdesign/catalog/<CODE>.png. Код берём из адреса пункта — в его
 * параметрах кода нет, а адрес раздела заканчивается ровно им.
 * Нет файла — ниже подставится фото раздела, как было раньше.
 */
$ndSectionIcon = function($arItem) {
	$path = parse_url((string)$arItem['LINK'], PHP_URL_PATH);
	$code = basename(rtrim((string)$path, '/'));

	// «Перголы» ведут не в каталог, а на статью в «Материалах» — раздела
	// с таким кодом нет, но иконка для них в макете есть (в плитках каталога
	// она тоже прописана отдельно).
	if($code !== '' && mb_strpos($code, 'pergola') !== false)
		$code = 'pergola';

	if($code === '' || !preg_match('/^[a-z0-9_-]+$/i', $code))
		return null;

	$file = SITE_TEMPLATE_PATH.'/images/newdesign/catalog/'.$code.'.png';

	return is_file($_SERVER['DOCUMENT_ROOT'].$file) ? $file : null;
};

/**
 * Картинка раздела: в левой колонке 64×64, в плитке 56×56.
 * PICTURE приходит из menu_ext идентификатором файла.
 */
$ndSectionImg = function($arItem, $size) {
	$id = isset($arItem['PARAMS']['PICTURE']) ? (int)$arItem['PARAMS']['PICTURE'] : 0;
	if($id <= 0)
		return null;
	$img = CFile::ResizeImageGet($id, array('width' => $size, 'height' => $size), BX_RESIZE_IMAGE_PROPORTIONAL, true);
	return (is_array($img) && $img['src']) ? $img['src'] : null;
};

/**
 * Картинки посадочных страниц каталога (ИБ 21) — для ссылок из UF_MENULINK_TOP.
 * У такой ссылки нет раздела, поэтому берём анонс посадочной: сначала по
 * названию (текст ссылки пишут по нему, это надёжнее), потом по URL из
 * свойства CPY_FILTER_TAG. Логика и оговорки — с боевого шаблона.
 */
$ndLandingPics = function() {
	static $arPics;
	if(isset($arPics))
		return $arPics;

	$arPics = array('URL' => array(), 'NAME' => array());
	$res = CIBlockElement::GetList(
		array(),
		array('IBLOCK_ID' => 21, 'ACTIVE' => 'Y'),
		false,
		false,
		array('ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY_CPY_FILTER_TAG')
	);
	while($arLanding = $res->Fetch())
	{
		if(!$arLanding['PREVIEW_PICTURE'])
			continue;

		$url = trim((string)$arLanding['PROPERTY_CPY_FILTER_TAG_VALUE']);
		// Один URL встречается у разных посадочных (опечатка в CPY_FILTER_TAG),
		// поэтому занятый ключ не перезаписываем — картинка досталась бы чужой ссылке.
		if($url !== '')
		{
			$key = rtrim($url, '/').'/';
			if(!isset($arPics['URL'][$key]))
				$arPics['URL'][$key] = $arLanding['PREVIEW_PICTURE'];
		}
		$arPics['NAME'][mb_strtolower(trim($arLanding['NAME']))] = $arLanding['PREVIEW_PICTURE'];
	}

	return $arPics;
};

/**
 * Разбор ссылки из UF_MENULINK_TOP: там лежит готовый HTML вида
 * `<a href="...">Название</a>`. Возвращает href, текст и картинку посадочной.
 */
$ndParseMenuLink = function($sHtml) use ($ndLandingPics) {
	$sHtml = htmlspecialchars_decode($sHtml);
	if(!preg_match('/<a\s+([^>]*?)>(.*?)<\/a>/is', $sHtml, $m))
		return null;

	$attrs = $m[1];
	$text  = trim(strip_tags($m[2]));
	$href  = '';
	if(preg_match('/href\s*=\s*["\']([^"\']+)["\']/i', $attrs, $mh))
		$href = trim($mh[1]);

	if($text === '' || $href === '')
		return null;

	$arPics = $ndLandingPics();
	$picId  = 0;
	$key    = mb_strtolower($text);
	if(isset($arPics['NAME'][$key]))
		$picId = $arPics['NAME'][$key];
	if(!$picId)
	{
		$urlKey = rtrim($href, '/').'/';
		if(isset($arPics['URL'][$urlKey]))
			$picId = $arPics['URL'][$urlKey];
	}

	$src = null;
	if($picId)
	{
		$img = CFile::ResizeImageGet($picId, array('width' => 56, 'height' => 56), BX_RESIZE_IMAGE_PROPORTIONAL, true);
		if(is_array($img) && $img['src'])
			$src = $img['src'];
	}

	return array('LINK' => $href, 'TEXT' => $text, 'IMG' => $src);
};

/**
 * Картинка для пункта, у которого своей нет: берём анонс страницы, на которую
 * он ведёт. Так «Перголы» (пункт добавлен в menu_ext руками и ведёт на статью
 * в «Материалах») получает картинку статьи. Код элемента — последний сегмент
 * URL. Вызывается редко и только для таких пунктов, а вывод целиком кэшируется.
 */
$ndLinkedPagePicture = function($href, $size) {
	$path = parse_url((string)$href, PHP_URL_PATH);
	$code = basename(rtrim((string)$path, '/'));
	if($code === '' || strpos($code, '.') !== false)
		return null;

	$res = CIBlockElement::GetList(
		array(),
		array('=CODE' => $code, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'),
		false,
		array('nTopCount' => 1),
		array('ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE')
	);
	if(!($arElement = $res->Fetch()))
		return null;

	$picId = (int)($arElement['PREVIEW_PICTURE'] ? $arElement['PREVIEW_PICTURE'] : $arElement['DETAIL_PICTURE']);
	if($picId <= 0)
		return null;

	$img = CFile::ResizeImageGet($picId, array('width' => $size, 'height' => $size), BX_RESIZE_IMAGE_PROPORTIONAL, true);
	return (is_array($img) && $img['src']) ? $img['src'] : null;
};

/**
 * Заглушка вместо картинки — как в боевом шаблоне. Нужна не для красоты:
 * у пунктов, добавленных в menu_ext руками («Перголы»), картинки нет вовсе,
 * а у ссылок на посадочные (ИБ 21) не заполнен анонсный рисунок — без
 * заглушки плитка выглядит пустой рамкой.
 */
$ndImgPlaceholder = function() {
	?><svg class="nd-cat__noimg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
		<rect x="2.5" y="2.5" width="19" height="19" rx="2"/>
		<circle cx="8.5" cy="8.5" r="2"/>
		<path d="M21 15L16 10L5 21"/>
	</svg><?
};

/**
 * ID раздела по ссылке пункта меню. В параметрах пункта его нет (menu_ext
 * кладёт только IBLOCK_ID, DEPTH_LEVEL и пользовательские поля), поэтому
 * один раз строим карту «адрес раздела → ID».
 */
$ndSectionIdByLink = function($link) {
	static $arMap;

	if(!isset($arMap))
	{
		$arMap = array();
		$catalogId = (int)CNextCache::$arIBlocks[SITE_ID]['aspro_next_catalog']['aspro_next_catalog'][0];
		if($catalogId > 0)
		{
			$res = CIBlockSection::GetList(
				array('LEFT_MARGIN' => 'ASC'),
				array('IBLOCK_ID' => $catalogId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'),
				false,
				array('ID', 'SECTION_PAGE_URL')
			);
			while($arSect = $res->GetNext())
			{
				$arMap[rtrim($arSect['SECTION_PAGE_URL'], '/').'/'] = (int)$arSect['ID'];
			}
		}
	}

	$key = rtrim((string)$link, '/').'/';

	return isset($arMap[$key]) ? $arMap[$key] : 0;
};

/**
 * Акция раздела: элемент ИБ «Акции и скидки», привязанный к разделу или его
 * родителям свойством LINK_SECT_CATALOG. Если привязка не заполнена, акция
 * общая и в меню раздела не показывается — иначе одна и та же плашка висела бы
 * во всех разделах. Учитываем регион, как остальные блоки акций.
 */
$ndSectionPromo = function($sectionId) {
	$sectionId = (int)$sectionId;
	if($sectionId <= 0)
		return null;

	$iblockId = (int)CNextCache::$arIBlocks[SITE_ID]['aspro_next_content']['aspro_next_stock'][0];
	if($iblockId <= 0)
		return null;

	global $arRegion;

	$arFilter = array(
		'IBLOCK_ID' => $iblockId,
		'ACTIVE' => 'Y',
		'ACTIVE_DATE' => 'Y',
		'PROPERTY_LINK_SECT_CATALOG' => $sectionId,
	);
	if($arRegion && $arRegion['ID'])
	{
		$arFilter[] = array(
			'LOGIC' => 'OR',
			array('PROPERTY_LINK_REGION' => false),
			array('PROPERTY_LINK_REGION' => $arRegion['ID']),
		);
	}

	$res = CIBlockElement::GetList(
		array('SORT' => 'ASC', 'ID' => 'DESC'),
		$arFilter,
		false,
		array('nTopCount' => 1),
		array('ID', 'IBLOCK_ID', 'NAME', 'ACTIVE_TO', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL', 'PROPERTY_REDIRECT')
	);
	if(!($arPromo = $res->GetNext()))
		return null;

	$picId = (int)($arPromo['PREVIEW_PICTURE'] ? $arPromo['PREVIEW_PICTURE'] : $arPromo['DETAIL_PICTURE']);
	if($picId <= 0)
		return null;

	$img = CFile::ResizeImageGet($picId, array('width' => 600, 'height' => 600), BX_RESIZE_IMAGE_PROPORTIONAL, true);
	if(!is_array($img) || !$img['src'])
		return null;

	$till = '';
	if($arPromo['ACTIVE_TO'])
	{
		// ConvertDateTime понимает только свои токены — иначе месяц не подставится
		$till = ConvertDateTime($arPromo['ACTIVE_TO'], 'DD.MM.YYYY');
	}

	return array(
		'NAME' => $arPromo['NAME'],
		'LINK' => (strlen($arPromo['PROPERTY_REDIRECT_VALUE']) ? $arPromo['PROPERTY_REDIRECT_VALUE'] : $arPromo['DETAIL_PAGE_URL']),
		'IMG'  => $img['src'],
		'TILL' => $till,
	);
};

/**
 * Производители раздела: берём только те бренды, что реально встречаются
 * у активных товаров раздела (вместе с подразделами). Считаем группировкой
 * по свойству — так это один запрос вместо выборки всех товаров.
 */
$ndSectionBrands = function($sectionId) {
	$sectionId = (int)$sectionId;
	if($sectionId <= 0)
		return array();

	$catalogId = (int)CNextCache::$arIBlocks[SITE_ID]['aspro_next_catalog']['aspro_next_catalog'][0];
	if($catalogId <= 0)
		return array();

	$res = CIBlockElement::GetList(
		array(),
		array(
			'IBLOCK_ID' => $catalogId,
			'ACTIVE' => 'Y',
			'SECTION_ID' => $sectionId,
			'INCLUDE_SUBSECTIONS' => 'Y',
			'!PROPERTY_BRAND' => false,
		),
		array('PROPERTY_BRAND'),
		false,
		array('ID')
	);

	// при группировке значение свойства приходит в ключе с суффиксом _VALUE
	$arBrandIds = array();
	while($arRow = $res->Fetch())
	{
		$id = (int)$arRow['PROPERTY_BRAND_VALUE'];
		if($id > 0)
			$arBrandIds[$id] = $id;
	}
	if(!$arBrandIds)
		return array();

	$arBrands = array();
	$rsBrands = CIBlockElement::GetList(
		array('SORT' => 'ASC', 'NAME' => 'ASC'),
		array('ID' => array_values($arBrandIds), 'ACTIVE' => 'Y'),
		false,
		false,
		array('ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL')
	);
	while($arBrand = $rsBrands->GetNext())
	{
		$picId = (int)($arBrand['PREVIEW_PICTURE'] ? $arBrand['PREVIEW_PICTURE'] : $arBrand['DETAIL_PICTURE']);
		$src = null;
		if($picId > 0)
		{
			// с запасом под ретину: плитка 168×92, поля 12/16
			$img = CFile::ResizeImageGet($picId, array('width' => 320, 'height' => 176), BX_RESIZE_IMAGE_PROPORTIONAL, true);
			if(is_array($img) && $img['src'])
				$src = $img['src'];
		}
		$arBrands[] = array(
			'NAME' => $arBrand['NAME'],
			'LINK' => $arBrand['DETAIL_PAGE_URL'],
			'IMG'  => $src,
		);
	}

	return $arBrands;
};
?>
<div class="nd-cat">

	<!-- Слева: разделы -->
	<?// Ролей tablist/tab здесь нет намеренно. Раньше стояли, но набор был
	   // неполный: ни role="tabpanel", ни aria-selected, ни управления стрелками.
	   // Читалке экрана такой набор мешает — она объявляет ссылку «вкладка N из 11»
	   // и ждёт поведения вкладок, которого нет. Валидатор Яндекса на эти атрибуты
	   // тоже ругался: «невозможно определить принадлежность данных полей»
	   // (Ирина, 4 сентября 2026).?>
	<div class="nd-cat__aside">
		<?foreach($arSections as $i => $arSection):?>
			<?
			// Порядок источников: поле раздела UF_ND_ICON → вырезка из макета
			// (общая с плитками каталога) → фото раздела.
			$img   = null;
			$bIcon = false;

			if($arUf = $ndSectionUfIcon($arSection['LINK'], 64))
			{
				$img   = $arUf['SRC'];
				$bIcon = $arUf['ICON'];
			}

			if(!$img && ($img = $ndSectionIcon($arSection)))
				$bIcon = true;

			if(!$img)
				$img = $ndSectionImg($arSection, 64);
			// У пунктов не из каталога (добавлены в menu_ext) картинки раздела нет —
			// подставляем анонс страницы, на которую ведёт ссылка.
			if(!$img)
				$img = $ndLinkedPagePicture($arSection['LINK'], 64);
			?>
			<a class="nd-cat__chip<?=($i === 0 ? ' is-active' : '')?>"
			   href="<?=htmlspecialcharsbx($arSection['LINK'])?>"
			   data-nd-cat-target="<?=htmlspecialcharsbx($arSection['LINK'])?>">
				<span class="nd-cat__chip-img">
					<?// Картинки грузим не сразу: панели скрыты, и штатный loading="lazy"
					// в скрытом блоке браузер откладывает до показа — из-за этого при
					// наведении плитки были пустыми. Подменяет src скрипт при первом
					// открытии каталога (js/newdesign-header.js).?>
					<?if($img):?>
						<img class="nd-cat__chip-pic<?=($bIcon ? ' nd-cat__chip-pic--icon' : '')?>" data-nd-src="<?=htmlspecialcharsbx($img)?>" alt="" width="64" height="64">
					<?else:?>
						<?$ndImgPlaceholder();?>
					<?endif;?>
				</span>
				<span class="nd-cat__chip-label"><?=htmlspecialcharsbx($arSection['TEXT'])?></span>
				<?// Шеврон инлайном, а не картинкой: цвет у активного пункта другой,
				// а currentColor позволяет задать оба состояния из CSS.?>
				<svg class="nd-cat__chip-arrow" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M8.86321 5.36327C9.21469 5.0118 9.78518 5.0118 10.1367 5.36327L16.1367 11.3633C16.488 11.7148 16.4881 12.2853 16.1367 12.6367L10.1367 18.6367C9.7852 18.9882 9.21469 18.9881 8.86321 18.6367C8.51174 18.2852 8.51174 17.7147 8.86321 17.3633L14.2265 12L8.86321 6.63671C8.51174 6.28524 8.51174 5.71474 8.86321 5.36327Z" fill="currentColor"/>
				</svg>
			</a>
		<?endforeach;?>
	</div>

	<!-- Справа: подразделы выбранного раздела -->
	<div class="nd-cat__panels">
		<?foreach($arSections as $i => $arSection):?>
			<div class="nd-cat__panel<?=($i === 0 ? ' is-active' : '')?>"
			     data-nd-cat-panel="<?=htmlspecialcharsbx($arSection['LINK'])?>">

				<div class="nd-cat__panel-head">
					<div class="nd-cat__panel-title"><?=htmlspecialcharsbx($arSection['TEXT'])?></div>
					<a class="nd-cat__all" href="<?=htmlspecialcharsbx($arSection['LINK'])?>">Смотреть все</a>
				</div>

				<?
				// Плитки: сначала подразделы, потом ссылки на посадочные страницы
				// из UF_MENULINK_TOP (в «Ступенях» это «Полнотелая ступень»
				// и «Пустотелая ступень» — разделами они не являются).
				$arCards = array();

				if(!empty($arSection['CHILD']))
				{
					foreach($arSection['CHILD'] as $arChild)
					{
						if(!$ndShowItem($arChild))
							continue;
						$arCards[] = array(
							'LINK'     => $arChild['LINK'],
							'TEXT'     => $arChild['TEXT'],
							'IMG'      => $ndSectionImg($arChild, 56),
							'SELECTED' => !empty($arChild['SELECTED']),
						);
					}
				}

				if(!empty($arSection['PARAMS']['MENULINK_TOP']) && is_array($arSection['PARAMS']['MENULINK_TOP']))
				{
					foreach($arSection['PARAMS']['MENULINK_TOP'] as $sLinkHtml)
					{
						$arLink = $ndParseMenuLink($sLinkHtml);
						if($arLink)
						{
							$arLink['SELECTED'] = false;
							$arCards[] = $arLink;
						}
					}
				}
				?>
				<?
				// Акция и производители — по товарам этого раздела (макет:
				// баннер справа от плиток, логотипы строкой внизу). Раздел
				// берём из параметров пункта меню; у пунктов не из каталога
				// («Перголы») его нет — там блоков не будет.
				$ndSectionId = $ndSectionIdByLink($arSection['LINK']);
				$arPromo = $ndSectionId ? $ndSectionPromo($ndSectionId) : null;
				$arBrands = $ndSectionId ? $ndSectionBrands($ndSectionId) : array();
				?>
				<?if($arCards || $arPromo):?>
					<div class="nd-cat__body">
						<?if($arCards):?>
							<div class="nd-cat__grid">
								<?foreach($arCards as $arCard):?>
									<a class="nd-cat__card<?=($arCard['SELECTED'] ? ' is-active' : '')?>" href="<?=htmlspecialcharsbx($arCard['LINK'])?>">
										<span class="nd-cat__card-img">
											<?if($arCard['IMG']):?>
												<img data-nd-src="<?=htmlspecialcharsbx($arCard['IMG'])?>" alt="" width="56" height="56">
											<?else:?>
												<?$ndImgPlaceholder();?>
											<?endif;?>
										</span>
										<span class="nd-cat__card-label"><?=htmlspecialcharsbx($arCard['TEXT'])?></span>
									</a>
								<?endforeach;?>
							</div>
						<?endif;?>

						<?if($arPromo):?>
							<a class="nd-cat__promo" href="<?=htmlspecialcharsbx($arPromo['LINK'])?>">
								<img data-nd-src="<?=htmlspecialcharsbx($arPromo['IMG'])?>" alt="<?=htmlspecialcharsbx($arPromo['NAME'])?>">
								<?if($arPromo['TILL']):?>
									<span class="nd-cat__promo-till">Акция до <?=htmlspecialcharsbx($arPromo['TILL'])?></span>
								<?endif;?>
							</a>
						<?endif;?>
					</div>
				<?endif;?>

				<?if($arBrands):?>
					<div class="nd-cat__brands">
						<div class="nd-cat__brands-title">Производители</div>
						<div class="nd-cat__brands-list">
							<?foreach($arBrands as $arBrand):?>
								<a class="nd-cat__brand" href="<?=htmlspecialcharsbx($arBrand['LINK'])?>" title="<?=htmlspecialcharsbx($arBrand['NAME'])?>">
									<?if($arBrand['IMG']):?>
										<img data-nd-src="<?=htmlspecialcharsbx($arBrand['IMG'])?>" alt="<?=htmlspecialcharsbx($arBrand['NAME'])?>">
									<?else:?>
										<span class="nd-cat__brand-name"><?=htmlspecialcharsbx($arBrand['NAME'])?></span>
									<?endif;?>
								</a>
							<?endforeach;?>
						</div>
					</div>
				<?endif;?>
			</div>
		<?endforeach;?>
	</div>

</div>
<?
$html = ob_get_clean();
$cache->endDataCache(array('HTML' => $html));
echo $html;
?>
