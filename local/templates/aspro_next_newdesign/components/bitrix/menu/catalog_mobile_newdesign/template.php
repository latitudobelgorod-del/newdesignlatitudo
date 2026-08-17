<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Мобильный каталог нового дизайна — две панели поверх меню.
 *
 * Макет Figma «Чистовик»: «Каталог» 20512:85302 (список разделов) и
 * «Категория» 20512:85390 (подразделы выбранного раздела с шапкой-карточкой
 * и ссылкой «Показать все»).
 *
 * Данные те же, что у десктопного выпадающего каталога
 * (menu/catalog_wide_newdesign): тип меню top_content_multilevel, дети
 * раздела /catalog/ приходят из catalog/.top_menu_new.menu_ext.php,
 * дерево собирает result_modifier.php.
 *
 * Панели переключает js/newdesign-mobile.js по data-nd-msub /
 * data-nd-msub-open / data-nd-msub-parent. Стили — css/newdesign-mobile.css.
 *
 * Вывод кэшируем целиком: тут под сотню CFile::ResizeImageGet и запрос
 * количеств товаров. Ключ считается от самого дерева меню, поэтому правка
 * разделов подхватывается сразу.
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
$cacheDir = '/nd/catalog_mobile';
$cacheKey = 'nd_mcat_'.md5(serialize($arSections).'|'.($arRegion ? $arRegion['ID'] : '').'|'.SITE_ID.'|'.LANGUAGE_ID);
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
 * Файлы из поля разделов UF_ND_ICON («фото-иконка раздела») — тот же источник,
 * что у выпадающего каталога на десктопе. Пользовательских полей в параметрах
 * пункта меню нет, поэтому один раз строим карту «адрес раздела → файл».
 * Поля может не быть вовсе (локальная копия базы) — тогда карта пустая.
 */
$ndUfIcons = function() {
	static $arMap;

	if(isset($arMap))
		return $arMap;

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

	return $arMap;
};

/**
 * Картинка раздела 64×64 (в макете и первый, и второй уровень одного размера).
 *
 * Порядок источников: поле UF_ND_ICON → вырезка товара из макета, лежащая
 * в шаблоне (images/newdesign/catalog/<код раздела>.png, ими же живут плитки
 * каталога) → PICTURE раздела.
 *
 * Квадратные картинки (вырезки) режем пропорционально, широкие фото — EXACT:
 * в макете фото заполняет квадрат целиком, а вырезку кадрировать нельзя.
 */
$ndSectionImg = function($arItem) use ($ndUfIcons) {
	$link = (string)$arItem['LINK'];
	$arMap = $ndUfIcons();
	$key = rtrim($link, '/').'/';

	$id = isset($arMap[$key]) ? (int)$arMap[$key] : 0;

	if($id <= 0)
	{
		// Вырезка из макета: у пунктов не из каталога («Перголы») раздела нет,
		// но файл под их код в шаблоне лежит.
		$code = basename(rtrim((string)parse_url($link, PHP_URL_PATH), '/'));
		if($code !== '' && mb_strpos($code, 'pergola') !== false)
			$code = 'pergola';

		if($code !== '' && preg_match('/^[a-z0-9_-]+$/i', $code))
		{
			$file = SITE_TEMPLATE_PATH.'/images/newdesign/catalog/'.$code.'.png';
			if(is_file($_SERVER['DOCUMENT_ROOT'].$file))
				return $file;
		}

		$id = isset($arItem['PARAMS']['PICTURE']) ? (int)$arItem['PARAMS']['PICTURE'] : 0;
	}

	if($id <= 0)
		return null;

	$arFile = CFile::GetFileArray($id);
	$w = (int)($arFile['WIDTH'] ?? 0);
	$h = (int)($arFile['HEIGHT'] ?? 0);
	$bSquare = ($w > 0 && $h > 0 && abs($w / $h - 1) <= 0.25);

	$img = CFile::ResizeImageGet(
		$id,
		array('width' => 128, 'height' => 128),
		$bSquare ? BX_RESIZE_IMAGE_PROPORTIONAL : BX_RESIZE_IMAGE_EXACT,
		true
	);

	return (is_array($img) && $img['src']) ? $img['src'] : null;
};

/**
 * Количество товаров в разделе — цифра рядом с названием на втором уровне
 * макета. Считаем одним запросом на весь инфоблок (CIBlockSection::GetList
 * с $bIncCnt) и раскладываем по адресу раздела: у пунктов меню ID раздела нет.
 */
$ndSectionCounts = function() {
	static $arMap;
	if(isset($arMap))
		return $arMap;

	$arMap = array();
	$catalogId = (int)CNextCache::$arIBlocks[SITE_ID]['aspro_next_catalog']['aspro_next_catalog'][0];
	if($catalogId <= 0)
		return $arMap;

	$res = CIBlockSection::GetList(
		array('LEFT_MARGIN' => 'ASC'),
		array(
			'IBLOCK_ID' => $catalogId,
			'ACTIVE' => 'Y',
			'GLOBAL_ACTIVE' => 'Y',
			'CNT_ACTIVE' => 'Y',
			// с подразделами: у раздела верхнего уровня своих товаров может не быть
			'CNT_ALL' => 'Y',
		),
		true,
		array('ID', 'SECTION_PAGE_URL')
	);
	while($arSect = $res->GetNext())
	{
		$arMap[rtrim($arSect['SECTION_PAGE_URL'], '/').'/'] = (int)$arSect['ELEMENT_CNT'];
	}

	return $arMap;
};

/** Заглушка вместо картинки — у пунктов из menu_ext её не бывает. */
$ndImgPlaceholder = function() {
	?><svg class="nd-msub__noimg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
		<rect x="2.5" y="2.5" width="19" height="19" rx="2"/>
		<circle cx="8.5" cy="8.5" r="2"/>
		<path d="M21 15L16 10L5 21"/>
	</svg><?
};

$arCounts = $ndSectionCounts();
?>
<!-- Первый уровень: разделы каталога -->
<div class="nd-msub" data-nd-msub="catalog" data-nd-msub-key="<?=htmlspecialcharsbx(SITE_DIR.'catalog/')?>" hidden>
	<div class="nd-msub__head">
		<button class="nd-msub__back" type="button" data-nd-msub-back aria-label="Назад"></button>
		<span class="nd-msub__title">Каталог</span>
		<button class="nd-msub__close" type="button" data-nd-close aria-label="Закрыть"></button>
	</div>
	<div class="nd-msub__list">
		<?foreach($arSections as $i => $arSection):?>
			<?
			$img       = $ndSectionImg($arSection);
			$arCards   = array();
			$bHasChild = false;

			if(!empty($arSection['CHILD']))
			{
				foreach($arSection['CHILD'] as $arChild)
				{
					if($ndShowItem($arChild))
						$bHasChild = true;
				}
			}
			?>
			<?// Раздел без подразделов — обычная ссылка, разворачивать нечего.?>
			<a class="nd-msub__row" href="<?=htmlspecialcharsbx($arSection['LINK'])?>"<?=($bHasChild ? ' data-nd-msub-open="catalog-'.$i.'"' : '')?>>
				<span class="nd-msub__pic">
					<?if($img):?>
						<img data-nd-src="<?=htmlspecialcharsbx($img)?>" alt="" width="64" height="64">
					<?else:?>
						<?$ndImgPlaceholder();?>
					<?endif;?>
				</span>
				<span class="nd-msub__name"><?=htmlspecialcharsbx($arSection['TEXT'])?></span>
				<?if($bHasChild):?><i class="nd-msub__arrow"></i><?endif;?>
			</a>
		<?endforeach;?>
	</div>
</div>

<?// Второй уровень: подразделы выбранного раздела ?>
<?foreach($arSections as $i => $arSection):?>
	<?
	$arCards = array();
	if(!empty($arSection['CHILD']))
	{
		foreach($arSection['CHILD'] as $arChild)
		{
			if(!$ndShowItem($arChild))
				continue;
			$key = rtrim((string)$arChild['LINK'], '/').'/';
			$arCards[] = array(
				'LINK' => $arChild['LINK'],
				'TEXT' => $arChild['TEXT'],
				'IMG'  => $ndSectionImg($arChild),
				'CNT'  => isset($arCounts[$key]) ? $arCounts[$key] : 0,
			);
		}
	}
	?>
	<?if(!$arCards) continue;?>
	<div class="nd-msub" data-nd-msub="catalog-<?=$i?>" data-nd-msub-parent="catalog" hidden>
		<div class="nd-msub__head">
			<button class="nd-msub__back" type="button" data-nd-msub-back aria-label="Назад"></button>
			<span class="nd-msub__crumb">Каталог</span>
			<button class="nd-msub__close" type="button" data-nd-close aria-label="Закрыть"></button>
		</div>

		<?// Шапка-карточка раздела: название и ссылка «Показать все» на сам раздел.?>
		<div class="nd-msub__cur">
			<span class="nd-msub__pic">
				<?
				$img = $ndSectionImg($arSection);
				?>
				<?if($img):?>
					<img data-nd-src="<?=htmlspecialcharsbx($img)?>" alt="" width="64" height="64">
				<?else:?>
					<?$ndImgPlaceholder();?>
				<?endif;?>
			</span>
			<span class="nd-msub__cur-body">
				<span class="nd-msub__cur-name"><?=htmlspecialcharsbx($arSection['TEXT'])?></span>
				<a class="nd-msub__all" href="<?=htmlspecialcharsbx($arSection['LINK'])?>">Показать все</a>
			</span>
		</div>

		<div class="nd-msub__list nd-msub__list--sub">
			<?foreach($arCards as $arCard):?>
				<a class="nd-msub__row nd-msub__row--sub" href="<?=htmlspecialcharsbx($arCard['LINK'])?>">
					<span class="nd-msub__pic">
						<?if($arCard['IMG']):?>
							<img data-nd-src="<?=htmlspecialcharsbx($arCard['IMG'])?>" alt="" width="64" height="64">
						<?else:?>
							<?$ndImgPlaceholder();?>
						<?endif;?>
					</span>
					<span class="nd-msub__name nd-msub__name--sub">
						<?=htmlspecialcharsbx($arCard['TEXT'])?><?if($arCard['CNT']):?><sup class="nd-msub__cnt"><?=$arCard['CNT']?></sup><?endif;?>
					</span>
				</a>
			<?endforeach;?>
		</div>
	</div>
<?endforeach;?>
<?
$html = ob_get_clean();
$cache->endDataCache(array('HTML' => $html));
echo $html;
?>
