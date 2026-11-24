<?global $arRegion, $isShowCompany;?>

<?=bitrix_sessid_post();?>


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
				?>
				
				
				
				
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
													<p><span class="fa fa-phone"></span><a rel="nofollow" href="tel:+<?=$dump?>">#REGION_TAG_PHONE#</a></p>
						

				<?/*ЕСЛИ НОМЕР ОБЫЧНЫЙ*/?>
				<?endif;?>
				
				
				
				<div class="block">
					<span class="btn white animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="question" style="width:100%;"><span>Заказать звонок</span></span>
				</div>
								<span class="fa fa-envelope-o"></span><a href="mailto:#REGION_TAG_MAIL#">#REGION_TAG_MAIL#</a>

			<?endif;?>
	</div>
<?endif;?>