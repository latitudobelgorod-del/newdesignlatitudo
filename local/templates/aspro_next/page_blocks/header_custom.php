<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
global $arTheme, $arRegion;
$arRegions = CNextRegionality::getRegions();
$regionID = ($arRegion ? $arRegion['ID'] : '');
$arPageParams = $arSection = $section = array();
$url_page = $_SERVER['REQUEST_URI'];
if($arRegion)
	$bPhone = ($arRegion['PHONES'] ? true : false);

else
$bPhone = ((int)$arTheme['HEADER_PHONES'] ? true : false);
$REGION_TAG_PHONE = "#REGION_TAG_PHONE#";
$REGION_TAG_PHONE_PODMENA = "#REGION_TAG_PHONE_PODMENA#";
$REGION_TAG_PHONE_PODP = "#REGION_TAG_PHONEPODP#";
$REGION_TAG_PHONESKLAD = "#REGION_TAG_PHONESKLAD#";
$REGION_TAG_PHONESKLAD_PODP = "#REGION_TAG_PHONESKLAD_PODP#";
$REGION_TAG_LINKVIDEO = "#REGION_TAG_LINKVIDEO#";
$REGION_TAG_MAIL = "#REGION_TAG_MAIL#";

$logoClass = ($arTheme['COLORED_LOGO']['VALUE'] !== 'Y' ? '' : ' colored');
?>


<?=bitrix_sessid_post();?>
<?
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
if ($val=='utm_medium')
	$utm_medium =$v;
}
?>
<div class="top-block top-block-v1">
	<div class="maxwidth-theme">
			<div class="wrapp_block">
			<div class="row">
				<?if($arRegions):?>
					<div class="top-block-item pull-left">
						<div class="top-description">
							<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
								array(
									"COMPONENT_TEMPLATE" => ".default",
									"PATH" => SITE_DIR."include/top_page/regionality.list.php",
									"AREA_FILE_SHOW" => "file",
									"AREA_FILE_SUFFIX" => "",
									"AREA_FILE_RECURSIVE" => "Y",
									"EDIT_TEMPLATE" => "include_area.php"
								),
								false
							);?>
						</div>
					</div>
					<div>



				<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
					array(
						"COMPONENT_TEMPLATE" => ".default",
						"PATH" => SITE_DIR."include/menu/menu.topest.php",
						"AREA_FILE_SHOW" => "file",
						"AREA_FILE_SUFFIX" => "",
						"AREA_FILE_RECURSIVE" => "Y",
						"EDIT_TEMPLATE" => "include_area.php"
					),
					false
				);?>
			</div>
		
			
	
		
				<?endif;?>
			</div>
		</div>
	
	</div>
</div>




<div class="header-v8 header-wrapper">

	<div class="logo_and_menu-row">
		<div class="logo-row">
			<div class="maxwidth-theme">
				<div class="row">
					<div class="logo-block col-md-2 col-sm-3">
						<div class="logo<?=$logoClass?>">
							<?/*=CNext::ShowLogo();*/?>

							<a href="/"><img src="/images/company/logo.svg" alt="Латитудо" title="Латитудо" class="img-responsive">
							
							<?/*img src="/images/company/site_logo.png" alt="Латитудо" title="Латитудо" class="img-responsive new-year"*/?></a>	
						</div>
					</div>
				
					
							
							<?switch ($regionID) {				
	case 9277 : case 9278 :  case 9568 ?>
								<div class="nopadding_right nopadding_left" >
								<div class="pull-left inner-table-block top-description  address_header">
								<div class="main-filials">
								<div class="main-filials_padding">
										<div class="col">
								<div class="item">
								Адрес офиса:						<?if ($arRegion['PROPERTY_REGION_TAG_LINKVIDEO_VALUE']):?>
												<span class="fa fa-play-circle-o" style="padding-left:10px;color:#999999;"></span>
												
				<a  class="gallery _border" rel="group" href="<?=$REGION_TAG_LINKVIDEO?>"> видео офиса</a>
				<?endif;?>
				
									<div class="address_my">
										<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/address_my.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
										
						
									</div>
														
								<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/region_time.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
										
																	</div></div></div></div></div>
								</div>
					<? break;
					
						case 10039 ?>
										<div class="nopadding_right nopadding_left" >
								<div class="pull-left inner-table-block top-description  address_header">
								<div class="main-filials msk">
								<div class="main-filials_padding">
										<div class="col">
								<div class="item">
													
									Адрес офиса:	<?if ($arRegion['PROPERTY_REGION_TAG_LINKVIDEO_VALUE']):?>
												<span class="fa fa-play-circle-o" style="padding-left:10px;color:#999999;"></span>
												
				<a  class="gallery _border" rel="group" href="<?=$REGION_TAG_LINKVIDEO?>"> видео офиса</a>
				<?endif;?>
				<div class="address_my">
										<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/address_my.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
										
						
									</div>
														
								<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/region_time.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
										
																	</div></div></div></div></div>
								</div>
					<? break;	

		 case 22018 ?>
								<div class="col-md-4 nopadding_right nopadding_left" >
								<div class="inner-table-block top-description">
									<div class="address_my">
										<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/address_my.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
																
														<?if ($arRegion['PROPERTY_REGION_TAG_LINKVIDEO_VALUE']):?>
												<span class="fa fa-play-circle-o" style="color:#999999;"></span>
												
				<a  class="gallery _border" rel="group" href="<?=$REGION_TAG_LINKVIDEO?>"> видео офиса</a>
				<?endif;?>
										
										
									</div> 
														
								<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/region_time.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
										
										
																	</div>
								</div>
					<? break;		

 case 22029 : case 22013 ?>
								
						<div class="nopadding_right nopadding_left" >
								<div class="pull-left inner-table-block top-description  address_header">
								<div class="main-filials msk">
								
										<div class="col">
								<div class="item">											
									Выдача заказов на доставку:	
							
							<div class="address_my">
										<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/address_my.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
										
						
									</div>
														
								<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/region_time.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
										
																	</div></div></div></div>
								</div>


				<? break;		
					
	case 10102 : case 10101 :?>
	<div class="col-md-4 nopadding_right" >
	<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
									array(
										"COMPONENT_TEMPLATE" => ".default",
										"PATH" => SITE_DIR."include/timeofwork.php",
										"AREA_FILE_SHOW" => "file",
										"AREA_FILE_SUFFIX" => "",
										"AREA_FILE_RECURSIVE" => "Y",
										"EDIT_TEMPLATE" => "standard.php"
									),
									false
									);?>	
									</div>
	<? break;
	
					default:?>
					<div class="pull-left col-md-6" >
					<?$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"contacts_header_other_regions", 
	array(
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"AJAX_MODE" => "N",
		"IBLOCK_TYPE" => "aspro_next_regionality",
		"IBLOCK_ID" => "7",
		"NEWS_COUNT" => "5",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_ORDER1" => "DESC",
		"SORT_BY2" => "SORT",
		"SORT_ORDER2" => "ASC",
		"FILTER_NAME" => "arRegionLinkShop",
		"FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"PROPERTY_CODE" => array(
			0 => "EMAIL",
			1 => "ADDRESS",
			2 => "REGION_TAG_PHONE",
			3 => "REGION_TAG_PHONE_PODMENA",
			4 => "ADDRESS_SKLAD",
			5 => "PHONE",
			6 => "PHONE",
			7 => "",
		),
		"CHECK_DATES" => "Y",
		"PREVIEW_TRUNCATE_LEN" => "",
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"SET_STATUS_404" => "Y",
		"SET_TITLE" => "Y",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"ADD_SECTIONS_CHAIN" => "N",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"INCLUDE_SUBSECTIONS" => "Y",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000000",
		"CACHE_NOTES" => "",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"PAGER_TEMPLATE" => ".default",
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"PAGER_TITLE" => "Новости",
		"PAGER_SHOW_ALWAYS" => "Y",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "Y",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"AJAX_OPTION_HISTORY" => "N",
		"COMPONENT_TEMPLATE" => "contacts_header_other_regions",
		"AJAX_OPTION_ADDITIONAL" => "",
		"SET_BROWSER_TITLE" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_META_DESCRIPTION" => "Y",
		"SET_LAST_MODIFIED" => "N",
		"STRICT_SECTION_CHECK" => "N",
		"VIEW_TYPE" => "table",
		"SHOW_DETAIL_LINK" => "Y",
		"COUNT_IN_LINE" => "3",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"SHOW_404" => "Y",
		"MESSAGE_404" => "",
		"ELEMENT_ID" => $_REQUEST["ELEMENT_ID"],
		"ELEMENT_CODE" => "",
		"IBLOCK_URL" => "",
		"SET_CANONICAL_URL" => "N",
		"BROWSER_TITLE" => "-",
		"META_KEYWORDS" => "-",
		"META_DESCRIPTION" => "-",
		"ADD_ELEMENT_CHAIN" => "Y",
		"USE_PERMISSIONS" => "N",
		"IMAGE_POSITION" => "left",
		"USE_SHARE" => "N",
		"S_ASK_QUESTION" => "",
		"S_ORDER_SERVICE" => "",
		"T_GALLERY" => "",
		"T_DOCS" => "",
		"T_GOODS" => "",
		"T_SERVICES" => "",
		"T_PROJECTS" => "",
		"T_REVIEWS" => "",
		"T_STAFF" => "",
		"TITLE_BLOCK" => "Новости",
		"TITLE_BLOCK_ALL" => "Все новости",
		"ALL_URL" => "company/news/",
		"SHOW_DATE" => "N",
		"SHOW_IMAGE" => "N",
		"DETAIL_URL" => "",
		"FILE_404" => ""
	),
	false
);?>


				
				
									</div>
						
					<? break;					
										}
				?>
						

						
													<?switch ($regionID) {				
	case 9278 : case 9568 ?>
						<div class="pull-left nopadding_right nopadding_left" >
								<div class="inner-table-block top-description address_header">
								<div class="main-filials " >
								<div class="main-filials_padding sklad">
									<div class="">
								<div class="item">
									Адрес склада: 
									<div class="address_my">
										<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/address_my_sklad.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
									</div>	
									
										Пн — Пт: с 9:00 до 18:00<br>(перерыв с 13:00 до 14:00)<br>Сб — Вс: выходные дни	
										</div>
										</div>	</div>				
									</div>
									
									</div>
								</div>
						
					<? break;
					
						case 10039: ?>
						<div class="pull-left nopadding_right nopadding_left" >
								<div class="inner-table-block top-description address_header">
								<div class="main-filials " >
								<div class="main-filials_padding sklad">
									<div class="">
								<div class="item">
									Адрес склада: 
									<div class="address_my">
										<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/address_my_sklad.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
									</div>	
									
										Пн — Пт: с 9:00 до 18:00 (перерыв с 13:00 до 14:00)<br>Сб — Вс: выходные дни	
										</div>
										</div>	</div>				
									</div>
									
									</div>
								</div>
						
					<? break;
					
						case 9277: ?>
						<div class="pull-left nopadding_right nopadding_left" >
								<div class="inner-table-block top-description address_header">
								<div class="main-filials" >
								<div class="main-filials_padding sklad">
									<div class="">
								<div class="item">
									Адрес склада: 
									<div class="address_my">
										<?$APPLICATION->IncludeComponent(
										"bitrix:main.include",
										"",
										Array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/address_my_sklad.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
										);?>
									</div>	
									
										Пн — Пт: с 8:00 до 17:00<br>Сб — Вс: выходные дни	
										</div>
										</div>	</div>				
									</div>
									
									</div>
								</div>
						
					<? break;
					
}
				?>
					
				
						
						
				<div class="pull-right">
							<div class="inner-table-block" style="margin-top:-15px;">
							<div><span style="text-indent:0;" class="callback-block animate-load twosmallfont colored  btn-default  btn" data-event="jqm" data-param-form_id="MAINFORM" data-name="question" >Оставить заявку</span></div>
								
								<?/*if ($arRegion['PROPERTY_REGION_TAG_MAIL_VALUE']):?>
								<div> <span class="fa fa-envelope-o"></span><a class="_border"  href="mailto:<?=$REGION_TAG_MAIL?>"><?=$REGION_TAG_MAIL?></a></div>
								<?endif;*/?>
								<div><span class="fa fa-whatsapp"></span><a   data-event="jqm" data-param-form_id="WHATSAPP" data-name="spbuttonWHATSAPPoblojkaIHud6578Sl64782XDMFy" class="whatsap" >Написать в WhatsApp</a></div>
						<div><span class="fa fa-send"></span><a   data-event="jqm" data-param-form_id="TELEGRAM" data-name="spbuttonTELEGRAMoblojkaIHud6578Sl64782XDMFy" class="whatsap" >Написать в Telegram</a></div>
						
							
							</div>
				</div>
							
			
			<?if($bPhone):?>
							<div class="pull-right">
								<div class="inner-table-block">
								<div class="phone">
								
								
								
								
								
								
					<?
									$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
									$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
									$dump1 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);
									$href1 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);?>	
				
						
								<?if ($utm_medium == "cpc") :?>   
								<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
								<div class="head_phon"> 
									<a  rel="nofollow" href="<?=$href1?>"><?=$REGION_TAG_PHONE_PODMENA?><span><?=$REGION_TAG_PHONE_PODP?></span></a>
								</div>
								<br/>
								 <?else:?>
								  <div class="head_phon"> 
									<a  rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?><span><?=$REGION_TAG_PHONE_PODP?></span></a>
								</div>
								<br/>
									<?if ($arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']):
				$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']);
				$hrefskl = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']);?>				
				<div class="head_phon"> 
				<a rel="nofollow" href="<?=$hrefskl?>"><?=$REGION_TAG_PHONESKLAD?><span><?=$REGION_TAG_PHONESKLAD_PODP?></span></a>
				</div>	 
				<?endif;?>
				
								<?endif;?>
								 <?else:?>
								 <div class="head_phon"> 
									<a  rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?><span><?=$REGION_TAG_PHONE_PODP?></span></a>
								</div>
								<br/>
								<?if ($arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']):
				$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']);
				$hrefskl = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']);?>				
				<div class="head_phon"> 
				<a rel="nofollow" href="<?=$hrefskl?>"><?=$REGION_TAG_PHONESKLAD?><span><?=$REGION_TAG_PHONESKLAD_PODP?></span></a>
				</div>	 
				<?endif;?>
				
								<?endif;?>
						
								
						
						
						
					<?/*<div style="margin-top:-5px"><a class="_border" style="font-weight:normal; margin-left: 0px;" href="/contacts/"><span class="mb-22">График работы в праздничные дни</span></a></div>*/?>
						
				
								
								</div>
								
									<?if ($arRegion['PROPERTY_REGION_TAG_MAIL_VALUE']):?>
								<div> <span class="fa fa-envelope-o"></span><a class="_border"  href="mailto:<?=$REGION_TAG_MAIL?>"><?=$REGION_TAG_MAIL?></a></div>
								<?endif;?>
								
								
						<? switch ($regionID) 
						{
                        case 10039 ?>
						<div style="font-size: 12px;line-height: 18px;color:#999999;">Для пропуска нужен паспорт или права</div>
						<a data-event="jqm" data-param-form_id="PROPUSK" data-name="spbuttonPROPUSKcontactIHud6578Sl64782XDMFi" class="whatsap" style="margin-left:0;">Заказать пропуск</a>
                        <? break;
						default: ?>
                        <? break;
                        }
						?>		
						<? switch ($regionID) 
						{
                        case 24647 : case 24639 : case 24629 : case 22015?>
						<div class="prep">Ваши заказы обслуживает<br/>Краснодарский филиал</div>
						<? break;
						case 22002 : case 22007 : case 22017 :?>	
                      <?//	case 22002 : case 22007 : case 22013 : case 22017 :?>	
							
						<div class="prep">Ваши заказы обслуживает<br/>Воронежский филиал</div>
						<? break;
                      	case 10101 : case 10102 : case 21990 : case 22005?>	
						<div class="prep">Ваши заказы обслуживает<br/>Белгородский филиал</div>
                        <? break;	
						case 22025 : case 24628 : case 24630 : case 24632 : case 24643 : case 24644 : case 24645 : case 24648 :  case 24631 : case 24646?>	
						<div class="prep">Ваши заказы обслуживает<br/>Ростовский филиал</div>
						 <? break;	
						case 21989 : case 24619 : case 24620?>	
						<?//case 21989 : case 22029 : case 24619 : case 24620?>	

						<div class="prep">Ваши заказы обслуживает<br/>Краснодарский и Ростовский филиалы</div>
						 <? break;	
						case 21996 : case 21997 : case 22000 : case 22011 : case 22014 : case 22020 : case 22021 : case 22024 : case 22026 : case 22027?>	
						<div class="prep">Ваши заказы обслуживает<br/>Московский филиал</div>
                        <? break;
						default: ?>
                        <? break;
                        }
						?>

					</div>
					</div>
			<?endif?>

						
					</div>		
					
				</div>
			</div>
		</div><?// class=logo-row?>
	
	
		
				
		<div class="menu-row middle-block bg<?=strtolower($arTheme["MENU_COLOR"]["VALUE"]);?> sliced">
		<div class="maxwidth-theme">
			<div class="row">
				<div class="col-md-12">
					<div class="right-icons pull-right show-fixed">
						<div class="wrap_icon">
							<button class="inline-search-show twosmallfont">
								<?=CNext::showIconSvg("search", SITE_TEMPLATE_PATH."/images/svg/Search_black.svg");?>
							</button>
						</div>
					</div>
					<div class="menu-only">
						<nav class="mega-menu sliced">
							<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
								array(
									"COMPONENT_TEMPLATE" => ".default",
									"PATH" => SITE_DIR."include/menu/menu.top.php",
									"AREA_FILE_SHOW" => "file",
									"AREA_FILE_SUFFIX" => "",
									"AREA_FILE_RECURSIVE" => "Y",
									"EDIT_TEMPLATE" => "include_area.php"
								),
								false, array("HIDE_ICONS" => "Y")
							);?>
						</nav>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="line-row visible-xs"></div>
</div>












