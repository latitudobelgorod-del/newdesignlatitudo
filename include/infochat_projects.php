<?global $arRegion, $isShowCompany;?>

<?=bitrix_sessid_post();?>
<?
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
if ($val=='utm_source')
	$utm_source =$v;
}
/* 22.09.2026: подмена — как в шапке: только пока идёт рекламный визит (ndShouldReplaceContacts, сутки с перехода);
   метка в сессии живёт 30 дней. 'ya_visit' лишь включает проверки str_contains(..., 'ya') ниже. */
$utm_source = (function_exists('ndShouldReplaceContacts') && ndShouldReplaceContacts()) ? 'ya_visit' : 'empty';
?>

<?if($isShowCompany):?>
		<?if($arRegion):
			$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
			$dump1 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);?>
				<div class="inf">
					<div class="row">
							<div class="col-md-4 col-sm-4 img text-center">
									  <?
									$arSelect1 = Array("ID", "NAME","PREVIEW_PICTURE");
									$res = CIBlockElement::GetList(Array("ID"=>"ASC"), Array("ID"=>$arRegion["ID"], "IBLOCK_ID" => 7, ), false, false, Array("NAME","PREVIEW_PICTURE") );
									$home = array();
											while($ob = $res->GetNextElement()) 
											{
												$arr = $ob->GetFields();
												$home[$arr['ID']] = $arr;
											}
											
									?>
										<? foreach ($home as $vid): ?>
											<?$url = CFile::GetPath($vid["PREVIEW_PICTURE"]);?>
											<img alt="#REGION_TAG_DIRECTOR#" src="<?=$url?>" style="width: 140px;border-radius: 100%;margin-bottom: 15px;margin-top: 15px;margin-bottom: 15px;"/>
										<? endforeach; ?>

									<p style="margin-bottom:0px;"><span style="font-weight:bold;">#REGION_TAG_DIRECTOR#</span></p>
							</div>
												
												
												
							<div class="col-md-8 col-sm-8">
												<div style="font-size: 1.6em;line-height:1.2em; margin: 0px 0 10px; font-weight: bold;color:#383838;">Уточните наличие и условия доставки</div>
												<div>Свяжитесь с  нами сейчас!</div>
												
															<?if (ndShouldReplaceContacts()) :?>
							   
																<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
																	<p><span class="fa fa-phone" style="padding-right:10px;"></span><a rel="nofollow" href="tel:+<?=$dump1?>">#REGION_TAG_PHONE_PODMENA#</a></p>
																	<?else:?>
																	<p ><span class="fa fa-phone" style="padding-right:10px;"></span><a rel="nofollow" href="tel:+<?=$dump?>">#REGION_TAG_PHONE#</a></p>
																<?endif;?>
															<?else:?>
																<p ><span class="fa fa-phone" style="padding-right:10px;"></span><a rel="nofollow" href="tel:+<?=$dump?>">#REGION_TAG_PHONE#</a></p>
															
															<?endif;?>
														
														<div class="block" style="margin-top:10px;">
														<span class="btn white animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="question" style="width:100%;"><span>Заказать звонок</span></span>
														</div>
														<div>Скидывайте информацию для расчета на почту</div>	
														<?if ((ndShouldReplaceContacts()) && $arRegion['PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE']):?>
														<span class="fa fa-envelope-o" style="padding-right:10px;"></span><a href="mailto:#REGION_TAG_EMAIL_PODMENA#">#REGION_TAG_EMAIL_PODMENA#</a>
														<?else:?>
														<span class="fa fa-envelope-o" style="padding-right:10px;"></span><a href="mailto:#REGION_TAG_MAIL#">#REGION_TAG_MAIL#</a>
														<?endif;?>
										
							</div>
					</div>
				</div>
		<?endif;?>						
<?endif;?>