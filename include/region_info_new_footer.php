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

$REGION_TAG_PHONESKLAD = "#REGION_TAG_PHONESKLAD#";
$REGION_TAG_PHONE3 = "#REGION_TAG_PHONE3#";
$REGION_TAG_TIME = "#REGION_TAG_TIME#";
$REGION_TAG_MAIL = "#REGION_TAG_MAIL#";

$REGION_TAG_RAITING = "#REGION_TAG_RAITING#";
$REGION_NAME_DECLINE_PP = "#REGION_NAME_DECLINE_PP#";
$REGION_TAG_SEO_OBLAST_IP = "#REGION_TAG_SEO_OBLAST_IP#";
$REGION_TAG_SEO_OBLAST_PP = "#REGION_TAG_SEO_OBLAST_PP#";
$REGION_TAG_USE_NUMBERS_PHONE = "#REGION_TAG_USE_NUMBERS_PHONE#";
?>


<?=bitrix_sessid_post();?>





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
			<?if ($arRegion['PROPERTY_REGION_TAG_MAIL_VALUE']):?>
								<div class="f-link"><a href="mailto:<?=$REGION_TAG_MAIL?>"><?=$REGION_TAG_MAIL?></a></div>

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