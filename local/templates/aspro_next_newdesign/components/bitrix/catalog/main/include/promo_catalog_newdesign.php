<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Плитки акций внутри списка товаров (новый дизайн).
 * Макет: Figma «Категория» 20464:371289 — плитка занимает обычную ячейку сетки.
 *
 * Данные — инфоблок «Акции и скидки» (aspro_next_stock):
 *   SHOW_ON_CATALOG_PAGE — «Показывать в списке элементов каталога»;
 *   IMAGE_FOR_CATALOG    — «Изображение для списка каталога товаров»;
 *   LINK_SECT_CATALOG    — привязка к разделам каталога (пусто = во всех);
 *   LINK_REGION          — привязка к регионам (пусто = во всех).
 *
 * Разметка печатается ПУЛОМ вне сетки, а по ячейкам её раскладывает
 * js/newdesign-catalog.js: позиция в макете задана рядом («последняя ячейка
 * второго ряда»), а число колонок зависит от ширины экрана.
 */

/* Никаких global: файл подключается в область видимости шаблона комплексного
   компонента, и `global $arSection` подменил бы его локальный $arSection
   пустым глобальным — после этого CNext::checkBreadcrumbsChain() обнуляет
   хлебные крошки и страница падает на array_merge. */
$ndPromoIblockId = (int)CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_stock"][0];
$ndPromoRegion = (isset($arRegion) && $arRegion ? $arRegion : array());

if($ndPromoIblockId && $arSection["ID"]){
	// раздел и все его родители: акция, привязанная к «Террасной доске»,
	// должна показываться и в её подразделах
	$ndSectionIds = array();
	$ndRsChain = CIBlockSection::GetNavChain((int)$arParams["IBLOCK_ID"], (int)$arSection["ID"], array("ID"));
	while($ndChainItem = $ndRsChain->Fetch()){
		$ndSectionIds[] = (int)$ndChainItem["ID"];
	}
	if(!$ndSectionIds){
		$ndSectionIds[] = (int)$arSection["ID"];
	}

	$ndPromoFilter = array(
		"IBLOCK_ID" => $ndPromoIblockId,
		"ACTIVE" => "Y",
		"ACTIVE_DATE" => "Y",
		"!PROPERTY_SHOW_ON_CATALOG_PAGE" => false,
		"!PROPERTY_IMAGE_FOR_CATALOG" => false,
		// LOGIC => OR кладём только подгруппой: на верхнем уровне он
		// распространился бы на весь фильтр, включая IBLOCK_ID
		array(
			"LOGIC" => "OR",
			array("PROPERTY_LINK_SECT_CATALOG" => false),
			array("PROPERTY_LINK_SECT_CATALOG" => $ndSectionIds),
		),
	);

	if($ndPromoRegion && $ndPromoRegion["ID"]){
		$ndPromoFilter[] = array(
			"LOGIC" => "OR",
			array("PROPERTY_LINK_REGION" => false),
			array("PROPERTY_LINK_REGION" => $ndPromoRegion["ID"]),
		);
	}

	$ndPromoItems = CNextCache::CIBLockElement_GetList(
		array(
			"SORT" => "ASC",
			"CACHE" => array("MULTI" => "Y", "TAG" => CNextCache::GetIBlockCacheTag($ndPromoIblockId)),
		),
		$ndPromoFilter,
		false,
		false,
		array("ID", "IBLOCK_ID", "NAME", "DETAIL_PAGE_URL", "PROPERTY_IMAGE_FOR_CATALOG", "PROPERTY_REDIRECT")
	);

	// множественные свойства в фильтре размножают строки выборки — отбираем по ID
	$ndPromoUnique = array();
	if($ndPromoItems){
		foreach($ndPromoItems as $ndPromoItem){
			$ndPromoUnique[$ndPromoItem["ID"]] = $ndPromoItem;
		}
	}

	if($ndPromoUnique):?>
		<div id="nd-promo-pool" style="display:none;">
			<?foreach($ndPromoUnique as $ndPromoItem):?>
				<?
				$ndPromoFile = CFile::GetFileArray($ndPromoItem["PROPERTY_IMAGE_FOR_CATALOG_VALUE"]);
				if(!$ndPromoFile)
					continue;
				$ndPromoUrl = (strlen($ndPromoItem["PROPERTY_REDIRECT_VALUE"]) ? $ndPromoItem["PROPERTY_REDIRECT_VALUE"] : $ndPromoItem["DETAIL_PAGE_URL"]);
				?>
				<div class="item_block nd-promo-cell">
					<a href="<?=htmlspecialcharsbx($ndPromoUrl)?>">
						<img src="<?=htmlspecialcharsbx($ndPromoFile["SRC"])?>" alt="<?=htmlspecialcharsbx($ndPromoItem["NAME"])?>" loading="lazy" />
					</a>
				</div>
			<?endforeach;?>
		</div>
	<?endif;
}
?>
