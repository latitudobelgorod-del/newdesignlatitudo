<?
global $arTheme, $arRegion, $APPLICATION;
$logoClass = ($arTheme['COLORED_LOGO']['VALUE'] !== 'Y' ? '' : ' colored');
$regionID = ($arRegion ? $arRegion['ID'] : '');
$REGION_TAG_NAME = "#REGION_TAG_NAME#";
$REGION_TAG_ADDRESSMY = "#REGION_TAG_ADDRESSMY#";
$REGION_TAG_PHONE = "#REGION_TAG_PHONE#";

$REGION_TAG_TIME = "#REGION_TAG_TIME#";
$REGION_TAG_USE_NUMBERS_PHONE = "#REGION_TAG_USE_NUMBERS_PHONE#";
$REGION_TAG_PHONE_8800 = "#REGION_TAG_PHONE_8800#";
$REGION_TAG_PHONE_MOBILE = "#REGION_TAG_PHONE_MOBILE#";
?>
<?=bitrix_sessid_post();?>


<div class="mobileheader-v1">
	<div class="burger pull-left">
		<?=CNext::showIconSvg("burger dark", SITE_TEMPLATE_PATH."/images/svg/Burger_big_white.svg");?>
		<?=CNext::showIconSvg("close dark", SITE_TEMPLATE_PATH."/images/svg/Close.svg");?>
	</div>
	<div class="logo-block pull-left">
		<div class="logo<?=$logoClass?>">
			<?=CNext::ShowLogo();?>
		</div>
	</div>
	
	<div class="pull-right">
	
	
	<? if ($arRegion['PROPERTY_REGION_TAG_USE_NUMBERS_PHONE_VALUE'] == "Y") : ?>
						  	<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
							<div class="inner-table-block phones" >
							
							
																		<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']):
																		$dump8800 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']);
																		$href8800 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']);
																		?>
																		
																		
																			
																			 <div class="phone"><a rel="nofollow" href="<?=$href8800?>"><?=$REGION_TAG_PHONE_8800?></a></div>
						
																		<?endif;?>
																			
							</div>
							<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
							<?else:?>
							<?/*ЕСЛИ НОМЕР ОБЫЧНЫЙ*/?>
	<div class="inner-table-block phones" >
										<?
										$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
										?>
								<div class="phone"><a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a></div>

	</div>
		<?/*ЕСЛИ НОМЕР ОБЫЧНЫЙ*/?>
	<?endif;?>
	
	
	
	
	
				
			<div class="inner-table-block phones" >
			<a data-name="spbuttonWHATSAPPfizedheadermobile8Sl64782XDMFy" data-event="jqm" data-param-form_id="WHATSAPP" style="color:unset;font-size: 0px;" >
			<img src="/images/icons/header-whatsapp-hover.svg" alt="whatsapp" style="width:25px;">Написать в WhatsApp</a>
			</div>
	</div>
		
</div>
