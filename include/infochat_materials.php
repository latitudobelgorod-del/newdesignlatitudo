<?global $arRegion, $isShowCompany;?>

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

<div class="zak_rascket_head">Получите бесплатную консультацию</div>
<div>Свяжитесь с  нами сейчас!</div>
<?if($isShowCompany):?>
	<div class="inf">
		<?
		$res = CIBlockElement::GetByID($arRegion["ID"]);
		if($ar_res = $res->GetNext())
		$URL = CFile::GetPath($ar_res['PREVIEW_PICTURE']);?> 
		<img  alt="#REGION_TAG_DIRECTOR#" src="<?=$URL;?>"/>
		<p class="name_dir">#REGION_TAG_DIRECTOR#</p>
			<?if($arRegion):
				$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
				$dump1 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);?>
				
				
				
				
				<? if ($arRegion['PROPERTY_REGION_TAG_USE_NUMBERS_PHONE_VALUE'] == "Y") : ?>
						  	<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
				
																		<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']):
																		$dump8800 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']);
																		$href8800 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']);
																		?>
																		
																			<span class="fa fa-phone"></span><a rel="nofollow" href="<?=$href8800?>">#REGION_TAG_PHONE_8800#</a>
																			 
																		<?endif;?>
															 
															<br>
																
																<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']):
																		$dumpphone_mobile = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']);
																		$hrefphone_mobile = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']);?>				
																<span class="fa fa-phone"></span><a rel="nofollow" href="<?=$hrefphone_mobile?>">#REGION_TAG_PHONE_MOBILE#</a>
																														
																<?endif;?>
																
																
									  	<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>							
																
				
				
				
				  	<?/*ЕСЛИ НОМЕР ОБЫЧНЫЙ*/?>
				
				<?else:?>
						<?if (str_contains($utm_source, "ya") || str_contains($utm_source, "tg") || str_contains($utm_source, "vk") || str_contains($utm_source, "maps")) :?>
							  
							<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
								<p><span class="fa fa-phone"></span><a rel="nofollow" href="tel:+<?=$dump1?>">#REGION_TAG_PHONE_PODMENA#</a></p>
								<?else:?>
								<p><span class="fa fa-phone"></span><a rel="nofollow" href="tel:+<?=$dump?>">#REGION_TAG_PHONE#</a></p>
							<?endif;?>
						<?else:?>
							<p><span class="fa fa-phone"></span><a rel="nofollow" href="tel:+<?=$dump?>">#REGION_TAG_PHONE#</a></p>
						
						<?endif;?>
				<?/*ЕСЛИ НОМЕР ОБЫЧНЫЙ*/?>
				<?endif;?>
				
				
				
				<div class="block">
					<span class="btn white animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="question" style="width:100%;"><span>Заказать звонок</span></span>
				</div>
				<?if ((str_contains($utm_source, "ya") || str_contains($utm_source, "tg") || str_contains($utm_source, "vk") || str_contains($utm_source, "maps")) && $arRegion['PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE']):?>
				<span class="fa fa-envelope-o"></span><a href="mailto:#REGION_TAG_EMAIL_PODMENA#">#REGION_TAG_EMAIL_PODMENA#</a>
				<?else:?>
				<span class="fa fa-envelope-o"></span><a href="mailto:#REGION_TAG_MAIL#">#REGION_TAG_MAIL#</a>
				<?endif;?>
			<?endif;?>
	</div>
<?endif;?>