<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>

<?/* Догрузка раздела на странице бренда просит у этого же шаблона голые
   карточки. Всё, что печатается один раз на список — обёртки, стили, общие
   скрипты, — тогда пропускаем: на странице это уже есть, а второй экземпляр
   скриптов навесил бы вторые обработчики на те же клики. */?>
<?$ldItemsOnly = (($arParams['LD_ITEMS_ONLY'] ?? '') === 'Y');?>

<?/* Страница бренда: где кончается один раздел и начинается следующий.
   Считаем сразу — заголовок первого раздела печатается ещё до сетки, а
   result_modifier к этому моменту уже разложил товары по разделам и
   перенумеровал $arResult['ITEMS'] подряд, с 0. */?>
<?
$ldHeads = array();      // индекс первой карточки раздела => сам раздел
$ldTails = array();      // индекс последней карточки раздела => сам раздел
$ldLastTail = null;      // раздел, которым список заканчивается
$ldAjaxQuery = (string)($arParams['LD_AJAX_QUERY'] ?? '');
/* Кто отдаёт догруженные карточки: у бренда свой обработчик, у товаров акции
   свой — оба зовут тот же include/brand_products.php. */
$ldAjaxUrl = (string)($arParams['LD_AJAX_URL'] ?? '');
if($ldAjaxUrl === '')
	$ldAjaxUrl = '/local/ajax/brand_products.php';
$ldMeta = (array)($arResult['LD_SECTIONS_META'] ?? array());

/* Сетка бренда пятиколоночная (макет «Категория производителя»: карточки по
   267px вплотную), тогда как в разделе каталога и на главной колонок четыре.
   Поэтому у неё свой класс, а не общий .catalog_block. */
$ldBrandGrid = (!empty($arResult['LD_GROUPED']) || !empty($arResult['LD_FLATTENED'])) ? ' nd-brandsect__grid' : '';

if(!empty($arResult['LD_GROUPED']) && !$ldItemsOnly){
	$ldPrevSid = null;
	$ldPrevSection = null;
	foreach($arResult['ITEMS'] as $ldI => $ldItem){
		$ldSid = (int)$ldItem['IBLOCK_SECTION_ID'];
		if($ldSid === $ldPrevSid)
			continue;
		if($ldPrevSection !== null)
			$ldTails[$ldI - 1] = $ldPrevSection;
		// товары без раздела идут общей кучей в конец, их не подписываем
		if(!empty($ldItem['LD_SECTION']))
			$ldHeads[$ldI] = $ldItem['LD_SECTION'];
		$ldPrevSid = $ldSid;
		$ldPrevSection = (!empty($ldItem['LD_SECTION']) ? $ldItem['LD_SECTION'] : null);
	}
	// кнопка последнего раздела печатается уже за сеткой, после цикла
	$ldLastTail = $ldPrevSection;
}

/* Якорь висит на заголовке: ссылки над списком печатает
   catalog.section/ankor_section и адресует разделы их кодом. */
$ldSectionHead = function($section){
	if(empty($section))
		return '';
	return '<h2 class="nd-brandsect__title" id="'.htmlspecialcharsbx($section['CODE']).'">'
		.htmlspecialcharsbx($section['NAME']).'</h2>';
};

/* Кнопка появляется, только если показано не всё. Ведёт она на кусок разметки,
   а не на страницу, поэтому <button>, а не ссылка: без JS переход по такому
   адресу показал бы голые карточки. section=0 — плоский список бренда, у
   которого разделов нет. */
/* «10 товаров», «2 товара», «1 товар» — подпись из макета, а число меняется
   после каждой догрузки, так что склонение считаем, а не пишем руками. */
$ldGoodsWord = function($n){
	$n = (int)$n;
	$last = $n % 10;
	$two = $n % 100;
	if($last === 1 && $two !== 11)
		return 'товар';
	if($last >= 2 && $last <= 4 && ($two < 12 || $two > 14))
		return 'товара';
	return 'товаров';
};

$ldMoreButton = function($sid, $total, $shown) use ($ldAjaxQuery, $ldAjaxUrl, $ldGoodsWord){
	if($ldAjaxQuery === '' || $total <= $shown)
		return '';
	$left = $total - $shown;
	$url = $ldAjaxUrl.'?'.$ldAjaxQuery.'&section='.(int)$sid.'&offset='.(int)$shown;
	return '<div class="nd-brandsect__more">'
		.'<button type="button" class="nd-brandsect__more-btn" data-nd-brand-more'
		.' data-url="'.htmlspecialcharsbx($url).'">Показать еще '.$left.' '.$ldGoodsWord($left).'</button>'
		.'</div>';
};

$ldSectionMore = function($section) use ($ldMeta, $ldMoreButton){
	if(empty($section))
		return '';
	$sid = (int)$section['ID'];
	return $ldMoreButton($sid, (int)($ldMeta[$sid]['TOTAL'] ?? 0), (int)($ldMeta[$sid]['SHOWN'] ?? 0));
};

/* Плоский список бренда — весь список одной сеткой и одна кнопка под ней. */
$ldFlatMeta = (array)($arResult['LD_FLAT_META'] ?? array());
$ldFlatMore = function() use ($ldFlatMeta, $ldMoreButton){
	if(!$ldFlatMeta)
		return '';
	return $ldMoreButton(0, (int)$ldFlatMeta['TOTAL'], (int)$ldFlatMeta['SHOWN']);
};
?>

<?if(!$ldItemsOnly){?>
<style>


/* Для отладки - подсветка скрытых элементов */
div[id*="prop_169"][style*="display: none"] {
    display: none !important;
    /* background: #ff000020; - раскомментировать для отладки */
}



/* Скрываем список складов, пока внутри него нет триггера (т.е. до выполнения JS) */
.product-item-stores .stores-list:not(:has(.stores-trigger)) {
    display: none;
}

.catalog_item.has-stores-block {
    padding-top: 35px;
}

/* Скрываем стандартный блок остатков */
.catalog_item .item-stock {
    display: none !important;
}

/* Контейнер блока складов – привязан к левому верхнему углу карточки */
.product-item-stores {
    position: absolute;
    top: 4px;
    left: 0;
    z-index: 10;
    font-size: 9px;
    margin: 0;
}

/* Общие стили для триггера (без фона и цвета текста – цвета задаются дополнительными классами) */
.stores-trigger {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    font-weight: 500;
    font-size: 12px;
    padding: 0px 4px;
    border-radius: 0 4px 4px 0;
    transition: background 0.2s;
    white-space: nowrap;
}

/* Зелёный – В НАЛИЧИИ */
.stores-trigger-green {
    background: #ebfaef;
    color: #2ca94c;
}
.stores-trigger-green:hover {
    background: #d4f0dc;
}

/* Серый – ПОД ЗАКАЗ */
.stores-trigger-gray {
    background: #eeeef0;
    color: #999;
}
.stores-trigger-gray:hover {
    background: #e2e2e6;
}
.stores-trigger-orange {
    background: #fff5e6;
    color: #ff9500;
}
.stores-trigger-orange:hover {
    background: #ffe8cc;
}

/* Стрелочка */
.stores-arrow {
    font-size: 8px;
    transition: transform 0.2s;
}

/* Выпадающий список */
.stores-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 20;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 10px 10px;
    margin-top: 4px;
    min-width: 125px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 6px;
}

/* Бейджи складов */
.store-badge {
    display: block;
    padding: 0px 4px;
    border-radius: 4px;
    font-size: 9px;
    white-space: nowrap;
}

.store-badge-green {
    background: #ebfaef;
    color: #2ca94c;
}

.store-badge-orange {
    background: #fff5e6;
    color: #ff9500;
}

.store-badge-gray {
    background: #eeeef0;
    color: #999;
}

/* Сообщение "Нет данных о складах" */
.stores-empty {
    color: #999;
    font-style: italic;
    font-size: 9px;
}

</style>

<?/* Стили и скрипт списка нового дизайна. Тегами прямо в разметке, а не через
	SetAdditionalCSS/AddHeadScript: шаблон рисуется, когда <head> уже отдан.
	Защита от повторного подключения — шаблон выводится ещё и по ajax
	(фильтр, «показать ещё»), а на странице бывает несколько списков. */?>
<?if(!defined('ND_CATALOG_ASSETS')){
	define('ND_CATALOG_ASSETS', true);
	$ndAssetsDir = SITE_TEMPLATE_PATH;
	$ndCssFile = $_SERVER['DOCUMENT_ROOT'].$ndAssetsDir.'/css/newdesign-catalog.css';
	$ndJsFile = $_SERVER['DOCUMENT_ROOT'].$ndAssetsDir.'/js/newdesign-catalog.js';
	?>
	<link href="<?=$ndAssetsDir?>/css/newdesign-catalog.css?<?=(file_exists($ndCssFile) ? filemtime($ndCssFile) : '')?>" rel="stylesheet" />
	<script src="<?=$ndAssetsDir?>/js/newdesign-catalog.js?<?=(file_exists($ndJsFile) ? filemtime($ndJsFile) : '')?>"></script>
<?}?>
<?/* Общий скрипт нового дизайна — им работает кнопка «Показать ещё»
	под сеткой (та же, что на отзывах и материалах). */?>
<?if(!defined('ND_UI_JS')){
	define('ND_UI_JS', true);
	$ndUiFile = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/newdesign-ui.js';
	?>
	<script src="<?=SITE_TEMPLATE_PATH?>/js/newdesign-ui.js?<?=(file_exists($ndUiFile) ? filemtime($ndUiFile) : '')?>"></script>
<?}?>
<?}?>

<?global $arRegion;
  $regionID = ($arRegion ? $arRegion['ID'] : '');?>

	<?if( count( $arResult["ITEMS"] ) >= 1 ){?>
	<?if(!$ldItemsOnly && (($arParams["AJAX_REQUEST"]=="N") || !isset($arParams["AJAX_REQUEST"]))){?>
	 <div class="top_wrapper nd-catlist row margin0 <?=($arParams["SHOW_UNABLE_SKU_PROPS"] != "N" ? "show_un_props" : "unshow_un_props");?>">	
	 
	<?if(strlen($arParams['TITLE'])):?>
			<hr /><h5><?=$arParams['TITLE'];?></h5>
	<?endif;?>
	<?/* Заголовок первого раздела бренда — до сетки: сетка обведена рамкой,
	   и заголовок внутри неё смотрелся бы строкой таблицы. */?>
	<?=$ldSectionHead(isset($ldHeads[0]) ? $ldHeads[0] : null)?>
	<?/* nd-catlist__list + nd-catlist__nav у навигации — на эту пару завязана
	   кнопка «Показать ещё» из js/newdesign-ui.js (она ищет список по имени
	   блока из класса обёртки навигации). */?>
	<div id="portfolio_loader" class="catalog_block nd-catlist__list items block_list margin0 row flexbox<?=$ldBrandGrid?>">


	<?}?>

		<?
		$currencyList = '';
		if (!empty($arResult['CURRENCIES'])){
			$templateLibrary[] = 'currency';
			$currencyList = CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true);
		}
		$templateData = array(
			'TEMPLATE_LIBRARY' => $templateLibrary,
			'CURRENCIES' => $currencyList
		);
		unset($currencyList, $templateLibrary);
		$arParams["BASKET_ITEMS"]=($arParams["BASKET_ITEMS"] ? $arParams["BASKET_ITEMS"] : array());
		$arOfferProps = implode(';', $arParams['OFFERS_CART_PROPERTIES']);
		switch ($arParams["LINE_ELEMENT_COUNT"]){
			case '1':
			case '2':
				$col=2;
				break;
			case '3':
				$col=3;
				break;
			case '5':
				$col=5;
				break;
			case '4':
				$col=4;
				break;	
			default:
				
				break;
		}
		if($arParams["LINE_ELEMENT_COUNT"] > 5)
			$col = 5;?>
<?
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/bitrix/components/maxyss/measure_unit/templates/aspro_list_tp/style.css');
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/bitrix/components/maxyss/measure_unit/templates/aspro_list_tp/script.js'); 
?>
	  <? $arr_count_article = array(); ?>

	  <?foreach($arResult["ITEMS"] as $i => $arItem){?>

		<?/* Начался следующий раздел бренда: закрываем сетку предыдущего,
		   ставим его кнопку и заголовок нового, открываем сетку заново.
		   Раздел = отдельная сетка, а не строка внутри общей: сетка обведена
		   рамкой, и заголовок с кнопкой внутри неё читались бы её строками. */?>
		<?if($i > 0 && isset($ldHeads[$i])){?>
			</div>
			<?=$ldSectionMore(isset($ldTails[$i - 1]) ? $ldTails[$i - 1] : null)?>
			<?=$ldSectionHead($ldHeads[$i])?>
			<div class="catalog_block nd-catlist__list items block_list margin0 row flexbox<?=$ldBrandGrid?>">
		<?}?>

		
			<div  class="item_block col-<?=$col;?> col-lg-3 col-md-4  col-sm-4 col-xs-6 ">
		
				<div class="catalog_item_wrapp item">
		
				
					



				<?/*basket_props_block*/?>
					<div class="basket_props_block" id="bx_basket_div_<?=$arItem["ID"];?>" style="display: none;">
						<?if (!empty($arItem['PRODUCT_PROPERTIES_FILL'])){
							foreach ($arItem['PRODUCT_PROPERTIES_FILL'] as $propID => $propInfo){?>
								<input type="hidden" name="<? echo $arParams['PRODUCT_PROPS_VARIABLE']; ?>[<? echo $propID; ?>]" value="<? echo htmlspecialcharsbx($propInfo['ID']); ?>">
								<?if (isset($arItem['PRODUCT_PROPERTIES'][$propID]))
									unset($arItem['PRODUCT_PROPERTIES'][$propID]);
							}
						}
						$arItem["EMPTY_PROPS_JS"]="Y";
						$emptyProductProperties = empty($arItem['PRODUCT_PROPERTIES']);
						if (!$emptyProductProperties){
							$arItem["EMPTY_PROPS_JS"]="N";?>
							<div class="wrapper">
								<table>
									<?foreach ($arItem['PRODUCT_PROPERTIES'] as $propID => $propInfo){?>
										<tr>
											<td><? echo $arItem['PROPERTIES'][$propID]['NAME']; ?></td>
											<td>
												<?if('L' == $arItem['PROPERTIES'][$propID]['PROPERTY_TYPE']	&& 'C' == $arItem['PROPERTIES'][$propID]['LIST_TYPE']){
													foreach($propInfo['VALUES'] as $valueID => $value){?>
														<label>
															<input type="radio" name="<? echo $arParams['PRODUCT_PROPS_VARIABLE']; ?>[<? echo $propID; ?>]" value="<? echo $valueID; ?>" <? echo ($valueID == $propInfo['SELECTED'] ? '"checked"' : ''); ?>><? echo $value; ?>
														</label>
													<?}
												}else{?>
													<select name="<? echo $arParams['PRODUCT_PROPS_VARIABLE']; ?>[<? echo $propID; ?>]"><?
														foreach($propInfo['VALUES'] as $valueID => $value){?>
															<option value="<? echo $valueID; ?>" <? echo ($valueID == $propInfo['SELECTED'] ? '"selected"' : ''); ?>><? echo $value; ?></option>
														<?}?>
													</select>
												<?}?>
											</td>
										</tr>
									<?}?>
								</table>
							</div>
							<?}?>
					</div>
				<?/*basket_props_block*/?>
				
					<?
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')));
					
					$arItem["strMainID"] = $this->GetEditAreaId($arItem['ID']);
					$arItemIDs=CNext::GetItemsIDs($arItem);
					
					$totalCount = CNext::GetTotalCount($arItem, $arParams);
					$arQuantityData = CNext::GetQuantityArray($totalCount, $arItemIDs["ALL_ITEM_IDS"]);

					$item_id = $arItem["ID"];
					$strMeasure = '';
					$arAddToBasketData;
					if(!$arItem["OFFERS"] || $arParams['TYPE_SKU'] !== 'TYPE_1'){
						if($arParams["SHOW_MEASURE"] == "Y" && $arItem["CATALOG_MEASURE"]){
							$arMeasure = CCatalogMeasure::getList(array(), array("ID" => $arItem["CATALOG_MEASURE"]), false, false, array())->GetNext();
							$strMeasure = $arMeasure["SYMBOL_RUS"];
						}
						$arAddToBasketData = CNext::GetAddToBasketArray($arItem, $totalCount, $arParams["DEFAULT_COUNT"], $arParams["BASKET_URL"], false, $arItemIDs["ALL_ITEM_IDS"], 'small', $arParams);
					}
					elseif($arItem["OFFERS"]){
						$strMeasure = $arItem["MIN_PRICE"]["CATALOG_MEASURE_NAME"];
						/*дописала*/$arParams['OFFERS_CUSTOM'] = true;
					}
					$elementName = ((isset($arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) && $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) ? $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] : $arItem['NAME']);

					//Вывод артикула элемента
					$isArticle=$arItem["PROPERTIES"]["CML2_ARTICLE"]["VALUE"];
					//Показать или спрятать размеры элемента
					//$elementDlina = $arItem["PROPERTIES"]["PROP_2065"]["VALUE"];
					//$elementShirina = $arItem["PROPERTIES"]["IT_8"]["VALUE"];
					//$elementTolshina = $arItem["PROPERTIES"]["IT_6"]["VALUE"];
					//Показать или спрятать размеры элемента
					?>
					
	
					
					<div class="catalog_item main_item_wrapper item_wrap <?=(($_GET['q'])) ? 's' : ''?>" id="<?=$arItemIDs["strMainID"];?>">
					<div class="inner_wrap">
						<?
						/* Метки товара. В макете это текстовые чипы в левом верхнем углу
						   картинки, цвет — по смыслу метки. Собираем оба источника:
						   свойство-список («Наши предложения») и текстовую «Метку». */
						$ndTags = array();
						$ndStikerProp = ($arParams["STIKERS_PROP"] ? $arParams["STIKERS_PROP"] : "HIT");
						/* «Хиты месяца» на карточке не показываем (Ирина, 2026-08-11) —
						   это служебная пометка для вкладки блока «Может заинтересовать»,
						   покупателю она ничего не говорит. Отсекаем по XML_ID HIT_MONTH:
						   он одинаков на всех средах, в отличие от ID значения; на текст
						   тоже завязываться нельзя — его правят из админки. */
						$ndStikerSrc = $arItem["PROPERTIES"][$ndStikerProp];
						if(is_array($ndStikerSrc)){
							if(is_array($ndStikerSrc['VALUE'])){
								foreach($ndStikerSrc['VALUE'] as $ndI => $ndV){
									if(isset($ndStikerSrc['VALUE_XML_ID'][$ndI]) && $ndStikerSrc['VALUE_XML_ID'][$ndI] === 'HIT_MONTH')
										unset($ndStikerSrc['VALUE'][$ndI], $ndStikerSrc['VALUE_XML_ID'][$ndI]);
								}
							}
							elseif(isset($ndStikerSrc['VALUE_XML_ID']) && $ndStikerSrc['VALUE_XML_ID'] === 'HIT_MONTH'){
								$ndStikerSrc['VALUE'] = '';
							}
						}
						foreach(CNext::GetItemStickers($ndStikerSrc) as $arSticker){
							if(strlen(trim($arSticker['VALUE'])))
								$ndTags[] = $arSticker['VALUE'];
						}
						if($arParams["SALE_STIKER"] && $arItem["PROPERTIES"][$arParams["SALE_STIKER"]]["VALUE"]){
							foreach((array)$arItem["PROPERTIES"][$arParams["SALE_STIKER"]]["VALUE"] as $val){
								if(strlen(trim($val)))
									$ndTags[] = $val;
							}
						}

						/* Класс чипа по ключевому слову: тексты меток задаются вручную
						   в админке и одинакового набора значений у них нет. */
						$ndTagClass = function($text){
							$t = mb_strtolower(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'), 'UTF-8');
							if(mb_strpos($t, 'остатк') !== false || mb_strpos($t, 'склад') !== false)
								return 'nd-tag--rest';
							if(mb_strpos($t, 'распродаж') !== false || mb_strpos($t, 'акци') !== false)
								return 'nd-tag--sale';
							if(mb_strpos($t, 'усилен') !== false)
								return 'nd-tag--strong';
							if(mb_strpos($t, 'новинк') !== false)
								return 'nd-tag--new';
							if(mb_strpos($t, 'доставк') !== false)
								return 'nd-tag--delivery';
							return '';
						};

						/* Плашка гарантии («25 лет») — свойство SET; в макете она в правом
						   нижнем углу картинки, поэтому вынесена из ссылки-миниатюры. */
						$ndWarrantyClass = ($arItem['PROPERTIES']['SET']['VALUE'] ? $arItem['PROPERTIES']['SET']['VALUE_XML_ID'] : '');
						?>
						<div class="image_wrapper_block">
							<div class="nd-badges">
								<?/*остатки на складах — из этого блока JS делает плашку «В наличии ▾»*/?>
								<div class="product-item-stores"
									data-product-id="<?=$arItem['ID']?>"
									data-current-offer="<?=$arItem['STORES_DATA'] ? $arItem['CURRENT_OFFER_ID'] ?? $arItem['ID'] : ''?>"
									data-offers-stores='<?=htmlspecialchars(json_encode($arItem['OFFERS_STORES_DATA'] ?? []), ENT_QUOTES)?>'>
									<div class="stores-list" id="stores-list-<?=$arItem['ID']?>">
										<?php if (!empty($arItem['STORES_DATA'])): ?>
											<?php foreach ($arItem['STORES_DATA'] as $store):
												$amount = (int)$store['AMOUNT'];
												$class = $amount >= 30 ? 'green' : ($amount > 0 ? 'orange' : 'gray');
												$text = $amount > 0 ? $amount . ' ' . GetMessage("PIECES") : GetMessage("ON_ORDER");
											?>
												<span class="store-badge store-badge-<?=$class?>">
													<?=htmlspecialcharsbx($store['NAME'])?>: <?=$text?>
												</span>
											<?php endforeach; ?>
										<?php else: ?>
											<span class="stores-empty"><?=GetMessage("NO_STORES")?></span>
										<?php endif; ?>
									</div>
								</div>
								<?if($ndTags):?>
									<div class="nd-badges__tags">
										<?foreach($ndTags as $ndTag):?>
											<span class="nd-tag <?=$ndTagClass($ndTag)?>"><?=$ndTag?></span>
										<?endforeach;?>
									</div>
								<?endif;?>
							</div>
							<?if($ndWarrantyClass):?>
								<div class="<?=$ndWarrantyClass?>"></div>
							<?endif;?>
						<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="thumb shine" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['PICT']; ?>">
						
								<link href="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" />
						
                            <?
                            $a_alt = ($arItem["PREVIEW_PICTURE"] && strlen($arItem["PREVIEW_PICTURE"]['DESCRIPTION']) ? $arItem["PREVIEW_PICTURE"]['DESCRIPTION'] : ($arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"] ? $arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"] : $arItem["NAME"] ));
                            $a_title = ($arItem["PREVIEW_PICTURE"] && strlen($arItem["PREVIEW_PICTURE"]['DESCRIPTION']) ? $arItem["PREVIEW_PICTURE"]['DESCRIPTION'] : ($arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"] ? $arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"] : $arItem["NAME"] ));
                            ?>
                            <?/* плашка гарантии выведена выше, над ссылкой — в макете она
                                 лежит в углу картинки, а не внутри миниатюры */?>
                            <?if( !empty($arItem["PREVIEW_PICTURE"]) ):?>
                                <img  src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>"  alt="<?=$a_alt;?>" title="<?=$a_title;?>" loading="lazy" />
                            <?elseif( !empty($arItem["DETAIL_PICTURE"])):?>
                                <?$img = CFile::ResizeImageGet($arItem["DETAIL_PICTURE"], array( "width" => 170, "height" => 170 ), BX_RESIZE_IMAGE_PROPORTIONAL,true );?>
                                <img   src="<?=$img["src"]?>" alt="<?=$a_alt;?>" title="<?=$a_title;?>"  />
                            <?else:?>
                                <img  src="/images/no_photo_medium.png" alt="<?=$a_alt;?>" title="<?=$a_title;?>" />
                            <?endif;?>
						</a>						
                   
                        	   <?if($arItem['HAS_VIDEO']){?>
                            
								    <a data-fancybox href="" class="colproduct_video">Смотрите видео</a>
																
                            <?}?> 
						</div>
					
							<?/*item_info*/?>
							<div class="item_info <?=$arParams["TYPE_SKU"]?>">
							<div class="item-title"><a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="dark_link"><span><?=$elementName;?><br></span></a></div>
							
							
						<div class="list__catalog_sku" data-product-id="<?=$arItem['ID']?>" style="min-height: 30px;"></div>
							
		<?if($arItem["OFFERS"]){?>
								<?if(!empty($arItem['OFFERS_PROP'])){?>
									<div class="sku_props">

										<div class="bx_catalog_item_scu wrapper_sku" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['PROP_DIV']; ?>">
											<?$arSkuTemplate = array();?>
											<?$arSkuTemplate=CNext::GetSKUPropsArray($arItem['OFFERS_PROPS_JS'], $arResult["SKU_IBLOCK_ID"], $arParams["DISPLAY_TYPE"], $arParams["OFFER_HIDE_NAME_PROPS"]); ?>
											<?foreach ($arSkuTemplate as $code => $strTemplate){
												if (!isset($arItem['OFFERS_PROP'][$code]))
													continue;
												echo '<div>', str_replace('#ITEM#_prop_', $arItemIDs["ALL_ITEM_IDS"]['PROP'], $strTemplate), '</div>';
											}?>
										</div>
										<?$arItemJSParams=CNext::GetSKUJSParams($arResult, $arParams, $arItem);?>

										<script type="text/javascript">
											var <? echo $arItemIDs["strObName"]; ?> = new JCCatalogSection(<? echo CUtil::PhpToJSObject($arItemJSParams, false, true); ?>);
										</script>
									</div>
								<?}?>
							<?}?>

				<?if($arItem["OFFERS"]):?>
					<?if(!empty($arItem['OFFERS_PROP'])):?>
					<div class="article_block item-article" ></div>
					<?endif;?>
				<?else:?>
					<?if ($isArticle): ?>
				
		 <? $arr_count_article[$arItem['ID']] = $isArticle; ?>
					<div data-id="<?= $arItem['ID'] ?>" class="article_block_nooffer" ></div>
					
					<?endif;?>
				<?endif;?>

			
				
	
				
				
				<div class="cost prices clearfix">
								
									<?if( $arItem["OFFERS"]){?>
									
																
										<div class="with_matrix <?=($arParams["SHOW_OLD_PRICE"]=="Y" ? 'with_old' : '');?>" style="display:none;">
											<?/*Закооментировала 17.09.24<div class="price price_value_block"><span class="values_wrapper"></span></div>*/?>
											<?if($arParams["SHOW_OLD_PRICE"]=="Y"):?>
												<?/*Закооментировала 17.09.24<div class="price discount"></div>*/?>
											<?endif;?>
												<?if($arParams["SHOW_DISCOUNT_PERCENT"]=="Y"){?>
												<?/*Закооментировала 17.09.24<div class="sale_block matrix" style="display:none;">
													<div class="sale_wrapper">
													<span class="title"><?=GetMessage("CATALOG_ECONOMY");?></span>
													<div class="text"><span class="values_wrapper"></span></div>
													<div class="clearfix"></div></div>
												</div>
												
												*/?>
											<?}?>
											
										
										</div>
											
										
                                        <?
                                        foreach ($arItem["OFFERS"] as $off){
                                            if($item_id == $off['ID']) {
//
                                                if(is_array($off['PROPERTIES']['UNIT_KOEF']['VALUE'])) {
                                                    foreach ($off['PROPERTIES']['UNIT_KOEF']['DESCRIPTION'] as $key => $un) {
                                                        ?>
                                                        <span style="display: none"><?= round($off['PRICES']['BASE']['DISCOUNT_VALUE'] * $off['PROPERTIES']['UNIT_KOEF']['VALUE'][$key], 0) ?> руб./<?echo $arResult['MEASURE_ALL'][$un]['SYMBOL_RUS'] ?></span>
                                                        <?
                                                    }
                                                    ?>
                                                    <span  style="display: none"><?=$off['PRICES']['BASE']['DISCOUNT_VALUE']?> руб./<?echo $off['ITEM_MEASURE']['TITLE']?></span>
                                                    <?
                                                }
                                            }
                                        }
                                        ?>
										<?\Aspro\Functions\CAsproSku::showItemPrices($arParams, $arItem, $item_id, $min_price_id, $arItemIDs, 'Y');?>
									<?}else{?>
										<?
										$item_id = $arItem["ID"];
										if(isset($arItem['PRICE_MATRIX']) && $arItem['PRICE_MATRIX']) // USE_PRICE_COUNT
										{?>
											<?if($arItem['ITEM_PRICE_MODE'] == 'Q' && count($arItem['PRICE_MATRIX']['ROWS']) > 1):?>
											
												<?//=CNext::showPriceRangeTop($arItem, $arParams, GetMessage("CATALOG_ECONOMY"));?>
											<?endif;?>
											<?=CNext::showPriceMatrix($arItem, $arParams, $strMeasure, $arAddToBasketData);?>
											<?$arMatrixKey = array_keys($arItem['PRICE_MATRIX']['MATRIX']);
											$min_price_id=current($arMatrixKey);?>
											
											<?//добавлено 27.08.24 ?>
											<?\Aspro\Functions\CAsproItem::showItemPrices($arParams, $arItem["PRICES"], $strMeasure, $min_price_id, 'Y');?>
										<?
                                            if (is_array($arItem['PROPERTIES']['UNIT_KOEF']['VALUE'])) {
                                                foreach ($arItem['PROPERTIES']['UNIT_KOEF']['DESCRIPTION'] as $key => $un) {
                                                    ?>
                                                    <span style="display: none"><?= round($arItem['MIN_PRICE']['DISCOUNT_VALUE'] * $arItem['PROPERTIES']['UNIT_KOEF']['VALUE'][$key], 0) ?>
                                                        руб./<?
                                                        echo $arResult['MEASURE_ALL'][$un]['SYMBOL_RUS'] ?></span>
                                                    <?
                                                }
                                                ?>
                                                <span style="display: none"><?= $arItem['MIN_PRICE']['DISCOUNT_VALUE'] ?>
                                                    руб./<?
                                                    echo $arItem['ITEM_MEASURE']['TITLE'] ?></span>
                                                <?
                                            }
										}
										else
										{
											$arCountPricesCanAccess = 0;
											$min_price_id=0;?>
											<?\Aspro\Functions\CAsproItem::showItemPrices($arParams, $arItem["PRICES"], $strMeasure, $min_price_id, 'Y');?>
                                            <?
                                            if (is_array($arItem['PROPERTIES']['UNIT_KOEF']['VALUE'])) {
                                                foreach ($arItem['PROPERTIES']['UNIT_KOEF']['DESCRIPTION'] as $key => $un) {
                                                    ?>
                                                    <span style="display: none"><?= round($arItem['PRICES']['BASE']['DISCOUNT_VALUE'] * $arItem['PROPERTIES']['UNIT_KOEF']['VALUE'][$key], 0) ?>
                                                        руб./<?
                                                        echo $arResult['MEASURE_ALL'][$un]['SYMBOL_RUS'] ?></span>
                                                    <?
                                                }
                                                ?>
                                                <span style="display: none"><?= $arItem['PRICES']['BASE']['DISCOUNT_VALUE'] ?>
                                                    руб./<?
                                                    echo $arItem['ITEM_MEASURE']['TITLE'] ?></span>
                                                <?
                                            }
                                        ?>
										<?}?>

									<?}?>
								</div>
								
								
								
								
								
								
								
								
								
									<?if($arParams["SHOW_DISCOUNT_TIME"]=="Y" && $arParams['SHOW_COUNTER_LIST'] != 'N'){?>
									<?$arUserGroups = $USER->GetUserGroupArray();?>
									<?if($arParams['SHOW_DISCOUNT_TIME_EACH_SKU'] != 'Y' || ($arParams['SHOW_DISCOUNT_TIME_EACH_SKU'] == 'Y' && !$arItem['OFFERS'])):?>
										<?$arDiscounts = CCatalogDiscount::GetDiscountByProduct($item_id, $arUserGroups, "N", $min_price_id, SITE_ID);
										$arDiscount=array();
										if($arDiscounts)
											$arDiscount=current($arDiscounts);
										if($arDiscount["ACTIVE_TO"]){?>
											<div class="view_sale_block <?=($arQuantityData["HTML"] ? '' : 'wq');?>"">
												<div class="count_d_block">
													<span class="active_to hidden"><?=$arDiscount["ACTIVE_TO"];?></span>
													<div class="title"><?=GetMessage("UNTIL_AKC");?></div>
													<span class="countdown values"><span class="item"></span><span class="item"></span><span class="item"></span><span class="item"></span></span>
												</div>
												<?if($arQuantityData["HTML"]):?>
													<div class="quantity_block">
														<div class="title"><?=GetMessage("TITLE_QUANTITY_BLOCK");?></div>
														<div class="values">
															<span class="item">
																<span class="value" <?=((count( $arItem["OFFERS"] ) > 0 && $arParams["TYPE_SKU"] == 'TYPE_1' && $arItem["OFFERS_PROP"]) ? 'style="opacity:0;"' : '')?>><?=$totalCount;?></span>
																<span class="text"><?=GetMessage("TITLE_QUANTITY");?></span>
															</span>
														</div>
													</div>
												<?endif;?>
											</div>
											<?/*item_info*/?>
										<?}?>
									<?else:?>
										<?if($arItem['JS_OFFERS'])
										{
											foreach($arItem['JS_OFFERS'] as $keyOffer => $arTmpOffer2)
											{
												$active_to = '';
												$arDiscounts = CCatalogDiscount::GetDiscountByProduct( $arTmpOffer2['ID'], $arUserGroups, "N", array(), SITE_ID );
												if($arDiscounts)
												{
													foreach($arDiscounts as $arDiscountOffer)
													{
														if($arDiscountOffer['ACTIVE_TO'])
														{
															$active_to = $arDiscountOffer['ACTIVE_TO'];
															break;
														}
													}
												}
												$arItem['JS_OFFERS'][$keyOffer]['DISCOUNT_ACTIVE'] = $active_to;
											}
										}?>
										<div class="view_sale_block" style="display:none;">
											<div class="count_d_block">
													<span class="active_to_<?=$arItem["ID"]?> hidden"><?=$arDiscount["ACTIVE_TO"];?></span>
													<div class="title"><?=GetMessage("UNTIL_AKC");?></div>
													<span class="countdown countdown_<?=$arItem["ID"]?> values"></span>
											</div>
											<?if($arQuantityData["HTML"]):?>
												<div class="quantity_block">
													<div class="title"><?=GetMessage("TITLE_QUANTITY_BLOCK");?></div>
													<div class="values">
														<span class="item">
															<span class="value"><?=$totalCount;?></span>
															<span class="text"><?=GetMessage("TITLE_QUANTITY");?></span>
														</span>
													</div>
												</div>
											<?endif;?>
										</div>
									<?endif;?>
								<?}?>
							</div>
						<?/*item_info*/?>
						
						
						
										<?if(!$arItem["OFFERS"] || $arParams['TYPE_SKU'] !== 'TYPE_1'):?>
									<div class="counter_wrapp <?=($arItem["OFFERS"] && $arParams["TYPE_SKU"] == "TYPE_1" ? 'woffers' : '')?>">
										<?if(($arAddToBasketData["OPTIONS"]["USE_PRODUCT_QUANTITY_LIST"] && $arAddToBasketData["ACTION"] == "ADD") && $arAddToBasketData["CAN_BUY"]):?>
											<div class="counter_block" data-offers="<?=($arItem["OFFERS"] ? "Y" : "N");?>" data-item="<?=$arItem["ID"];?>">
												<span class="minus" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['QUANTITY_DOWN']; ?>">-</span>
												<input type="text" class="text" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['QUANTITY']; ?>" name="<? echo $arParams["PRODUCT_QUANTITY_VARIABLE"]; ?>" value="<?=$arAddToBasketData["MIN_QUANTITY_BUY"]?>" />
												<span class="plus" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['QUANTITY_UP']; ?>" <?=($arAddToBasketData["MAX_QUANTITY_BUY"] ? "data-max='".$arAddToBasketData["MAX_QUANTITY_BUY"]."'" : "")?>>+</span>
											</div>
										<?endif;?>
										<div id="<?=$arItemIDs["ALL_ITEM_IDS"]['BASKET_ACTIONS']; ?>" class="button_block button_list <?=(($arAddToBasketData["ACTION"] == "ORDER"/*&& !$arItem["CAN_BUY"]*/)  || !$arAddToBasketData["CAN_BUY"] || !$arAddToBasketData["OPTIONS"]["USE_PRODUCT_QUANTITY_LIST"] || $arAddToBasketData["ACTION"] == "SUBSCRIBE" ? "wide" : "");?>">
											<!--noindex-->
												<?=$arAddToBasketData["HTML"]?>
											<!--/noindex-->
										</div>
									</div>
									<?
									if(isset($arItem['PRICE_MATRIX']) && $arItem['PRICE_MATRIX']) // USE_PRICE_COUNT
									{?>
										<?if($arItem['ITEM_PRICE_MODE'] == 'Q' && count($arItem['PRICE_MATRIX']['ROWS']) > 1):?>
											<?$arOnlyItemJSParams = array(
												"ITEM_PRICES" => $arItem["ITEM_PRICES"],
												"ITEM_PRICE_MODE" => $arItem["ITEM_PRICE_MODE"],
												"ITEM_QUANTITY_RANGES" => $arItem["ITEM_QUANTITY_RANGES"],
												"MIN_QUANTITY_BUY" => $arAddToBasketData["MIN_QUANTITY_BUY"],
												"ID" => $arItemIDs["strMainID"],
											)?>
											<script type="text/javascript">
												var <? echo $arItemIDs["strObName"]; ?>el = new JCCatalogSectionOnlyElement(<? echo CUtil::PhpToJSObject($arOnlyItemJSParams, false, true); ?>);
											</script>
										<?endif;?>
									<?}?>
								<?elseif($arItem["OFFERS"]):?>
									<?if(empty($arItem['OFFERS_PROP'])){?>
										<div class="offer_buy_block buys_wrapp woffers">
											<?
											$arItem["OFFERS_MORE"] = "Y";
											$arAddToBasketData = CNext::GetAddToBasketArray($arItem, $totalCount, $arParams["DEFAULT_COUNT"], $arParams["BASKET_URL"], false, $arItemIDs["ALL_ITEM_IDS"], 'small read_more1', $arParams);?>
											<!--noindex-->
											<?=$arAddToBasketData["HTML"]?>
											<!--/noindex-->
										</div>
									<?}else{?>
                                            <?
                                                $buttonHTML = '<span class="small read_more1 to-order btn btn-default grey transition_bg transparent animate-load" data-event="jqm" 
												data-param-form_id="TOORDER" data-name="toorder" data-autoload-product_name="" data-autoload-product_id="'.$arItem["ID"].'"><i></i>
												<span>'.(empty($arItem["PROPERTIES"]['IN_STOCK']['PROPERTY_VALUE_ID'])?'Заказать':'В корзину').'</span>
												</span>';
												
												$preopr_price = (int)$arItem["PROPERTIES"]['MINIMUM_PRICE']['VALUE']; 	
												$preopr_price1 = number_format($preopr_price, 2, ',', ' ');	
												$buttonHTML1 = '<span>'.$preopr_price.' руб.</span>';

                                                ?>
											<?//данный блок показывается в сохраненке - В корзину / Заказать?>
										<?//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xxx2024555.txt', print_r($preopr_price1, 1));?>
										<div> <?=$buttonHTML1?></div>
										<div class="offer_buy_block buys_wrapp woffers">
											<div class="counter_wrapp">
                                                <div class="button_block wide">
                                                    <?=$buttonHTML?>
                                                </div>
                                            </div>
										</div>
										<?//данный блок показывается в сохраненке - В корзину / Заказать?>
										
										
									<?}?>
								<?endif;?>


							
						</div><?/*inner_wrap*/?>  


					</div><?/*catalog_item*/?> 
				</div><?/*catalog_item_wrapp*/?> 
				
			</div><?/*item_block*/?>
				  
									<?//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xxx2025.txt', print_r($arItem, 1));?>

        <?if(isset($arItem['OFFERS']) && count($arItem['OFFERS'])>0){
            $offer_unit_yes = false;
            foreach ($arItem['OFFERS'] as $key=>$offer_u){
				if (isset($_GET['test'])){
					if (empty($offer_u['PRODUCT']['QUANTITY']))
						continue;
					//print_r($offer_u['PRODUCT']);
				}
                if(!empty( $offer_u["PROPERTIES"]["UNIT_KOEF"]["VALUE"]))
                    $offer_unit_yes = true;
                $arOfferUnit[$offer_u["ID"]]["ID"] = $offer_u["ID"];
                $arOfferUnit[$offer_u["ID"]]["UNITS"] = $offer_u["PROPERTIES"]["UNIT_KOEF"];
                $arOfferUnit[$offer_u["ID"]]["BASE_OFFER_MEASURE"] = $offer_u['ITEM_MEASURE'];
                $arOfferUnit[$offer_u["ID"]]["PRICES"] = $offer_u["PRICES"];
                $arOfferUnit[$offer_u["ID"]]["MIN_PRICE"] = $offer_u["MIN_PRICE"];
                $arOfferUnit[$offer_u["ID"]]["PRICE_MATRIX"] = $offer_u["PRICE_MATRIX"];
                $arOfferUnit[$offer_u["ID"]]["ITEM_PRICES"] = $offer_u["ITEM_PRICES"];
                $arOfferUnit[$offer_u["ID"]]["BASE_KOEFF_UNIT"] = $offer_u['PROPERTIES']['BASE_KOEF']['DESCRIPTION'];
                foreach ($offer_u["PROPERTIES"] as $key_sku => $sku_property){
    //                        echo '<pre>', print_r($sku_property[VALUE]), '</pre>' ;
                    if(array_key_exists($key_sku, $arResult['SKU_PROPS']) && !empty($sku_property['VALUE'])){

                        if(isset($arResult['SKU_PROPS'][$key_sku]['XML_MAP']))
                            $arOfferUnit[$offer_u["ID"]]["SKU_PROPS"][] = $sku_property['ID'].'_'.$arResult['SKU_PROPS'][$key_sku]['XML_MAP'][$sku_property['~VALUE']];
                        else
                            $arOfferUnit[$offer_u["ID"]]["SKU_PROPS"][] = $sku_property['ID'].'_'.$arResult['SKU_PROPS'][$key_sku]['VALUES'][$sku_property["VALUE_ENUM_ID"]]["ID"];
                    }
                }
            }

            if(!empty($arOfferUnit)) {
                $APPLICATION->IncludeComponent(
                    "maxyss:measure_unit",
                    "aspro_list_tp",
                    array(
                        "BX_BASCKET_OBJ" => $arItemIDs["strObName"],
                        "PRODUCT_ID" => $arItem["ID"],
                        "OFFERS_UNIT_YES" => $offer_unit_yes,
                        "OFFERS_UNIT" => $arOfferUnit,
                        //"CACHE_TIME" => "172800",
                        "CACHE_TYPE" => "N",
                        "MAIN_MEASURE_UNIT" => $arItem["ITEM_MEASURE"]["TITLE"],
                        "MEASURE_BLOCK_SELECTOR" => $arItemIDs["strMainID"],
                        "MEASURE_INPUT_SELECTOR" => ".counter_block .text",
                        "MEASURE_RESULT" => $arItem["PROPERTIES"]["PROP_UNITS"],
                        "ASPRO_MEASURE" => "Y",
                        "COMPONENT_TEMPLATE" => "aspro_list_tp"
                    ), $component, array("HIDE_ICONS" => "Y")
                );
                ?><?
            }
//            unset($offer_unit_yes);
            unset($arOfferUnit);
        }else{
            $arOfferUnit[$arItem["ID"]]["ID"] = $arItem["ID"];
            $arOfferUnit[$arItem["ID"]]["UNITS"] = $arItem["PROPERTIES"]["UNIT_KOEF"];
            $arOfferUnit[$arItem["ID"]]["BASE_OFFER_MEASURE"] = $arItem['ITEM_MEASURE'];
            $arOfferUnit[$arItem["ID"]]["PRICES"] = $arItem["PRICES"];
            $arOfferUnit[$arItem["ID"]]["MIN_PRICE"] = $arItem["MIN_PRICE"];
            $arOfferUnit[$arItem["ID"]]["PRICE_MATRIX"] = $arItem["PRICE_MATRIX"];
            $arOfferUnit[$arItem["ID"]]["ITEM_PRICES"] = $arItem["ITEM_PRICES"];
            $arOfferUnit[$arItem["ID"]]["BASE_KOEFF_UNIT"] = $arItem['PROPERTIES']['BASE_KOEF']['DESCRIPTION'];

            $APPLICATION->IncludeComponent(
                "maxyss:measure_unit",
                "aspro_list_tp",
                array(
                    "BX_BASCKET_OBJ" => $arItemIDs["strObName"],
                    "PRODUCT_ID" => $arItem["ID"],
                    "OFFERS_UNIT_YES" => $offer_unit_yes,
                    "OFFERS_UNIT" => $arOfferUnit,
                    "CACHE_TYPE" => "N",
                    "MAIN_MEASURE_UNIT" => $arItem["ITEM_MEASURE"]["TITLE"],
                    "MEASURE_BLOCK_SELECTOR" => $arItemIDs["strMainID"],
                    "MEASURE_INPUT_SELECTOR" => ".counter_block .text",
                    "MEASURE_RESULT" => $arItem["PROPERTIES"]["UNIT_KOEF"],
                    "ASPRO_MEASURE" => "Y",
                    "COMPONENT_TEMPLATE" => "aspro_list_tp"
                ), $component, array("HIDE_ICONS" => "Y")
            );
            unset($arOfferUnit);
        }?>

		<?}?>
		
	
					    <? $json = json_encode($arr_count_article); ?>
    <script type="text/javascript">
        (function (){
            let name = <?= $json ?>;
            for (const i in name) {
                $('.article_block_nooffer[data-id=' + i + ']').text('Артикул: ' + name[i]);
            }
        })();

    </script>

	<?/* Метка для скрипта: откуда продолжать и сколько ещё осталось. По ней он
	   либо сдвигает кнопку «Показать ещё» на следующую порцию, либо убирает её.
	   Прячем через hidden — в сетке display:none ячейкой не становится. */?>
	<?if($ldItemsOnly && !empty($arResult['LD_MORE'])){?>
		<span hidden data-nd-brand-next data-offset="<?=(int)$arResult['LD_MORE']['OFFSET']?>" data-left="<?=(int)$arResult['LD_MORE']['LEFT']?>"></span>
	<?}?>

	<?/* Догрузке отдаём только карточки: обёртки списка и навигация на странице
	   уже стоят, их надо закрывать и рисовать один раз. */?>
	<?if(!$ldItemsOnly){?>

</div>

	<?/* Кнопка последнего раздела бренда: его сетку только что закрыли.
	   У плоского списка раздел один — сетка целиком, кнопка та же. */?>
	<?=$ldSectionMore($ldLastTail)?>
	<?=$ldFlatMore()?>

	<?if(($arParams["AJAX_REQUEST"]=="N") || !isset($arParams["AJAX_REQUEST"])){?>
			</div>

	<?}?>

	<?if($arParams["AJAX_REQUEST"]=="Y"){?>
		<div class="wrap_nav">
	<?}?>

	<?/* ND_NO_PAGER=Y — совсем без обёртки навигации. Нужен блоку редактора
	      (sprint.editor, iblock_elements__aspro-catalog): состав задаёт
	      контент-менеджер, листать нечего, а пустая обёртка оставляла под
	      сеткой 64 px пустоты (40 высоты + 24 отступа). Другие вызовы флага не
	      передают, для них ничего не меняется. */?>
	<?if(($arParams['ND_NO_PAGER'] ?? '') !== 'Y'){?>
	<div class="bottom_nav nd-catlist__nav <?=$arParams["DISPLAY_TYPE"];?>" <?=($arParams["AJAX_REQUEST"]=="Y" ? "style='display: none; '" : "");?>>
		<?if( $arParams["DISPLAY_BOTTOM_PAGER"] == "Y" ){?><?=$arResult["NAV_STRING"]?><?}?>
	</div>
	<?}?>

	<?if($arParams["AJAX_REQUEST"]=="Y"){?>
		</div>
	<?}?>
	<?}?>
<?}elseif(!$ldItemsOnly){?>
	<script>
		// $(document).ready(function(){
			$('.sort_header').animate({'opacity':'1'}, 500);
		// })
	</script>
	<div class="no_goods catalog_block_view">
		<div class="no_products">
			<div class="wrap_text_empty">
				<?if($_REQUEST["set_filter"]){?>
					<?$APPLICATION->IncludeFile(SITE_DIR."include/section_no_products_filter.php", Array(), Array("MODE" => "html",  "NAME" => GetMessage('EMPTY_CATALOG_DESCR')));?>
				<?}else{?>
					<?$APPLICATION->IncludeFile(SITE_DIR."include/section_no_products.php", Array(), Array("MODE" => "html",  "NAME" => GetMessage('EMPTY_CATALOG_DESCR')));?>
				<?}?>
			</div>
		</div>
		<?if($_REQUEST["set_filter"]){?>
			<span class="button wide"><?=GetMessage('RESET_FILTERS');?></span>
		<?}?>
	</div>
<?}?>

<?/* «Найдено N» для страницы поиска. Шапку результатов печатает шаблон
   catalog.search/main выше по документу, а общее число товаров известно
   только здесь — в NAV_RESULT, и уже с учётом умного фильтра. Отдаём цифру в
   отложенную область: заглушку ShowViewContent('nd_search_count') шапка
   ставит заранее, содержимое приезжает сюда. Флаг ND_SEARCH_COUNT поднимает
   тот же шаблон поиска — на разделе и в блоках главной ничего не меняется. */?>
<?if(!empty($GLOBALS['ND_SEARCH_COUNT']) && !$ldItemsOnly){
	$ndTotal = (isset($arResult['NAV_RESULT']) && is_object($arResult['NAV_RESULT']))
		? (int)$arResult['NAV_RESULT']->NavRecordCount
		: count((array)$arResult['ITEMS']);
	/* Именно SetViewTarget, а не $APPLICATION->AddViewContent: компонент
	   кешируется, и на попадании в кеш его шаблон не выполняется. Битрикс
	   кладёт в кеш только те области, что открыты этой парой (__view →
	   templateCachedData), а прямой AddViewContent пропадает — на проде цифра
	   выводилась пустой. */
	$this->SetViewTarget('nd_search_count');
	echo 'Найдено '.$ndTotal;
	$this->EndViewTarget();
}?>

<?/* Ниже — скрипты уровня всего списка: сообщения BX, обработчики кликов на
   document. Догрузке они не нужны, а второй их экземпляр повесил бы на те же
   клики вторые обработчики. */?>
<?if(!$ldItemsOnly){?>

<script>
	BX.message({
		QUANTITY_AVAILIABLE: '<? echo COption::GetOptionString("aspro.next", "EXPRESSION_FOR_EXISTS", GetMessage("EXPRESSION_FOR_EXISTS_DEFAULT"), SITE_ID); ?>',
		QUANTITY_NOT_AVAILIABLE: '<? echo COption::GetOptionString("aspro.next", "EXPRESSION_FOR_NOTEXISTS", GetMessage("EXPRESSION_FOR_NOTEXISTS"), SITE_ID); ?>',
		ADD_ERROR_BASKET: '<? echo GetMessage("ADD_ERROR_BASKET"); ?>',
		//ADD_ERROR_COMPARE: '<? echo GetMessage("ADD_ERROR_COMPARE"); ?>',
	})
</script>


<script>
$(document).ready(function() {
    $('.list__catalog_sku').each(function() {
        var $block = $(this);
        var productId = $block.data('product-id');
        if (!productId) return;
        $.ajax({
            url: '/ajax/get_colors.php?id=' + productId,
            method: 'GET',
            success: function(html) {
                if (html.trim() !== '') {
                    $block.html(html);
                } else {
                    $block.hide();
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error for product ' + productId + ': ' + error);
            }
        });
    });
});
</script>


<script>
window.STORE_MESSAGES = {
    PIECES: '<?=GetMessageJS("PIECES")?>',
    ON_ORDER: '<?=GetMessageJS("ON_ORDER")?>',
    NO_STORES: '<?=GetMessageJS("NO_STORES")?>'
};
</script>

<script>

BX.ready(function() {
    // Функция обновления остатков
    function updateStores(productId, offerId) {
        var storesBlock = document.querySelector('.product-item-stores[data-product-id="' + productId + '"]');
        if (!storesBlock) return;
        
        var offersStoresJson = storesBlock.getAttribute('data-offers-stores');
        if (!offersStoresJson) return;
        
        try {
            var offersStores = JSON.parse(offersStoresJson);
            var storesData = offersStores[offerId];
            if (!storesData) return;
            
            var container = document.getElementById('stores-list-' + productId);
            if (!container) return;
            
            var html = '';
            for (var i = 0; i < storesData.length; i++) {
                var store = storesData[i];
                var amount = parseInt(store.AMOUNT);
                var className = amount >= 30 ? 'green' : (amount > 0 ? 'orange' : 'gray');
                var amountText = amount > 0 ? amount + ' ' + (window.STORE_MESSAGES ? window.STORE_MESSAGES.PIECES : 'шт.') : (window.STORE_MESSAGES ? window.STORE_MESSAGES.ON_ORDER : 'под заказ');
                html += '<span class="store-badge store-badge-' + className + '">' + 
                        BX.util.htmlspecialchars(store.NAME) + ': ' + amountText + 
                        '</span>';
            }
            container.innerHTML = html;
        } catch(e) {
            console.error('Error updating stores:', e);
        }
    }
    
    // Перехватываем клики по элементам выбора SKU (длин)
    // В Аспро обычно используются такие селекторы - адаптируйте под свою вёрстку
    var skuSelectors = [
        '[data-sku-id]',
        '[data-offer-id]',
        '.sku-item',
        '.offer-item',
        '.catalog-sku-item',
        '.product-sku-item',
        '.aspro-sku-item',
        '.sku-item-link',
        '.item-sku'
    ];
    
    document.addEventListener('click', function(e) {
        // Ищем элемент SKU по одному из селекторов
        var skuElement = null;
        for (var i = 0; i < skuSelectors.length; i++) {
            skuElement = e.target.closest(skuSelectors[i]);
            if (skuElement) break;
        }
        if (!skuElement) return;
        
        // Извлекаем ID предложения
        var offerId = skuElement.dataset.skuId || skuElement.dataset.offerId || skuElement.dataset.id || skuElement.getAttribute('data-value');
        if (!offerId) return;
        
        // Находим карточку товара (родительский блок)
        var productItem = skuElement.closest('.product-item, .catalog-section-item, .item-block, .catalog-item, .product-card');
        if (!productItem) return;
        
        var storesBlock = productItem.querySelector('.product-item-stores');
        if (!storesBlock) return;
        
        var productId = storesBlock.dataset.productId;
        if (productId && offerId) {
            updateStores(productId, offerId);
        }
    });
});

</script>
<?}?>

