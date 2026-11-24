<?
global $arTheme, $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');
if($arRegion)
	$bPhone = ($arRegion['PHONES'] ? true : false);

else
	$bPhone = ((int)$arTheme['HEADER_PHONES'] ? true : false);
$REGION_TAG_PHONE = "#REGION_TAG_PHONE#";
$REGION_TAG_PHONE_PODP = "#REGION_TAG_PHONEPODP#";
$REGION_TAG_PHONESKLAD = "#REGION_TAG_PHONESKLAD#";
$REGION_TAG_PHONESKLAD_PODP = "#REGION_TAG_PHONESKLAD_PODP#";
$REGION_TAG_MAIL = "#REGION_TAG_MAIL#";

$logoClass = ($arTheme['COLORED_LOGO']['VALUE'] !== 'Y' ? '' : ' colored');
?>


<div class="wrapper_inner">
	<div class="logo-row v1 row margin0">
			<div class="pull-left">
			<div class="inner-table-block  nopadding logo-block">
			<div class="logo<?=$logoClass?>">
						<?/*=CNext::ShowLogo();*/?>

							<a href="/"><?/*img src="/images/company/logo.png" alt="Латитудо" title="Латитудо" class="img-responsive"*/?><img src="/images/company/site_logo.png" alt="Латитудо" title="Латитудо" class="img-responsive"></a>	
							
					
						</div>
			</div>
		</div>
		<div class="pull-left col-md-4 nopadding hidden-sm hidden-xs search animation-width">
			<div class="inner-table-block">
	<?switch ($regionID) {				
	case 9277 : case 9278 : case 10039?>
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
									</div>
														
								
								</div>
					<? break;

	
					default:?>
			
					<? break;					
										}
				?>
				
			</div>
		</div>
		
	
		<?//endif;?>
		
		<?/*phone_office*/?>
		
		
		
						<? if ($arRegion['PROPERTY_REGION_TAG_USE_NUMBERS_PHONE_VALUE'] == "Y") : ?>
						  	<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
							<div class="pull-right nopadding">
							<div class="inner-table-block" style="padding:0px !important;">
									<div class="phone">
																<div class="head_phon"> 
																		<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']):
																		$dump8800 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']);
																		$href8800 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']);
																		?>
																		
																		
																			 <div class="head_phon"><a  rel="nofollow" href="<?=$href8800?>"><?=$REGION_TAG_PHONE_8800?></a></div>
																		<?endif;?>
															 
																</div>
																<br/>
																<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']):
																		$dumpphone_mobile = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']);
																		$hrefphone_mobile = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']);?>				
																<div class="head_phon"> 
																<a rel="nofollow" href="<?=$hrefphone_mobile?>"><?=$REGION_TAG_PHONE_MOBILE?></a>
																</div>	 
																<?endif;?>
																
									</div>
															
													
							</div>
							</div>
							<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
						  <?else:?>
						  
						  
						  	<?/*ЕСЛИ НОМЕРА ОБЫЧНЫЕ*/?>
		
		<div class="pull-left ">
			<div class="inner-table-block" style="padding:0px !important;">
				<div class="phone">
								
				<?
				$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
				$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);			


				?>	
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
				

				
				
				</div>
				<div> 
				<span class="fa fa-envelope-o"></span>
				<a style="border-bottom:1px dashed; margin-left:10px;color:unset;font-size:13px;" href="mailto:<?=$REGION_TAG_MAIL?>"><?=$REGION_TAG_MAIL?></a>
				</div>
			</div>
		</div>
		
		<?/*ЕСЛИ НОМЕРА ОБЫЧНЫЕ*/?>
		
		<?endif;?>
		
		<?if($bPhone):?>
			<div class="pull-right">
					<div class="inner-table-block">
						
						<div class="social_block">
						<div class="max_messenger"><span class=""></span><a  style="border-bottom:1px dashed;margin-left:10px;color:unset;font-size:13px;" data-name="spbuttonMAXfixedheaderIHud6578Sl64782XDMFy" data-event="jqm" data-param-form_id="MAX" class="whatsap" >Написать в MAX</a></div>
			
	<div><span class="fa fa-send"></span><a  style="border-bottom:1px dashed;margin-left:10px;color:unset;font-size:13px;" data-name="spbuttonTELEGRAMfixedheaderIHud6578Sl64782XDMFy" data-event="jqm" data-param-form_id="TELEGRAM" class="whatsap" >Написать в Telegram</a></div>
	<div><span class="fa fa-whatsapp"></span><a  style="border-bottom:1px dashed;margin-left:10px;color:unset;font-size:13px;" data-name="spbuttonWHATSAPPfixedheaderIHud6578Sl64782XDMFy" data-event="jqm" data-param-form_id="WHATSAPP" class="whatsap" >Написать в WhatsApp</a></div>
	
						</div>				
 
 
 
 					
 
				
					</div>
			</div>
		<?endif;?>
				<div class="pull-right">
				<div class="inner-table-block" style="/*padding-left:40px;*/">
					
					
					
								<span style="text-indent:0;" class="callback-block animate-load twosmallfont colored  btn-default  btn" data-event="jqm" data-param-form_id="MAINFORM" data-name="question">Заказать звонок</span>
<?if ($arRegion['PROPERTY_REGION_TAG_MAIL_VALUE']):?>					
			
				<?endif;?>
				
				
				
				
					
					<!--a class="btn btn-default white btn-sm" href="#callbackwidget">
						Заказать звонок
					</a-->
				</div>
			</div>
		<?/*phone_office*/?>
		<?/*time_of_work_office*/?>
				<div class="pull-left  hidden-md nopadding">
				<div class="inner-table-block top-description" style="padding:0px !important;"> 
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
		<?/*time_of_work_office*/?>
		
		
	</div>
</div>