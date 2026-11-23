<?php
global $arRegion,$arTheme, $APPLICATION, $timeopen_regions, $timeclose_regions, $timeopen_rest, $timeclose_rest, $days, $dayofweek, $nowtime;
static $email_call;
$regionID = ($arRegion ? $arRegion['ID'] : '');
$arRegionName = ($arRegion ? $arRegion['NAME'] : '');
$REGION_TAG_NAME = "#REGION_TAG_NAME#";
$REGION_TAG_ADDRESSMY = "#REGION_TAG_ADDRESSMY#";
$REGION_TAG_PHONE = "#REGION_TAG_PHONE#";
$REGION_TAG_PHONE_8800 = "#REGION_TAG_PHONE_8800#";
$REGION_TAG_PHONE_MOBILE = "#REGION_TAG_PHONE_MOBILE#";
$REGION_TAG_PHONE_PODMENA = "#REGION_TAG_PHONE_PODMENA#";
$REGION_TAG_PHONESKLAD = "#REGION_TAG_PHONESKLAD#";
$REGION_TAG_PHONE3 = "#REGION_TAG_PHONE3#";
$REGION_TAG_TIME = "#REGION_TAG_TIME#";
$REGION_TAG_MAIL = "#REGION_TAG_MAIL#";
$REGION_TAG_EMAIL_PODMENA = "#REGION_TAG_EMAIL_PODMENA#";
$REGION_TAG_RAITING = "#REGION_TAG_RAITING#";
$REGION_NAME_DECLINE_PP = "#REGION_NAME_DECLINE_PP#";
$REGION_TAG_SEO_OBLAST_IP = "#REGION_TAG_SEO_OBLAST_IP#";
$REGION_TAG_SEO_OBLAST_PP = "#REGION_TAG_SEO_OBLAST_PP#";
$REGION_TAG_USE_NUMBERS_PHONE = "#REGION_TAG_USE_NUMBERS_PHONE#";
?>


<?=bitrix_sessid_post();?>
<?
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
if ($val=='utm_source')
	$utm_source =$v;
}
/* 22.09.2026: подмена — как в шапке: только пока идёт рекламный визит (ndIsUtmVisit, сутки с перехода);
   метка в сессии живёт 30 дней. 'ya_visit' лишь включает проверки str_contains(..., 'ya') ниже. */
$utm_source = (function_exists('ndIsUtmVisit') && ndIsUtmVisit()) ? 'ya_visit' : 'empty';
?>




<? if($arRegion):?> 
	<div class="row bottom-middle">
		<div class="col-md-3">
					<?//адрес?>
					<?switch ($regionID) {				
					case 9277 : case 9278 : case 10039 : case 9568 : case 22018 ?>					
					<?if($arRegion['PROPERTY_REGION_TAG_ADDRESSMY_VALUE']):?>
					<div class="mb-22" ><?=$REGION_TAG_ADDRESSMY?>
					<br/>
					<div class="f-link"><a href="/contacts/"><span class="mb-22">схема проезда</span></a></div></div>
					<?endif;?>
					<? break;
					default:?>
					<? break;					
					}
					?>
					<?/*switch ($regionID) {				
				 case 22029 : case 22013?>					
					<?if($arRegion['PROPERTY_REGION_TAG_ADDRESSMY_VALUE']):?>
					<div class="mb-22" >Выдача заказов на доставку<br><?=$REGION_TAG_ADDRESSMY?>
					<br/>
					<div class="f-link"><a href="/contacts/"><span class="mb-22">схема проезда</span></a></div></div>
					<?endif;?>
					<? break;
					default:?>
					<? break;					
					}
					*/?>
					<?//адрес?>
					
					
						
					<?//график работы для города?>
							<?switch ($regionID) {				
							case 9277 : case 9278 : case 10039 : case 9568 : case 22018  /*:case 22029  : case 22013*/?>	
							<?if ($arRegion['PROPERTY_REGION_TAG_TIME_VALUE']):?>
							<div><?=$REGION_TAG_TIME?></div>
							<?endif;?>
							<? break;
							?>
							<? break;	
							default:?>
							
								<div class="mb-22"><span class="bold"><?=$arRegionName?></span></div>	
									
								<?//на городах филиалов подменялись номера телефонов, если была метка utm?>	
									<?/*$APPLICATION->IncludeComponent(
									"bitrix:news.list", 
									"other_regions_footer", 
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
										"SET_STATUS_404" => "N",
										"SET_TITLE" => "N",
										"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
										"ADD_SECTIONS_CHAIN" => "N",
										"HIDE_LINK_WHEN_NO_DETAIL" => "N",
										"PARENT_SECTION" => "",
										"PARENT_SECTION_CODE" => "",
										"INCLUDE_SUBSECTIONS" => "N",
										"CACHE_TYPE" => "A",
										"CACHE_TIME" => "36000000",
										"CACHE_NOTES" => "",
										"CACHE_FILTER" => "N",
										"CACHE_GROUPS" => "N",
										"PAGER_TEMPLATE" => ".default",
										"DISPLAY_TOP_PAGER" => "N",
										"DISPLAY_BOTTOM_PAGER" => "N",
										"PAGER_TITLE" => "Новости",
										"PAGER_SHOW_ALWAYS" => "N",
										"PAGER_DESC_NUMBERING" => "N",
										"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
										"PAGER_SHOW_ALL" => "N",
										"AJAX_OPTION_JUMP" => "N",
										"AJAX_OPTION_STYLE" => "Y",
										"AJAX_OPTION_HISTORY" => "N",
										"COMPONENT_TEMPLATE" => "contacts_header_other_regions",
										"AJAX_OPTION_ADDITIONAL" => "",
										"SET_BROWSER_TITLE" => "N",
										"SET_META_KEYWORDS" => "N",
										"SET_META_DESCRIPTION" => "N",
										"SET_LAST_MODIFIED" => "N",
										"STRICT_SECTION_CHECK" => "N",
										"VIEW_TYPE" => "table",
										"SHOW_DETAIL_LINK" => "Y",
										"COUNT_IN_LINE" => "3",
										"PAGER_BASE_LINK_ENABLE" => "N",
										"SHOW_404" => "N",
										"MESSAGE_404" => "",
										"ELEMENT_ID" => $_REQUEST["ELEMENT_ID"],
										"ELEMENT_CODE" => "",
										"IBLOCK_URL" => "",
										"SET_CANONICAL_URL" => "N",
										"BROWSER_TITLE" => "-",
										"META_KEYWORDS" => "-",
										"META_DESCRIPTION" => "-",
										"ADD_ELEMENT_CHAIN" => "N",
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
								);*/?>
								<?//на городах филиалов подменялись номера телефонов, если была метка utm?>	
								

								<div class="mb-22">С понедельника по пятницу: с 9:00 до 18:00<br>суббота: с 10:00 до 15:00<br>воскресенье: выходной день</div>					
								<?if ($arRegion['PROPERTY_REGION_TAG_OBLAST_DP_VALUE']):?>
								<div class="mb-22">Доставка по адресу или на объект в <?=$REGION_TAG_SEO_OBLAST_PP?></div>
								<?endif;?>
								
								
							<? break;					
							}
							?>
					<?//график работы для города?>		
				
		</div>
		<div class="col-md-3">
				<? if ($arRegion['PROPERTY_REGION_TAG_USE_NUMBERS_PHONE_VALUE'] == "Y") : ?>
						  	<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
							
									
															
																		<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']):
																		$dump8800 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']);
																		$href8800 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']);
																		?>
																		
																		
																		<div class="phone"><a  rel="nofollow" href="<?=$href8800?>"><?=$REGION_TAG_PHONE_8800?></a></div>
																		<div class="free">Звонок бесплатный</div>	
																		<?endif;?>
															 
															
																<br/>
																<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']):
																		$dumpphone_mobile = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']);
																		$hrefphone_mobile = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']);?>				
																
																<div class="phone"><a rel="nofollow" href="<?=$hrefphone_mobile?>"><?=$REGION_TAG_PHONE_MOBILE?></a></div>
															 
																<?endif;?>
																
									
															
											
															
															
													<? switch ($regionID) 
													{
													case 10039 ?>
													<div style="font-size: 12px;line-height: 18px;color:#999999;">Для пропуска нужен паспорт или права</div>
													<a data-event="jqm" data-param-form_id="PROPUSK" data-name="spbuttonPROPUSKcontactIHud6578Sl64782XDMFi" class="whatsap" style="margin-left:0;">Заказать пропуск
													 </a>
													<? break;
													default: ?>
													<? break;
													}
													?>		
							
							<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
						  <?else:?>
						  
						  
						  	<?/*ЕСЛИ НОМЕРА ОБЫЧНЫЕ*/?>




							<?
					$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']); 
					$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
					?>
					
					<?
					$dump1 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);
					$href1 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);
					?>
					
			
		<?if (str_contains($utm_source, "ya") || str_contains($utm_source, "tg") || str_contains($utm_source, "vk") || str_contains($utm_source, "maps")) :?>       
							<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
									<div class="phone"><a rel="nofollow" href="<?=$href1?>"><?=$REGION_TAG_PHONE_PODMENA?></a></div>
							<?else:?>
								<div class="phone"><a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a></div>
								 
								 <?//Приписка Ваши заказы обслуживает какой-то филиал?>
								 <?/* switch ($regionID) {
									case 24647 : case 24639 : case 24629 : case 24620 : case 24619 : case 22015?>
																		<?//case 24647 : case 24639 : case 24629 : case 24620 : case 24619 : case 22015 :  case 22029?>

									<div class="prep">Ваши заказы обслуживает<br/>Краснодарский филиал</div>
									<? break;
									case 22002 : case 22007 : case 22013 : case 22017 :?>	
									<div class="prep">Ваши заказы обслуживает<br/>Воронежский филиал</div>
									<? break;
									case 10101 : case 10102 : case 21990 : case 22005?>	
									<div class="prep">Ваши заказы обслуживает<br/>Белгородский филиал</div>
									<? break;	
									case 22025 : case 24628 : case 24630 : case 24632 : case 24643 : case 24644 : case 24645 : case 24648 : case 21989 : case 24631 : case 24646?>	
									<div class="prep">Ваши заказы обслуживает<br/>Ростовский филиал</div>
									 <? break;	
									case 21996 : case 21997 : case 22000 : case 22011 : case 22014 : case 22020 : case 22021 : case 22024 : case 22026 : case 22027?>	
									<div class="prep">Ваши заказы обслуживает<br/>Московский филиал</div>
									<? break;
									default: ?>
									<? break;
								  }*/?>
								<?//Приписка Ваши заказы обслуживает какой-то филиал?>
								
								
								<?if ($arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']):
								$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']);
								$hrefskl = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']);?>				

								<div class="phone"><a rel="nofollow" href="<?=$hrefskl?>"><?=$REGION_TAG_PHONESKLAD?></a></div>	 
								<?endif;?>
									
								<?if ($arRegion['PROPERTY_REGION_TAG_PHONE3_VALUE']):
								$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE3_VALUE']);
								$hrefph3 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONE3_VALUE']);?>				

								<div class="phone" style="margin-bottom:0;"><a rel="nofollow" href="<?=$hrefph3?>"><?=$REGION_TAG_PHONE3?></a></div>	 
								<?endif;?>
				
			<?endif;?>
					<?else:?>
								<?// если нет UTM метки, то вывод?>
					
						
						
							<?switch ($regionID) {				
							case 9277 : case 9278 : case 10039 : case 9568 : case 22018 /*: case 22029  : case 22013*/?>	
							<div class="phone"><a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a></div>
							<? break;	
							default:?>
							<div class="phone mb-22"><a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a></div>
							<div class="mb-22">Звонок бесплатный</div>	
										
							<? break;					
							}
							?>
							
							
						<?//Приписка Ваши заказы обслуживает какой-то филиал?>
						<?/* switch ($regionID) {
                        case 24647 : case 24639 : case 24629 : case 22015?>
						<div class="prep">Ваши заказы обслуживает<br/>Краснодарский филиал</div>
						<? break;
                      	case 22002 : case 22007 : case 22017 :?>	
						<?//case 22002 : case 22007 : case 22013 : case 22017 :?>	
						<div class="prep">Ваши заказы обслуживает<br/>Воронежский филиал</div>
						<? break;
                      	case 10101 : case 10102 : case 21990 : case 22005?>	
						<div class="prep">Ваши заказы обслуживает<br/>Белгородский филиал</div>
                        <? break;	
						 case 21989 : case 24619 : case 24620?>	
						 						 <?//case 21989 : case 22029 : case 24619 : case 24620?>	

						<div class="prep">Ваши заказы обслуживает<br/>Краснодарский и Ростовский филиалы</div>
						 <? break;	
						case 22025 : case 24628 : case 24630 : case 24632 : case 24643 : case 24644 : case 24645 : case 24648 : case 24631 : case 24646?>	
						<div class="prep">Ваши заказы обслуживает<br/>Ростовский филиал</div>
						 <? break;	
						case 21996 : case 21997 : case 22000 : case 22011 : case 22014 : case 22020 : case 22021 : case 22024 : case 22026 : case 22027?>	
						<div class="prep">Ваши заказы обслуживает<br/>Московский филиал</div>
                        <? break;
						default: ?>
                        <? break;
                        }*/?>
						<?//Приписка Ваши заказы обслуживает какой-то филиал?>
						
						
						<?if ($arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']):
						$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']);
						$hrefskl = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']);?>				

						<div class="phone"><a rel="nofollow" href="<?=$hrefskl?>"><?=$REGION_TAG_PHONESKLAD?></a></div>	 
						<?endif;?>
						
						<?if ($arRegion['PROPERTY_REGION_TAG_PHONE3_VALUE']):
						$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE3_VALUE']);
						$hrefph3 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONE3_VALUE']);?>				

						<div class="phone" style="margin-bottom:0;"><a rel="nofollow" href="<?=$hrefph3?>"><?=$REGION_TAG_PHONE3?></a></div>	 
						<?endif;?>
						<?// если нет UTM метки, то вывод?>
			
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
			
		
			 	<?/*ЕСЛИ НОМЕРА ОБЫЧНЫЕ*/?>
<?endif;?>
		</div>
					
	<div class="col-md-3">
			<?if ($arRegion['PROPERTY_REGION_TAG_MAIL_VALUE'] || $arRegion['PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE']):?>
				<?if ((str_contains($utm_source, "ya") || str_contains($utm_source, "tg") || str_contains($utm_source, "vk") || str_contains($utm_source, "maps")) && $arRegion['PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE']):?>
				<div class="f-link"><a href="mailto:<?=$REGION_TAG_EMAIL_PODMENA?>"><?=$REGION_TAG_EMAIL_PODMENA?></a></div>
				<?else:?>
				<div class="f-link"><a href="mailto:<?=$REGION_TAG_MAIL?>"><?=$REGION_TAG_MAIL?></a></div>
				<?endif;?>
			<?endif;?>
			<div class="f-link"><a data-name="spbuttonMAXfixedheaderfottercontacts8Sl64782XDMFy" data-event="jqm" data-param-form_id="MAX" >Написать в MAX</a></div>
			<div class="f-link"><a data-name="spbuttonTELEGRAMfixedheaderfottercontacts8Sl64782XDMFy" data-event="jqm" data-param-form_id="TELEGRAM" >Написать в Telegram</a></div>
			<div class="f-link"><a data-name="spbuttonWHATSAPPfixedheaderfottercontacts8Sl64782XDMFy" data-event="jqm" data-param-form_id="WHATSAPP" >Написать в WhatsApp</a></div>
			
				<div><?=$REGION_TAG_RAITING?></div>					
	</div>
						
	<div class="col-md-3">
		<div class="social-block rounded_block" style="margin-bottom:30px;">
			<?$APPLICATION->IncludeComponent("aspro:social.info.next", "vertical_group", Array(
				"CACHE_TYPE" => "N",	// Тип кеширования
					"CACHE_TIME" => "172800",	// Время кеширования (сек.)
					"CACHE_GROUPS" => "N",	// Учитывать права доступа
					"COMPONENT_TEMPLATE" => "vertical_group",
					"SOCIAL_TITLE" => GetMessage("SOCIAL_TITLE"),
					"TITLE_BLOCK" => "",	// Заголовок блока
					"COMPOSITE_FRAME_MODE" => "A",
					"COMPOSITE_FRAME_TYPE" => "AUTO"
				),
				false
			);?>
		</div>
	</div>
					
		
	</div>







<?endif;?>