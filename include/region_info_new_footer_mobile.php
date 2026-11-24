<?php
	global $arRegion,$arTheme, $APPLICATION, $timeopen_regions, $timeclose_regions, $timeopen_rest, $timeclose_rest, $days, $dayofweek, $nowtime, $utmSource;
	static $email_call;
	$regionID = ($arRegion ? $arRegion['ID'] : '');
	$arRegionName = ($arRegion ? $arRegion['NAME'] : '');
$REGION_TAG_NAME = "#REGION_TAG_NAME#";
$REGION_TAG_ADDRESSMY = "#REGION_TAG_ADDRESSMY#";
$REGION_TAG_PHONE = "#REGION_TAG_PHONE#";
$REGION_TAG_PHONE_8800 = "#REGION_TAG_PHONE_8800#";
$REGION_TAG_PHONE_MOBILE = "#REGION_TAG_PHONE_MOBILE#";

$REGION_TAG_PHONESKLAD = "#REGION_TAG_PHONESKLAD#";
$REGION_TAG_PHONE3 = "#REGION_TAG_PHONE3#";
$REGION_TAG_TIME = "#REGION_TAG_TIME#";
$REGION_TAG_MAIL = "#REGION_TAG_MAIL#";

$REGION_TAG_TIME_SKLAD = "#REGION_TAG_TIME_SKLAD#";
$REGION_TAG_ADDRESSMY_SKLAD = "#REGION_TAG_ADDRESSMY_SKLAD#";
$REGION_NAME_DECLINE_PP = "#REGION_NAME_DECLINE_PP#";
$REGION_TAG_SEO_OBLAST_IP = "#REGION_TAG_SEO_OBLAST_IP#";
$REGION_TAG_SEO_OBLAST_PP = "#REGION_TAG_SEO_OBLAST_PP#";
$REGION_TAG_USE_NUMBERS_PHONE = "#REGION_TAG_USE_NUMBERS_PHONE#";
?>

<?=bitrix_sessid_post();?>



<style>
footer .bottom-middle{padding: 10px 0px 0px;}
#footer .footer_inner .phone {display:block !important;font-weight: bold;font-size: 20px;line-height: 34px;margin-bottom: 15px;}
	#footer .footer_inner .phone a {border-bottom:none;}
	#footer .footer_inner  .f-link {line-height: 32px;margin-bottom: 20px;}
	#footer .footer_inner  .f-link a {border-bottom:1px dashed;}
</style>
<? if($arRegion):?> 
	<div class="row bottom-middle">
		<div class="col-md-3">
					<?//адрес?>
					<?switch ($regionID) {				
					case 9277 : case 9278 : case 10039 : case 9568 : case 22018?>					
					<?if($arRegion['PROPERTY_REGION_TAG_ADDRESSMY_VALUE']):?>
					<div class="mb-22" ><b>Адрес офиса:</b><br><?=$REGION_TAG_ADDRESSMY?>
					<br/>
					<div class="f-link"><a href="/contacts/"><span class="mb-22">схема проезда</span></a></div></div>
					<?endif;?>
					<? break;
					default:?>
					<? break;					
					}
					?>
					<?//адрес?>
						<? switch ($regionID) 
						{
                        case 10039 ?>
						<div style="margin:20px 0 0;">Для пропуска нужен паспорт или права</div>
						<div style="margin:10px 0 20px;"><span style="text-indent:0;" class="callback-block animate-load twosmallfont colored  btn-default  btn" data-event="jqm" data-param-form_id="PROPUSK" data-name="propusk">Заказать пропуск</span></div>
                        <? break;
						default: ?>
                        <? break;
                        }
						?>

						
					<?//график работы для города?>
							<?switch ($regionID) {				
							case 9277 : case 9278 : case 10039 : case 9568 : case 22018?>	
							<?if ($arRegion['PROPERTY_REGION_TAG_TIME_VALUE']):?>
							<div><b>Режим работы офиса:</b><br><?=$REGION_TAG_TIME?></div>
							<?endif;?>
										
										<?if ($arRegion['PROPERTY_REGION_TAG_ADDRESSMY_SKLAD_VALUE']):?>
							<div style="margin-top:30px;"><b>Адрес склада:</b><br><?=$REGION_TAG_ADDRESSMY_SKLAD?></div>
							<?endif;?>
								<?if ($arRegion['PROPERTY_REGION_TAG_TIME_SKLAD_VALUE']):?>
							<div><b>Режим работы склада:</b><br><?=$REGION_TAG_TIME_SKLAD?></div>
							<?endif;?>
								
							<? break;
							?>
							<? break;	
							default:?>
							<div class="mb-22"><span class="bold"><?=$arRegionName?></span></div>	
								



								<div>С понедельника по пятницу: с 9:00 до 18:00<br>суббота: с 10:00 до 15:00<br>воскресенье: выходной день</div>
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
					
					

						<?switch ($regionID) {				
							case 9277 : case 9278 : case 10039 : case 9568 : case 22018 ?>	
							<div class="phone"><a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a></div>
							<? break;	
							default:?>
							<div class="phone mb-22"><a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a>
							<div class="phonespan mb-22">Звонок бесплатный</div></div>
								
										
							<? break;					
							}
							?>
							
							
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
					
					
					<?/*ЕСЛИ НОМЕРА ОБЫЧНЫЕ*/?>	
		</div>
		
					
		<div class="col-md-3 ">
							<?if ($arRegion['PROPERTY_REGION_TAG_MAIL_VALUE']):?>
																		<div class="f-link"><a href="mailto:<?=$REGION_TAG_MAIL?>"><?=$REGION_TAG_MAIL?></a></div>

							<?endif;?>
									<div class="f-link"><a data-name="spbuttonMAXfixedheaderfottermobilecontacts8Sl64782XDMFy" data-event="jqm" data-param-form_id="MAX">Написать в MAX</a></div>
									<div class="f-link"><a data-name="spbuttonTELEGRAMfixedheaderfottermobilecontacts8Sl64782XDMFy" data-event="jqm" data-param-form_id="TELEGRAM">Написать в Telegram</a></div>
									<div class="f-link"><a data-name="spbuttonWHATSAPPfixedheaderfottermobilecontacts8Sl64782XDMFy" data-event="jqm" data-param-form_id="WHATSAPP">Написать в WhatsApp</a></div>
		</div>
						
		<div class="col-md-3">
								<div class="social-block rounded_block" >
					<?$APPLICATION->IncludeComponent("aspro:social.info.next", "vertical_group", Array(
	"CACHE_TYPE" => "A",	// Тип кеширования
		"CACHE_TIME" => "172800",	// Время кеширования (сек.)
		"CACHE_GROUPS" => "N",	// Учитывать права доступа
		"COMPONENT_TEMPLATE" => "template2",
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