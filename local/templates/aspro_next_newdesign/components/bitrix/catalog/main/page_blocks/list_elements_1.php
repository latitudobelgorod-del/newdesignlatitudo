<?global $arTheme;?>
<?use Bitrix\Main\Context;?>
<?if($arSeoItem):?>
	<div class="seo_block">
		<?if($arSeoItem["DETAIL_PICTURE"]):?>
			<img src="<?=CFile::GetPath($arSeoItem["DETAIL_PICTURE"]);?>" alt="" title="" class="img-responsive"/>
		<?endif;?>

		<?ob_start();?>
		<?if($arSeoItem["PREVIEW_TEXT"]):?>
			<?=$arSeoItem["PREVIEW_TEXT"]?>
		<?endif;?>
		<?
		$html = ob_get_clean();
		$APPLICATION->AddViewContent('top_desc', $html);
		$APPLICATION->ShowViewContent('top_desc');
		$APPLICATION->ShowViewContent('sotbit_seometa_top_desc');
		?>
		<?if($arParams["USE_SHARE"] == "Y"):?>
			<?$this->SetViewTarget('product_share');?>
			<div class="line_block share top <?=($arParams['USE_RSS'] == 'Y' ? 'rss-block' : '');?>">
				<?$APPLICATION->IncludeFile(SITE_DIR."include/share_buttons.php", Array(), Array("MODE" => "html", "NAME" => GetMessage('CT_BCE_CATALOG_SOC_BUTTON')));?>
			</div>
			<?$this->EndViewTarget();?>
		<?endif;?>

		<?if($arSeoItem["PROPERTY_FORM_QUESTION_VALUE"]):?>
			<table class="order-block noicons">
				<tbody>
					<tr>
						<td class="col-md-9 col-sm-8 col-xs-7 valign">
							<div class="text">
								<?$APPLICATION->IncludeComponent(
									 'bitrix:main.include',
									 '',
									 Array(
										  'AREA_FILE_SHOW' => 'page',
										  'AREA_FILE_SUFFIX' => 'ask',
										  'EDIT_TEMPLATE' => ''
									 )
								);?>
							</div>
						</td>
						<td class="col-md-3 col-sm-4 col-xs-5 valign">
							<div class="btns">
								<span><span class="btn btn-default btn-lg white transparent animate-load" data-event="jqm" data-param-form_id="ASK" data-name="question"><span><?=(strlen($arParams['S_ASK_QUESTION']) ? $arParams['S_ASK_QUESTION'] : GetMessage('S_ASK_QUESTION'))?></span></span></span>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		<?endif;?>
		<?if($arSeoItem["PROPERTY_TIZERS_VALUE"]):?>
			<?$GLOBALS["arLandingTizers"] = array("ID" => $arSeoItem["PROPERTY_TIZERS_VALUE"]);?>
			<?$APPLICATION->IncludeComponent(
				"bitrix:news.list",
				"next",
				array(
					"IBLOCK_TYPE" => "aspro_next_content",
					"IBLOCK_ID" => CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_tizers"][0],
					"NEWS_COUNT" => "4",
					"SORT_BY1" => "SORT",
					"SORT_ORDER1" => "ASC",
					"SORT_BY2" => "ID",
					"SORT_ORDER2" => "DESC",
					"FILTER_NAME" => "arLandingTizers",
					"FIELD_CODE" => array(
						0 => "",
						1 => "",
					),
					"PROPERTY_CODE" => array(
						0 => "LINK",
						1 => "",
					),
					"CHECK_DATES" => "Y",
					"DETAIL_URL" => "",
					"AJAX_MODE" => "N",
					"AJAX_OPTION_JUMP" => "N",
					"AJAX_OPTION_STYLE" => "Y",
					"AJAX_OPTION_HISTORY" => "N",
					"CACHE_TYPE" =>$arParams["CACHE_TYPE"],
					"CACHE_TIME" => $arParams["CACHE_TIME"],
					"CACHE_FILTER" => "Y",
					"CACHE_GROUPS" => "N",
					"PREVIEW_TRUNCATE_LEN" => "",
					"ACTIVE_DATE_FORMAT" => "j F Y",
					"SET_TITLE" => "N",
					"SET_STATUS_404" => "N",
					"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
					"ADD_SECTIONS_CHAIN" => "N",
					"HIDE_LINK_WHEN_NO_DETAIL" => "N",
					"PARENT_SECTION" => "",
					"PARENT_SECTION_CODE" => "",
					"INCLUDE_SUBSECTIONS" => "Y",
					"PAGER_TEMPLATE" => "",
					"DISPLAY_TOP_PAGER" => "N",
					"DISPLAY_BOTTOM_PAGER" => "N",
					"PAGER_TITLE" => "",
					"PAGER_SHOW_ALWAYS" => "N",
					"PAGER_DESC_NUMBERING" => "N",
					"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
					"PAGER_SHOW_ALL" => "N",
					"AJAX_OPTION_ADDITIONAL" => "",
					"COMPONENT_TEMPLATE" => "next",
					"SET_BROWSER_TITLE" => "N",
					"SET_META_KEYWORDS" => "N",
					"SET_META_DESCRIPTION" => "N",
					"SET_LAST_MODIFIED" => "N",
					"PAGER_BASE_LINK_ENABLE" => "N",
					"SHOW_404" => "N",
					"MESSAGE_404" => ""
				),
				false, array("HIDE_ICONS" => "Y")
			);?>
		<?endif;?>
		<?$APPLICATION->ShowViewContent('sotbit_seometa_add_desc');?>
	</div>
<?endif;?>



<?$isAjax="N";?>
<?if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest"  && isset($_GET["ajax_get"]) && $_GET["ajax_get"] == "Y" || (isset($_GET["ajax_basket"]) && $_GET["ajax_basket"]=="Y")){
	$isAjax="Y";
}?>
<?if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest" && isset($_GET["ajax_get_filter"]) && $_GET["ajax_get_filter"] == "Y" ){
	$isAjaxFilter="Y";
}
if($isAjaxFilter == "Y")
	$isAjax="N";?>
<?$section_pos_top = \Bitrix\Main\Config\Option::get("aspro.next", "TOP_SECTION_DESCRIPTION_POSITION", "UF_SECTION_DESCR", SITE_ID );?>
<?$section_pos_bottom = \Bitrix\Main\Config\Option::get("aspro.next", "BOTTOM_SECTION_DESCRIPTION_POSITION", "DESCRIPTION", SITE_ID );?>
<?if($itemsCnt):?>
	

	<?// sliceheight for ajax mode
	/*?>
	<?if($arParams['AJAX_MODE'] == 'Y' && strpos($_SERVER['REQUEST_URI'], 'bxajaxid') !== false):?>
		<script type="text/javascript">
			setTimeout(function(){
				$('.ajax_load .catalog_block .catalog_item_wrapp .catalog_item .item-title').sliceHeight({resize: false});
				$('.ajax_load .catalog_block .catalog_item_wrapp .catalog_item .cost').sliceHeight({resize: false});
				$('.ajax_load .catalog_block .catalog_item_wrapp .item_info').sliceHeight({resize: false});
				$('.ajax_load .catalog_block .catalog_item_wrapp').sliceHeight({classNull: '.footer_button', resize: false});
			}, 100);
			setStatusButton();
		</script>
	<?endif;
	*/?>

	<?// filer?>
	<?if($arTheme["FILTER_VIEW"]["VALUE"] === "VERTICAL"):?>
		<?//add filter with ajax?>
		<?if($arParams['AJAX_MODE'] == 'Y' && strpos($_SERVER['REQUEST_URI'], 'bxajaxid') !== false):?>
			<div class="filter_tmp swipeignore">
				<?include_once(__DIR__."/../filter.php")?>
			</div>
			<script type="text/javascript">
				if(typeof window['trackBarOptions'] !== 'undefined'){
					window['trackBarValues'] = {}
					for(key in window['trackBarOptions']){
						window['trackBarValues'][key] = {
							'leftPercent': window['trackBar' + key].leftPercent,
							'leftValue': window['trackBar' + key].minInput.value,
							'rightPercent': window['trackBar' + key].rightPercent,
							'rightValue': window['trackBar' + key].maxInput.value,
						}
					}
				}

				if($('.filter_wrapper_ajax').length)
					$('.filter_wrapper_ajax').remove();
				var filter_node = $('.left_block .bx_filter.bx_filter_vertical'),
					new_filter_node = $('<div class="filter_wrapper_ajax"></div>'),
					left_block_node = $('#content .left_block');
				if(!filter_node.length)
				{
					if(left_block_node.find('.menu_top_block').length)
						new_filter_node.insertAfter(left_block_node.find('.menu_top_block'));
				}
				else
				{
					new_filter_node.insertBefore(filter_node);
					filter_node.remove();
				}
				$('.filter_tmp').appendTo($('.filter_wrapper_ajax'));

				if(typeof window['trackBarOptions'] !== 'undefined'){
					for(key in window['trackBarOptions']){
						window['trackBarOptions'][key].leftPercent = window['trackBarValues'][key].leftPercent;
						window['trackBarOptions'][key].rightPercent = window['trackBarValues'][key].rightPercent;
						window['trackBarOptions'][key].curMinPrice = window['trackBarValues'][key].leftValue;
						window['trackBarOptions'][key].curMaxPrice = window['trackBarValues'][key].rightValue;
						window['trackBar' + key] = new BX.Iblock.SmartFilter(window['trackBarOptions'][key]);
						window['trackBar' + key].minInput.value = window['trackBarValues'][key].leftValue;
						window['trackBar' + key].maxInput.value = window['trackBarValues'][key].rightValue;
					}
				}

			</script>
		<?endif;?>
		<?ob_start();?>
			<?include_once(__DIR__."/../filter.php")?>
			<script>
				$('#content > .wrapper_inner > .left_block').addClass('filter_ajax filter_visible');
			</script>
		<?$html = ob_get_clean();?>
		<?$APPLICATION->AddViewContent('left_menu', $html);?>
	<?endif;?>
	<?if(isset($arParams['LANDING_POSITION']) && $arParams['LANDING_POSITION'] === 'BEFORE_PRODUCTS'):?>
	    <div class="<?=($arParams["LANDING_TYPE_VIEW"] ? $arParams["LANDING_TYPE_VIEW"] : "landing_1" );?>" >
		    <?@include_once(($arParams["LANDING_TYPE_VIEW"] ? $arParams["LANDING_TYPE_VIEW"] : "landing_1").'.php');?>
	    </div>
	<?endif;?>
	
	
	<div class="right_block1 clearfix catalog <?=strtolower($arTheme["FILTER_VIEW"]["VALUE"]);?>" id="right_block_ajax">
		<?if($arTheme["FILTER_VIEW"]["VALUE"] === "HORIZONTAL" || $arTheme["FILTER_VIEW"]["VALUE"] === "COMPACT"){?>
			<div class="<?=($arTheme["FILTER_VIEW"]["VALUE"]=="HORIZONTAL" ? 'filter_horizontal' : '');?><?=($arTheme["FILTER_VIEW"]["VALUE"]=="COMPACT" ? ' filter_compact' : '');?> swipeignore">
				<?include_once(__DIR__."/../filter.php")?>
			</div>
		<?}/*else{?>
			<div class="js_filter filter_horizontal">
				<?if($isAjaxFilter == "Y"):?>
					<?include(__DIR__."/../filter.php")?>
				<?else:?>
					<div class="bx_filter bx_filter_vertical"></div>
				<?endif;?>
			</div>
		<?}*/?>
		<div class="inner_wrapper">
		
	
<?




?>
	
	

	
	
	
	<?
	//sort
	ob_start();
	include_once(__DIR__."/../sort.php");
	$sortHtml = ob_get_clean();
	$listElementsTemplate = $template;
	?>
	
<?endif;?>



     <?
            function isMobile()
            {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
                return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent)
                    || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4));
            }
            ?>

 


<?// ВЕРХНИЙ СЕО; ?>
<? if (!$arSeoItem): ?>
            <? if ($arParams["SHOW_SECTION_DESC"] != 'N' && strpos($_SERVER['REQUEST_URI'], 'PAGEN') === false): ?>
                <? if ($posSectionDescr == "BOTH"): ?>
                    <? if ($arSection[$section_pos_top]): ?>
                        <div class="group_description_block top">
                            <div><?= $arSection[$section_pos_top] ?></div>
                        </div>
                    <? endif; ?>
					<? elseif ($posSectionDescr == "TOP"): ?>
                    <? if ($arSection[$arParams["SECTION_PREVIEW_PROPERTY"]]): ?>
                        <div class="group_description_block top">
                            <div><?= $arSection[$arParams["SECTION_PREVIEW_PROPERTY"]] ?></div>
                        </div>
                    <? elseif ($arSection["DESCRIPTION"]): ?>
                        <div class="group_description_block top">
                            <div><?= $arSection["DESCRIPTION"] ?></div>
                        </div>
                    <? elseif ($arSection["UF_SECTION_DESCR"]): ?>
                        <div class="group_description_block top">
                            <div><?= $arSection["UF_SECTION_DESCR"] ?></div>
                        </div>
                    <? endif; ?>
                <? endif; ?>
            <? endif; ?>
			<?
//            global $USER;
//            if($USER->IsAdmin())echo "<pre>"; print_r($section); echo "</pre>";
            ?>
<!--			--><?//echo "<pre>"; print_r($regionID); echo "</pre>";?>
<!--			        --><?//echo "<pre>"; var_dump(!IsSeoDisrupting($arParams)); echo "</pre>";?>
			<? if (!substr_count($_SERVER['REQUEST_URI'], '/filter/')) : ?>
					<? if (!IsSeoDisrupting($arParams)): ?>
					
					<?//блок с акциями?>
										<? include_once(__DIR__ . "/../include/km_akciya.php") ?>
					<?//блок с акциями?>
	
	
					<?//КМ между H1 и подразделами?>
						<? include_once(__DIR__ . "/../include/km_mezhduh1_i_podrazdelami.php") ?>
					<?//КМ между H1 и подразделами?>
						<?// ПОДСКАЗКА на всех городах + Москва (кроме Краснодар, Белгроод, Воронеж, Ростов); ?>
					<?switch ($regionID) {				
					case 9277 : case 9278 : case 9568 : case 22018 ?>					
					<? break;
					default:?>
				
					<?if($arSection["UF_YANDEX_HINT"]):?>
					<?/*div id="hint_popup" title="<?= $arSection["UF_YANDEX_HINT"] ?>"></div*/?>
				
					<!-- Пример с иконкой вопроса -->
					<div class="tooltip-question">!
					  <div class="tooltip-content">
					   <?= $arSection["UF_YANDEX_HINT"] ?>
					  </div>
					</div>
					<?endif;?>			
					<? break;					
					}
					?>
					<?// ПОДСКАЗКА на всех городах + Москва (кроме Краснодар, Белгроод, Воронеж, Ростов); ?>

			<? endif; ?>



			<? endif; ?>


<? else: ?>

			<? if  (strpos($_SERVER['REQUEST_URI'], 'PAGEN') === false):?>
										<?/*SEO текст для посадочных страниц каталога по регионам ВЕРХ*/?>
										<? include_once(__DIR__ . "/../include/seo_dlya_posadochnyh_stranic_verx.php") ?>
										<?/*SEO текст для посадочных страниц каталога по регионам ВЕРХ*/?>
				<? else: ?>
				
				<?$APPLICATION->AddHeadString('<link href="https://'.$_SERVER['SERVER_NAME'].$arSeoItem['PROPERTY_FILTER_URL_VALUE'].'" rel="canonical" />',true);?>
			<? endif; ?>


<? endif; ?>


<?// ВЕРХНИЙ СЕО; ?>


<? $category_code == $section['ID'] ;?>
<?	/*GLOBALS['arSectionDopRazdel'] = array('UF_PLACE_SECTION' => '0');*/?>
<?/*
if($arSection["PLACE"]){
		$place = $arSection["PLACE"];
	}
	else{
		$place = $arParams["DEFAULT_PLACE_TEMPLATE"];
	}
*/?>	
<?/*20 - где выводить раздел top - сверху*, '' - не установлено*/?>
<?$GLOBALS['arSectionDopRazdel'] = array('UF_PLACE_SECT' => array('20',''));?>
	 
<? if (!substr_count($_SERVER['REQUEST_URI'], '/filter/')) : ?>
	   <? if ($iSectionsCount): ?>			
            <div class="section_block">
			 <? if (!IsSeoDisrupting($arParams)): ?>

                <? $APPLICATION->IncludeComponent(
                    "bitrix:catalog.section.list",
                    "subsections_list_dop_razd",
                    array(
                        "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
                        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                        "SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
                        "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
                        "DISPLAY_PANEL" => $arParams["DISPLAY_PANEL"],
						"FILTER_NAME" => "arSectionDopRazdel",
                        "CACHE_TYPE" => "A",
                        "CACHE_TIME" => "172800",
                        "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                        "SECTION_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["section"],
                        "COUNT_ELEMENTS" => "N",
                        "ADD_SECTIONS_CHAIN" => ((!$iSectionsCount || $arParams['INCLUDE_SUBSECTIONS'] !== "N") ? 'N' : 'Y'),
                        "SHOW_SECTION_LIST_PICTURES" => $arParams["SHOW_SECTION_PICTURES"],
                        "TOP_DEPTH" => "1",
                    ),
                    $component
                ); ?>
				
               <? endif; ?>
			<?//КМ между подразделами и элементами?>
			<? include_once(__DIR__ . "/../include/km_mezhdu_podrazdelami_i_elementami.php") ?>
			<?//КМ между подразделами и элементами?>
			 </div>
			 
			 
		<? endif; ?>
 <? endif; ?>
 
 
<?
$res = CIBlockSection::GetByID($arSection['ID']);
$ar_res = $res->GetNext();
?>
<? //echo $ar_res['SECTION_PAGE_URL']; ?>

<?if (($_SERVER['REQUEST_URI'] !== $ar_res['SECTION_PAGE_URL']) && (!$arSeoItem)):?>
 <? //$APPLICATION->SetPageProperty("robots", "noindex, nofollow");  ?>
<?endif;?>

 
 
<? /*БЛОК */ ?>
<? if (!$arSeoItem): ?>
	        
			<? if (substr_count($_SERVER['REQUEST_URI'], '/filter/')) : ?>
				<? if ((isset($_GET['PAGEN_1']) && !empty($_GET['PAGEN_1']))) :?>
				   <? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
				<?else:?>
					<? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
				<? endif; ?>
            <? endif; ?>
			
       

<? /*Популярные категории*/ ?>
            <? if ($arSeoItems): ?>
                <? $arLandingFilter = array();
                if ($arSeoItem) {
                    $arLandingFilter = array("PROPERTY_SECTION" => $arSeoItem["PROPERTY_SECTION_VALUE"], "!ID" => $arSeoItem["ID"]);
                } else {
                    $arLandingFilter = array("PROPERTY_SECTION" => $arSection["ID"], array("!SORT" => 1000));
                }
                ?>			
				
                <? $GLOBALS["arLandingSections"] = $arLandingFilter; ?>
               
		<?//КМ верхние теги?>
			<? include_once(__DIR__ . "/../include/km_top_tag.php") ?>
			<?//КМ верхние теги?>
			
			
				
				<?//UF_COMMENT_PRICE (Комментарий о цене) - вывод пользовательского свойства раздела / заполнено в Террасной доске / Фасадах / Заборах?>
			<?if($arSection["UF_COMMENT_PRICE"]):?>
			 <div class="uf_comment_price"><?= $arSection["UF_COMMENT_PRICE"] ?></div>
			<?endif;?>
			<?//UF_COMMENT_PRICE (Комментарий о цене) - вывод пользовательского свойства раздела / заполнено в Террасной доске / Фасадах / Заборах?>
				
            <? endif; ?>

<? /*Популярные категории*/ ?>
<? endif; ?>
<? /*БЛОК */ ?>



 



			
			
			
			

<?if($itemsCnt):?>
			<?if('Y' === $arParams['USE_FILTER']):?>
				<?
			    $matchesFilter = array();
			    if(isset($arParams["SEF_URL_TEMPLATES"]['smart_filter']) && strripos($arParams["SEF_URL_TEMPLATES"]['smart_filter'], "#SMART_FILTER_PATH#")) {
				$isSmartFilter = str_replace("#SMART_FILTER_PATH#", "(.*?)", $arParams["SEF_URL_TEMPLATES"]['smart_filter']);
				$isSmartFilter = preg_replace('/^#[a-zA-Z_]+#/i', "", $isSmartFilter);
				$isSmartFilter = str_replace("/", "\/", $isSmartFilter);
				preg_match("/".$isSmartFilter."/i", $APPLICATION->GetCurPage(), $matchesFilter);
			    }
				?>
				<div class="adaptive_filter">
					<a class="filter_opener<?=($_REQUEST['set_filter'] === 'y' ? ' active num' : '')?><?=(($_REQUEST['set_filter'] === 'y' || (count($matchesFilter)>1 && $matchesFilter[1] != 'clear')) ? ' active num' : '')?>"><i></i><span><?=GetMessage("CATALOG_SMART_FILTER_TITLE")?></span></a>
				</div>
			<?endif;?>

			<?if($isAjax=="N"){
				$frame = new \Bitrix\Main\Page\FrameHelper("viewtype-block");
				$frame->begin();?>
			<?}?>

			<?// sort?>
			<?=$sortHtml;?>

			<?if($isAjax === 'Y'){
				$APPLICATION->RestartBuffer();
			}?>

			<?if($arTheme["FILTER_VIEW"]["VALUE"] == 'VERTICAL' && $isAjax !== 'Y'):?>
				<div id="filter-helper-wrapper">
					<div id="filter-helper" class="top"></div>
				</div>
			<?endif;?>
			
			<?$show = $arParams["PAGE_ELEMENT_COUNT"];?>
			<?if( isMobilelat() ):?>
				   <? $show = "10"; ?>
			<?else:?>  
				   <? $show = $arParams["PAGE_ELEMENT_COUNT"]; ?>
			<?endif;?>
			
			
			<?if($isAjax === 'N'){?>
			

				<div class="ajax_load <?=$display;?>">
			<?}?>
				<?
                if($_SESSION['SMART_FILTER_VAR']) {
                    $SMART_FILTER_FILTER = $GLOBALS[ $_SESSION['SMART_FILTER_VAR'] ];
                }

                if($arResult["VARIABLES"]['SECTION_ID']) {
                    $SMART_FILTER_FILTER['SECTION_ID'] = $arResult["VARIABLES"]['SECTION_ID'];
                } else if($arResult["VARIABLES"]['SECTION_CODE']) {
                    $SMART_FILTER_FILTER['SECTION_CODE'] = $arResult["VARIABLES"]['SECTION_CODE'];
                }

                $arSort = array(
                    $sort => $sort_order,
                    $arParams['ELEMENT_SORT_FIELD2'] => $arParams['ELEMENT_SORT_ORDER2'],
                );
                $SMART_FILTER_SORT = $arSort;
                ?>
								
								





<?//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/00500.txt', print_r($values_seo, 1));?>
<div  itemscope itemtype="https://schema.org/OfferCatalog">
								
								
				
								
								<?
                $APPLICATION->IncludeComponent(
					"bitrix:catalog.section",
					$listElementsTemplate,
					Array(
						"USE_REGION" => ($arRegion ? "Y" : "N"),
						"STORES" => $arParams['STORES'],
						"SHOW_UNABLE_SKU_PROPS"=>$arParams["SHOW_UNABLE_SKU_PROPS"],
						"ALT_TITLE_GET" => $arParams["ALT_TITLE_GET"],
						"SEF_URL_TEMPLATES" => $arParams["SEF_URL_TEMPLATES"],
						"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
						"IBLOCK_ID" => $arParams["IBLOCK_ID"],
						"SHOW_COUNTER_LIST" => $arParams["SHOW_COUNTER_LIST"],
						"SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
						"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
						"AJAX_REQUEST" => $isAjax,
						"ELEMENT_SORT_FIELD" => $sort,
						"ELEMENT_SORT_ORDER" => $sort_order,
						"SHOW_DISCOUNT_TIME_EACH_SKU" => $arParams["SHOW_DISCOUNT_TIME_EACH_SKU"],
						"ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
						"ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],
						"FILTER_NAME" => $arParams["FILTER_NAME"],
						"INCLUDE_SUBSECTIONS" => $arParams["INCLUDE_SUBSECTIONS"],
						"PAGE_ELEMENT_COUNT" => $show,
						"LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
						"DISPLAY_TYPE" => $display,
						"TYPE_SKU" => ($typeSKU ? $typeSKU : $arTheme["TYPE_SKU"]["VALUE"]),
						"SET_SKU_TITLE" => ((($typeSKU == "TYPE_1" || $arTheme["TYPE_SKU"]["VALUE"] == "TYPE_1") && $arTheme["CHANGE_TITLE_ITEM"]["VALUE"] == "Y") ? "Y" : ""),
						"PROPERTY_CODE" => $arParams["LIST_PROPERTY_CODE"],
						"SHOW_ARTICLE_SKU" => $arParams["SHOW_ARTICLE_SKU"],
						"SHOW_MEASURE_WITH_RATIO" => $arParams["SHOW_MEASURE_WITH_RATIO"],
						"OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
						"OFFERS_PROPERTY_CODE" => $arParams["LIST_OFFERS_PROPERTY_CODE"],
						"OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
						"OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
						"OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
						"OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
						'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
						'OFFER_SHOW_PREVIEW_PICTURE_PROPS' => $arParams['OFFER_SHOW_PREVIEW_PICTURE_PROPS'],
						"OFFERS_LIMIT" => $arParams["LIST_OFFERS_LIMIT"],
						"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
						"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
						"BASKET_URL" => $arParams["BASKET_URL"],
						"ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
						"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
						"PRODUCT_QUANTITY_VARIABLE" => "quantity",
						"PRODUCT_PROPS_VARIABLE" => "prop",
						"SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
						"SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
						"AJAX_MODE" => $arParams["AJAX_MODE"],
						"AJAX_OPTION_JUMP" => $arParams["AJAX_OPTION_JUMP"],
						"AJAX_OPTION_STYLE" => $arParams["AJAX_OPTION_STYLE"],
						"AJAX_OPTION_HISTORY" => $arParams["AJAX_OPTION_HISTORY"],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
						"CACHE_FILTER" => "Y",
						"META_KEYWORDS" => $arParams["LIST_META_KEYWORDS"],
						"META_DESCRIPTION" => $arParams["LIST_META_DESCRIPTION"],
						"BROWSER_TITLE" => $arParams["LIST_BROWSER_TITLE"],
						"ADD_SECTIONS_CHAIN" => $iSectionsCount ? 'N' : $arParams["ADD_SECTIONS_CHAIN"],
						"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
						'HIDE_NOT_AVAILABLE_OFFERS' => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
						"DISPLAY_COMPARE" => $arParams["USE_COMPARE"],
						"SET_TITLE" => $arParams["SET_TITLE"],
						"SET_STATUS_404" => $arParams["SET_STATUS_404"],
						"SHOW_404" => $arParams["SHOW_404"],
						"MESSAGE_404" => $arParams["MESSAGE_404"],
						"FILE_404" => $arParams["FILE_404"],
						"PRICE_CODE" => $arParams['PRICE_CODE'],
						"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
						"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
						"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
						"USE_PRODUCT_QUANTITY" => $arParams["USE_PRODUCT_QUANTITY"],
						"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
						"DISPLAY_TOP_PAGER" => $arParams["DISPLAY_TOP_PAGER"],
						"DISPLAY_BOTTOM_PAGER" => $arParams["DISPLAY_BOTTOM_PAGER"],
						"PAGER_TITLE" => $arParams["PAGER_TITLE"],
						"PAGER_SHOW_ALWAYS" => $arParams["PAGER_SHOW_ALWAYS"],
						"PAGER_TEMPLATE" => $arParams["PAGER_TEMPLATE"],
						"PAGER_DESC_NUMBERING" => $arParams["PAGER_DESC_NUMBERING"],
						"PAGER_DESC_NUMBERING_CACHE_TIME" => $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"],
						"PAGER_SHOW_ALL" => $arParams["PAGER_SHOW_ALL"],
						"AJAX_OPTION_ADDITIONAL" => "",
						"ADD_CHAIN_ITEM" => "N",
						"SHOW_QUANTITY" => $arParams["SHOW_QUANTITY"],
						"SHOW_QUANTITY_COUNT" => $arParams["SHOW_QUANTITY_COUNT"],
						"SHOW_DISCOUNT_PERCENT_NUMBER" => $arParams["SHOW_DISCOUNT_PERCENT_NUMBER"],
						"SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
						"SHOW_DISCOUNT_TIME" => $arParams["SHOW_DISCOUNT_TIME"],
						"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
						"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
						"CURRENCY_ID" => $arParams["CURRENCY_ID"],
						"USE_STORE" => $arParams["USE_STORE"],
						"MAX_AMOUNT" => $arParams["MAX_AMOUNT"],
						"MIN_AMOUNT" => $arParams["MIN_AMOUNT"],
						"USE_MIN_AMOUNT" => $arParams["USE_MIN_AMOUNT"],
						"USE_ONLY_MAX_AMOUNT" => $arParams["USE_ONLY_MAX_AMOUNT"],
						"DISPLAY_WISH_BUTTONS" => $arParams["DISPLAY_WISH_BUTTONS"],
						"LIST_DISPLAY_POPUP_IMAGE" => $arParams["LIST_DISPLAY_POPUP_IMAGE"],
						"DEFAULT_COUNT" => $arParams["DEFAULT_COUNT"],
						"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
						"SHOW_HINTS" => $arParams["SHOW_HINTS"],
						"OFFER_HIDE_NAME_PROPS" => $arParams["OFFER_HIDE_NAME_PROPS"],
						"SHOW_SECTIONS_LIST_PREVIEW" => $arParams["SHOW_SECTIONS_LIST_PREVIEW"],
						"SECTIONS_LIST_PREVIEW_PROPERTY" => $arParams["SECTIONS_LIST_PREVIEW_PROPERTY"],
						"SHOW_SECTION_LIST_PICTURES" => $arParams["SHOW_SECTION_LIST_PICTURES"],
						"USE_MAIN_ELEMENT_SECTION" => $arParams["USE_MAIN_ELEMENT_SECTION"],
						"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
						"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
						"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
						"SALE_STIKER" => $arParams["SALE_STIKER"],
						"STIKERS_PROP" => $arParams["STIKERS_PROP"],
						"SHOW_RATING" => $arParams["SHOW_RATING"],
						"ADD_PICT_PROP" => $arParams["ADD_PICT_PROP"],
						"OFFER_ADD_PICT_PROP" => $arParams["OFFER_ADD_PICT_PROP"],
						"GALLERY_ITEM_SHOW" => $arTheme["GALLERY_ITEM_SHOW"]["VALUE"],
						"MAX_GALLERY_ITEMS" => $arTheme["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["MAX_GALLERY_ITEMS"]["VALUE"],
						"ADD_DETAIL_TO_GALLERY_IN_LIST" => $arTheme["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["ADD_DETAIL_TO_GALLERY_IN_LIST"]["VALUE"],
						"IBINHERIT_TEMPLATES" => $arSeoItem ? $arIBInheritTemplates : array(),
						"REVIEWS_VIEW" => $arTheme["REVIEWS_VIEW"]["VALUE"] == "EXTENDED",
						"COMPATIBLE_MODE" => "Y",
						'CURRENT_BASE_PAGE' => \Aspro\Next\CacheableUrl::get(),
                        'PAGER_BASE_LINK' => \Aspro\Next\CacheableUrl::get(),
                        'PAGER_BASE_LINK_ENABLE' => 'Y',
						"HIDE_ELEMENT_LIST" => $APPLICATION->GetProperty("HIDE_ELEMENT_LIST"),
						"SEO_DEOPTIMIZING" => $arParams['SEO_DEOPTIMIZING']

					), $component, array("HIDE_ICONS" => $isAjax)
				);?>
					</div>	
				<!--noindex-->
				<script class="smart-filter-filter" data-skip-moving="true">
                        <?if($SMART_FILTER_FILTER) {?>
                            var filter = <?=\Bitrix\Main\Web\Json::encode($SMART_FILTER_FILTER);?>
                        <?}?>
                    </script>

                    <?if($SMART_FILTER_SORT):?>
                        <script class="smart-filter-sort" data-skip-moving="true">
                            var filter = <?=\Bitrix\Main\Web\Json::encode($SMART_FILTER_SORT)?>
                        </script>
                    <?endif;?>
                <!--/noindex-->
			<?if($isAjax !== 'Y'){?>
				</div>
				<?$frame->end();?>
			<?}?>
<?elseif(isset($arParams["ADD_SECTIONS_CHAIN"]) && $arParams["ADD_SECTIONS_CHAIN"] === 'Y'):?>
	<? $APPLICATION->AddChainItem($arSection['NAME']); ?>
<?endif;?>
			<?if($isAjax === 'N'){?>
				<?/*banners*/?>
					<?
					$linkedBannersIblock = CNextCache::$arIBlocks[SITE_ID]["aspro_next_adv"]["aspro_next_banners_catalog"][0];
					$filterName = "LITE_FILTER_LINKED_BANNERS";
					$GLOBALS[$filterName] = array(
						array(
							'LOGIC' => 'OR',
							// array( "ID" => $linkedBanners ),
							array( "PROPERTY_LINK_GOODS_SECTIONS" => $section['ID'] ),
						),
					);

					if($sectionParent) {
						$GLOBALS[$filterName][0][] = array( "PROPERTY_LINK_GOODS_SECTIONS" => $sectionParent['ID'] );
					}
					if($sectionRoot) {
						$GLOBALS[$filterName][0][] = array( "PROPERTY_LINK_GOODS_SECTIONS" => $sectionRoot['ID'] );
					}

					if ($arParams["FILTER_NAME"] && $arParams["FILTER_NAME"] == "arRegionLink" && $arRegion) {
						$GLOBALS[$filterName]["PROPERTY_LINK_REGION"] = $arRegion['ID'];
					}
					$bannersCount = CNextCache::CIblockElement_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($linkedBannersIblock))), $GLOBALS[$filterName], array());
					if ($bannersCount):?>
						<?\Aspro\Next\Functions\Extensions::init('slider_in_section');?>
						<?\Aspro\Functions\CAsproNext::showBlockHtml([
							'FILE' => '/catalog/banners_in_list.php',
							'PARAMS' => [
								'IBLOCK_ID' => $linkedBannersIblock,
								'FILTER_NAME' => $filterName,
							],
						])?>
					<?endif;?>
				<?/**/?>
				<?/*blog*/?>
				<?$blogIblockID = CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_articles"][0];?>
				<?if($blogIblockID && $linkedArticles):?>
					<?
                    $filterNameLinkedBlog = "MAX_FILTER_LINKED_BLOG";
                    $GLOBALS[$filterNameLinkedBlog] = array(
                        array(
                            'LOGIC' => 'OR',
                            array( "ID" => $linkedArticles ),
                            array( "PROPERTY_LINK_GOODS_SECTIONS" => $section['ID'] ),
                        ),
                    );

                    if($sectionParent) {
                        $GLOBALS[$filterNameLinkedBlog][0][] = array( "PROPERTY_LINK_GOODS_SECTIONS" => $sectionParent['ID'] );
                    }
                    if($sectionRoot) {
                        $GLOBALS[$filterNameLinkedBlog][0][] = array( "PROPERTY_LINK_GOODS_SECTIONS" => $sectionRoot['ID'] );
                    }

                    if ($arParams["FILTER_NAME"] && $arParams["FILTER_NAME"] == "arRegionLink" && $arRegion) {
                        $GLOBALS[$filterNameLinkedBlog]["PROPERTY_LINK_REGION"] = $arRegion['ID'];
                    }
                    $blogsCount = CNextCache::CIblockElement_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($blogIblockID))), $GLOBALS[$filterNameLinkedBlog], array());
					if($blogsCount):?>
						<?\Aspro\Next\Functions\Extensions::init('slider_in_section');?>
						<?\Aspro\Functions\CAsproNext::showBlockHtml([
							'FILE' => '/catalog/blog_in_list.php',
							'PARAMS' => [
								'IBLOCK_ID' => $blogIblockID,
								'FILTER_NAME' => $filterNameLinkedBlog,
							],
						])?>
					<?endif;?>
				<?endif?>
				<?/**/?>
				
				<?/*
if($arSection["PLACE"]){
		$place = $arSection["PLACE"];
	}
	else{
		$place = $arParams["DEFAULT_PLACE_TEMPLATE"];
	}
*/?>	

<?//echo printPre($arSection);?>
<?//echo printPre($section);?>
<?/*21 - где выводить раздел bottom - внизу*/?>
<?$GLOBALS['arSectionBottomPlace'] = array('UF_PLACE_SECT' => '21');?>
	 
            <div class="section_block">
					 <? if (!IsSeoDisrupting($arParams)): ?>

						<? $APPLICATION->IncludeComponent(
							"bitrix:catalog.section.list",
							"subsections_list_dop_razd_bottom",
							array(
								"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
								"IBLOCK_ID" => $arParams["IBLOCK_ID"],
								"SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
								"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
								"FILTER_NAME" => "arSectionBottomPlace",
								"DISPLAY_PANEL" => $arParams["DISPLAY_PANEL"],
								
								"SECTION_USER_FIELDS" => array(    // Свойства разделов
									0 => "UF_PLACE_SECTION", //Передаем любые пользовательские поля
									1 => "UF_PLACE_SECT",
									2 => "",
								),
								"CACHE_TYPE" => "A",
								"CACHE_TIME" => "172800",
								"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
								"SECTION_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["section"],
								"COUNT_ELEMENTS" => "N",
								"ADD_SECTIONS_CHAIN" => ((!$iSectionsCount || $arParams['INCLUDE_SUBSECTIONS'] !== "N") ? 'N' : 'Y'),
								"SHOW_SECTION_LIST_PICTURES" => $arParams["SHOW_SECTION_PICTURES"],
								"TOP_DEPTH" => "1",
							),
							$component
						); ?>
					   <? endif; ?>
			   </div>
			   
		<? if (substr_count($_SERVER['REQUEST_URI'], '/filter/')) : ?>
            <button class="bx_filter_search_reset btn white" type="reset" id="del_filter" name="del_filter"  data-href="">Смотреть полный каталог</button>
			
				<? if  (strpos($_SERVER['REQUEST_URI'], 'PAGEN') === false):?>
										<?/*SEO текст для посадочных страниц каталога по регионам НИЗ*/?>
							<? include_once(__DIR__ . "/../include/seo_dlya_posadochnyh_stranic_niz.php") ?>
							<?/*SEO текст для посадочных страниц каталога по регионам НИЗ*/?>
				<? else: ?>
	<? endif; ?>


		<? endif; ?>
		
<? if (!IsSeoDisrupting($arParams)): ?>				
				
				
				<?if(!$arSeoItem):?>
				 <?/*
                    $APPLICATION->IncludeComponent("bitrix:main.include", ".default",
                        array(
                            "COMPONENT_TEMPLATE" => ".default",
                            "PATH" => "/include/left_block/comp_news.php",
                            "AREA_FILE_SHOW" => "file",
                            "AREA_FILE_SUFFIX" => "",
                            "AREA_FILE_RECURSIVE" => "Y",
                            "EDIT_TEMPLATE" => "include_area.php"
                        ),
                        false
                    ); */?>
					<?if($arParams["SHOW_SECTION_DESC"] != 'N' && strpos($_SERVER['REQUEST_URI'], 'PAGEN') === false):?>
						<?ob_start();?>
						<?if($posSectionDescr === "BOTH"):?>
							<?if($arSection[$section_pos_bottom]):?>
								<div class="group_description_block bottom">
									<div><?=$arSection[$section_pos_bottom]?></div>
								</div>
							<?endif;?>
						<?elseif($posSectionDescr === "BOTTOM"):?>
							<?if($arSection[$arParams["SECTION_PREVIEW_PROPERTY"]]):?>
								<div class="group_description_block bottom">
									<div><?=$arSection[$arParams["SECTION_PREVIEW_PROPERTY"]]?></div>
								</div>
							<?elseif ($arSection["DESCRIPTION"]):?>
								<div class="group_description_block bottom">
									<div><?=$arSection["DESCRIPTION"]?></div>
								</div>
							<?elseif($arSection["UF_SECTION_DESCR"]):?>
								<div class="group_description_block bottom">
									<div><?=$arSection["UF_SECTION_DESCR"]?></div>
								</div>
							<?endif;?>
							
							
			<?/*Популярные категории типа Полимерная террасная доска ниже каталога*/?>
		<? if (!substr_count($_SERVER['REQUEST_URI'], '/filter/')) : ?>
                   
				<? if ($arSeoItems): ?>
								<? $arLandingFilter = array();
								if ($arSeoItem) {
									$arLandingFilter = array("PROPERTY_SECTION" => $arSeoItem["PROPERTY_SECTION_VALUE"], "!ID" => $arSeoItem["ID"]);
								} else {
									$arLandingFilter = array("PROPERTY_SECTION" => $arSection["ID"], array("SORT" => 1000));
								}
								?>			
								
								<? $GLOBALS["arLandingSections"] = $arLandingFilter; ?>
								<? $APPLICATION->IncludeComponent(
									"bitrix:news.list",
									isMobile() ? "landings_list_mobile_also" : "landings_list_also",
									array(
										"IBLOCK_TYPE" => "aspro_next_catalog",
										"IBLOCK_ID" => CNextCache::$arIBlocks[SITE_ID]["aspro_next_catalog"]["aspro_next_catalog_info"][0],
										"NEWS_COUNT" => "999",
										"SHOW_COUNT" => $arParams["LANDING_SECTION_COUNT"],
										"COMPARE_FIELD" => "FILTER_URL",
										"COMPARE_PROP" => "Y",
										"SORT_BY1" => "SORT",
										"SORT_ORDER1" => "ASC",
										"SORT_BY2" => "ID",
										"SORT_ORDER2" => "DESC",
										"FILTER_NAME" => "arLandingSections",
										"FIELD_CODE" => array(
											0 => "SORT",
											1 => "NAME",
										),
										"PROPERTY_CODE" => array(
											0 => "LINK",
											1 => "",
										),
										"CHECK_DATES" => "Y",
										"DETAIL_URL" => "",
										"AJAX_MODE" => "N",
										"AJAX_OPTION_JUMP" => "N",
										"AJAX_OPTION_STYLE" => "Y",
										"AJAX_OPTION_HISTORY" => "N",
										"CACHE_TYPE" => "A",
										"CACHE_TIME" => "172800",
										"CACHE_FILTER" => "Y",
										"CACHE_GROUPS" => "N",
										"PREVIEW_TRUNCATE_LEN" => "",
										"ACTIVE_DATE_FORMAT" => "j F Y",
										"SET_TITLE" => "N",
										"SET_STATUS_404" => "N",
										"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
										"ADD_SECTIONS_CHAIN" => "N",
										"HIDE_LINK_WHEN_NO_DETAIL" => "N",
										"PARENT_SECTION" => "",
										"PARENT_SECTION_CODE" => "",
										"INCLUDE_SUBSECTIONS" => "Y",
										"PAGER_TEMPLATE" => "",
										"DISPLAY_TOP_PAGER" => "N",
										"DISPLAY_BOTTOM_PAGER" => "N",
										"PAGER_TITLE" => "",
										"PAGER_SHOW_ALWAYS" => "N",
										"PAGER_DESC_NUMBERING" => "N",
										"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
										"PAGER_SHOW_ALL" => "N",
										"AJAX_OPTION_ADDITIONAL" => "",
										"COMPONENT_TEMPLATE" => "next",
										"SET_BROWSER_TITLE" => "N",
										"SET_META_KEYWORDS" => "N",
										"SET_META_DESCRIPTION" => "N",
										"SET_LAST_MODIFIED" => "N",
										"PAGER_BASE_LINK_ENABLE" => "N",
										"TITLE_BLOCK" => $arParams["LANDING_TITLE"],
										"SHOW_404" => "N",
										"MESSAGE_404" => ""
									),
									false, array("HIDE_ICONS" => "Y")
								); ?>
            <? endif; ?>
					
				<?/*Популярные категории типа Полимерная террасная доска ниже каталога*/?>
				

									<?//КМ нижние теги?>
									<? include_once(__DIR__ . "/../include/km_bottom_tag.php") ?>
									<?//КМ нижние теги?>

										
									<?//КМ после товаров?>
									<? include_once(__DIR__ . "/../include/km_posle_tovarov.php") ?>
									<?//КМ после товаров?>
					
				
					
											
				

 <? endif; ?>
			


			
						<?endif;?>
						<?
						$html = ob_get_clean();
						$APPLICATION->AddViewContent('bottom_desc', $html);
						$APPLICATION->ShowViewContent('bottom_desc');
						$APPLICATION->ShowViewContent('sotbit_seometa_bottom_desc');
						$APPLICATION->ShowViewContent('sotbit_seometa_add_desc');
						?>
					<?endif;?>
				<?else:?>
					<?ob_start();?>
					<?if($arSeoItem["DETAIL_TEXT"]):?>
						<?=$arSeoItem["DETAIL_TEXT"];?>
					<?endif;?>
					<?
					$html = ob_get_clean();
					$APPLICATION->AddViewContent('bottom_desc', $html);
					$APPLICATION->ShowViewContent('bottom_desc');
					$APPLICATION->ShowViewContent('sotbit_seometa_bottom_desc');
					?>
					
					
					
					
					
				<?endif;?>
				
				
				
				
 
 
 <? else: ?>


                    <? /*Популярные категории*/ ?>
                    <? if ($arSeoItems): ?>
                        <? $arLandingFilter = array();
                        if ($arSeoItem) {
                            $arLandingFilter = array("PROPERTY_SECTION" => $arSeoItem["PROPERTY_SECTION_VALUE"], "!ID" => $arSeoItem["ID"], array("!SORT" => 1000));
                        } else {
                             $arLandingFilter = array("PROPERTY_SECTION" => $arSection["ID"]);
                        }
 
                        ?>
						
						
                    <? $GLOBALS["arLandingSections"] = $arLandingFilter; ?>
					<?if ($arSeoItem["PROPERTY_NOINDEX_VALUE"]):?>
							<? $APPLICATION->SetPageProperty("robots", "noindex, follow"); ?>
						<?else:?> 
					<?endif?>
					<? $APPLICATION->IncludeComponent(
                            "bitrix:news.list",
                            "landings_list",
                            array(
                                "IBLOCK_TYPE" => "aspro_next_catalog",
                                "IBLOCK_ID" => CNextCache::$arIBlocks[SITE_ID]["aspro_next_catalog"]["aspro_next_catalog_info"][0],
                                "NEWS_COUNT" => "999",
                                "SHOW_COUNT" => $arParams["LANDING_SECTION_COUNT"],
                                "COMPARE_FIELD" => "FILTER_URL",
                                "COMPARE_PROP" => "Y",
                                "SORT_BY1" => "SORT",
                                "SORT_ORDER1" => "ASC",
                                "SORT_BY2" => "ID",
                                "SORT_ORDER2" => "DESC",
                                "FILTER_NAME" => "arLandingSections",
                                "FIELD_CODE" => array(
                                    0 => "",
                                    1 => "",
                                ),
                                "PROPERTY_CODE" => array(
                                    0 => "LINK",
									1 => "CPY_FILTER_TAG",
                                    2 => "",
                                ),
                                "CHECK_DATES" => "Y",
                                "DETAIL_URL" => "",
                                "AJAX_MODE" => "N",
                                "AJAX_OPTION_JUMP" => "N",
                                "AJAX_OPTION_STYLE" => "Y",
                                "AJAX_OPTION_HISTORY" => "N",
                                "CACHE_TYPE" => "A",
                                "CACHE_TIME" => "172800",
                                "CACHE_FILTER" => "Y",
                                "CACHE_GROUPS" => "N",
                                "PREVIEW_TRUNCATE_LEN" => "",
                                "ACTIVE_DATE_FORMAT" => "j F Y",
                                "SET_TITLE" => "N",
                                "SET_STATUS_404" => "N",
                                "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
                                "ADD_SECTIONS_CHAIN" => "N",
                                "HIDE_LINK_WHEN_NO_DETAIL" => "N",
                                "PARENT_SECTION" => "",
                                "PARENT_SECTION_CODE" => "",
                                "INCLUDE_SUBSECTIONS" => "Y",
                                "PAGER_TEMPLATE" => "",
                                "DISPLAY_TOP_PAGER" => "N",
                                "DISPLAY_BOTTOM_PAGER" => "N",
                                "PAGER_TITLE" => "",
                                "PAGER_SHOW_ALWAYS" => "N",
                                "PAGER_DESC_NUMBERING" => "N",
                                "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
                                "PAGER_SHOW_ALL" => "N",
                                "AJAX_OPTION_ADDITIONAL" => "",
                                "COMPONENT_TEMPLATE" => "next",
                                "SET_BROWSER_TITLE" => "N",
                                "SET_META_KEYWORDS" => "N",
                                "SET_META_DESCRIPTION" => "N",
                                "SET_LAST_MODIFIED" => "N",
                                "PAGER_BASE_LINK_ENABLE" => "N",
                                "TITLE_BLOCK" => $arParams["LANDING_TITLE"],
                                "SHOW_404" => "N",
                                "MESSAGE_404" => ""
                            ),
                            false, array("HIDE_ICONS" => "Y")
                        ); 
					?>						
                    <? endif; ?>
                    <? /*Популярные категории*/ ?>
					
					
							

                <? endif; ?>
 
 
 
 
 
				<?if(!isset($arParams['LANDING_POSITION']) || $arParams['LANDING_POSITION'] === 'AFTER_PRODUCTS'):?>
					<div class="<?=($arParams["LANDING_TYPE_VIEW"] ? $arParams["LANDING_TYPE_VIEW"] : "landing_1" );?>" >
						<?@include_once(($arParams["LANDING_TYPE_VIEW"] ? $arParams["LANDING_TYPE_VIEW"] : "landing_1" ).'.php');?>
					</div>
				<?endif;?>

				<?if($itemsCnt):?>
					<div class="clear"></div>
					
		
					<?//</div> //.ajax_load?>
				
				
				

				
				
				
				
				
				
				<?endif;?>
			<?}?>


		
				
	
<?//bBitrixAjax?>	
<?
global $arSite, $arTheme;
$postfix = "";
$bBitrixAjax = (strpos($_SERVER["QUERY_STRING"], "bxajaxid") !== false);
if($arTheme["HIDE_SITE_NAME_TITLE"]["VALUE"] == "N" && ($bBitrixAjax || $isAjaxFilter))
{
	$postfix = " - ".$arSite["NAME"];
}
?>
<?if($itemsCnt):?>

			<?if($isAjax == 'Y'){
				die();
				}?>
				
				
				
			<?//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xxx999.txt', print_r($arSection, 1));?>
			
			<?// echo $arSEO["ELEMENT_META_TITLE"]; ?>
<?//var_dump($values_seo['SECTION_META_TITLE']);?>
<?//var_dump($values_seo['SECTION_META_DESCRIPTION']);?>

<?	
	
// Формируем JSON-LD
    $jsonLd = '<script type="application/ld+json">
{
    "@context":"https://schema.org",
    "@type":"QAPage",
    "mainEntity": {
        "@type": "Question",
        "dateCreated": "' . date('c') . '",
        "name": "' . CUtil::JSEscape($values_seo['SECTION_META_TITLE']) . '",
        "text": "' . CUtil::JSEscape($values_seo['SECTION_META_DESCRIPTION']) . '",
        "author": {
            "@type": "Person",
            "name": "Ирина Кулыгина"
        },
        "acceptedAnswer": {
            "@type": "Answer",
            "author": {
                "@type": "Organization",
                "name": "Латитудо"
            },
            "text": "&#9989 Широкий ассортимент &#128293 От производителя &#128077 Доставка"
        },
        "answerCount": 1
    }
}
</script>';
    
    // Добавляем в head
    $APPLICATION->AddHeadString($jsonLd);			
				
		?>		
			
				
		</div>
	</div>
<?if($bBitrixAjax)
	{
		$page_title = $arValues['SECTION_META_TITLE'] ? $arValues['SECTION_META_TITLE'] : $arSection["NAME"];
		if($page_title){
			$APPLICATION->SetPageProperty("title", $page_title.$postfix);
		}
	}
	
	
	
	?>
<?else:?>
	<?if(!$section):?>
		<?\Bitrix\Iblock\Component\Tools::process404(
			trim($arParams["MESSAGE_404"]) ?: GetMessage("T_NEWS_NEWS_NA")
			,true
			,$arParams["SET_STATUS_404"] === "Y"
			,$arParams["SHOW_404"] === "Y"
			,$arParams["FILE_404"]
		);?>
	<?else:?>
		<?if(!$iSectionsCount):?>
			<div class="no_goods">
				<div class="no_products">
							<div class="wrap_text_empty">
									<? if ($_REQUEST["set_filter"]) { ?>
										<? $APPLICATION->IncludeFile(SITE_DIR . "include/section_no_products_filter.php", array(), array("MODE" => "html", "NAME" => GetMessage('EMPTY_CATALOG_DESCR'))); ?>
									<? } else { ?>
										<? $page_razd = ($arSeoItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] != "" ? $arSeoItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] : $arSeoItem["NAME"]); ?>
										<span class="big_text">Раздел "<?= $page_h1 ?>" сейчас наполняется, мы скоро добавим информацию</span>
										<br>
										<span class="middle_text">Свяжитесь с нами и мы предложим вам нужные позиции</span>


									<? } ?>
								</div>
				</div>
			</div>
		<?endif;?>
		
		
		
		
			<?
		$ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($arParams["IBLOCK_ID"], IntVal($arSection["ID"]));
		$arValues = $ipropValues->getValues();
		if($arParams["SET_TITLE"] !== 'N'){
			$page_h1 = $arValues['SECTION_PAGE_TITLE'] ? $arValues['SECTION_PAGE_TITLE'] : $arSection["NAME"];
			if($page_h1){
				$APPLICATION->SetTitle($page_h1);
			}
			else{
				$APPLICATION->SetTitle($arSection["NAME"]);
			}
		}
		$page_title = $arValues['SECTION_META_TITLE'] ? $arValues['SECTION_META_TITLE'] : $arSection["NAME"];
		if($page_title){
			$APPLICATION->SetPageProperty("title", $page_title.$postfix);
		}
		if($arValues['SECTION_META_DESCRIPTION']){
			$APPLICATION->SetPageProperty("description", $arValues['SECTION_META_DESCRIPTION']);
		}
		if($arValues['SECTION_META_KEYWORDS']){
			$APPLICATION->SetPageProperty("keywords", $arValues['SECTION_META_KEYWORDS']);
		}
		
		
						
			

		
	
		?>
		
			
		
	
	<?endif;?>
<?endif;?>

<?//bBitrixAjax?>	

<? if (!IsSeoDisrupting($arParams)): ?>
		
    <? if ($arSeoItem) {
        $langing_seo_h1 = ($arSeoItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] != "" ? $arSeoItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] : $arSeoItem["NAME"]);
        $APPLICATION->SetTitle($langing_seo_h1);
        if ($arSeoItem["IPROPERTY_VALUES"]["ELEMENT_META_TITLE"])
            $APPLICATION->SetPageProperty("title", $arSeoItem["IPROPERTY_VALUES"]["ELEMENT_META_TITLE"]);
        else
            $APPLICATION->SetPageProperty("title", $arSeoItem["NAME"] . $postfix);

        if ($arSeoItem["IPROPERTY_VALUES"]["ELEMENT_META_DESCRIPTION"])
            $APPLICATION->SetPageProperty("description", $arSeoItem["IPROPERTY_VALUES"]["ELEMENT_META_DESCRIPTION"]);

        if ($arSeoItem["IPROPERTY_VALUES"]['ELEMENT_META_KEYWORDS'])
            $APPLICATION->SetPageProperty("keywords", $arSeoItem["IPROPERTY_VALUES"]['ELEMENT_META_KEYWORDS']);
        
	
   
		
		?>
    <? } ?>
	
		
<? endif; ?>




<? if (!substr_count($_SERVER['REQUEST_URI'], '/filter/')) : ?>
            <? if (!IsSeoDisrupting($arParams)): ?>
					<? if ($arSection["UF_SHOW_BLOCK_METRIKA"] == "1"): ?>	
							<script>
							$(window).scroll(function () {
							if ($(window).scrollTop() + $(window).height() > $('.social-area').offset().top) {
							yaCounter62259859.reachGoal('social-area');
							}
							});
							</script>	
							<?else:?>
							 
					 <? endif; ?>

			<? endif; ?>
			
 <? endif; ?>
 
 			<?//Долистывание до блока - Вам есть на что посмотреть - на посадочных страницах каталога?>
				
				<? if($arSeoItem) :?>
				<? if ($arSeoItem["PROPERTY_SHOW_BLOCK_METRIKA_VALUE"] == "Y"): ?>	
				<script>
						$(window).scroll(function () {
						if ($(window).scrollTop() + $(window).height() > $('.social-area').offset().top) {
						yaCounter62259859.reachGoal('social-area');
						}
						});
						</script>	
				<? endif; ?>
				<? endif; ?>
				
				<?//Долистывание до блока - Вам есть на что посмотреть - на посадочных страницах каталога?>


<? if (!substr_count($_SERVER['REQUEST_URI'], '/filter/')) : ?>
            <? if (!IsSeoDisrupting($arParams)): ?>
					<? if ($arSection["UF_SHOW_BLOCK_METRIKA"] == "1"): ?>	
							<script>
							$(window).scroll(function () {
							if ($(window).scrollTop() + $(window).height() > $('.social-area').offset().top) {
							yaCounter62259859.reachGoal('social-area');
							}
							});
							</script>	
							<?else:?>
							 
					 <? endif; ?>

			<? endif; ?>
			
 <? endif; ?>
 


		<? if (IsSeoDisrupting($arParams)):?>

 
	<?  if ((isset($_GET['PAGEN_1']) && !empty($_GET['PAGEN_1']))) :?>
			<?  if($arSeoItem) :?>
			<?  //$langing_seo_h1 = ($arSeoItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] != "" ? $arSeoItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] : $arSeoItem["NAME"]);
			// $langing_seo_h1 = $langing_seo_h1.' - Страница №' . $_GET['PAGEN_1'];
			 $APPLICATION->AddHeadString('<meta name="yandex" content="noindex, follow" />',true);
			 // $APPLICATION->SetTitle($langing_seo_h1);
			//$APPLICATION->SetPageProperty("title", $langing_seo_h1);
			//$APPLICATION->SetPageProperty("description", $langing_seo_h1);

			?>

			<?else:?>
			 <? //$langing_seo_h1 = $arSection["NAME"];
			//$langing_seo_h1 = $langing_seo_h1.' - Страница №' . $_GET['PAGEN_1'];
			$APPLICATION->AddHeadString('<meta name="yandex" content="noindex, follow" />',true);
			// $APPLICATION->SetPageProperty("robots", "noindex, follow"); 
			//$APPLICATION->SetTitle($langing_seo_h1);
			//$APPLICATION->SetPageProperty("title", $langing_seo_h1);
			//$APPLICATION->SetPageProperty("description", $langing_seo_h1);
			
		
	?>
			<? endif; ?>
<? endif; ?>			
		<?endif;?>	



<?if($isAjaxFilter):?>
	<?global $APPLICATION;?>
	<?$arAdditionalData['TITLE'] = htmlspecialcharsback($APPLICATION->GetTitle());
	if($arSeoItem)
	{
		$postfix = '';
	}
	$arAdditionalData['WINDOW_TITLE'] = htmlspecialcharsback($APPLICATION->GetTitle('title').$postfix);?>
	<script type="text/javascript">
		BX.removeCustomEvent("onAjaxSuccessFilter", function tt(e){});
		BX.addCustomEvent("onAjaxSuccessFilter", function tt(e){
			var arAjaxPageData = <?=CUtil::PhpToJSObject($arAdditionalData);?>;
			if (arAjaxPageData.TITLE)
				BX.ajax.UpdatePageTitle(arAjaxPageData.TITLE);
			if (arAjaxPageData.WINDOW_TITLE || arAjaxPageData.TITLE)
				BX.ajax.UpdateWindowTitle(arAjaxPageData.WINDOW_TITLE || arAjaxPageData.TITLE);
		});
	</script>
<?endif;?>
