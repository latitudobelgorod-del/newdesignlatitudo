<?global $arRegion, $isShowCompany;?>

<?=bitrix_sessid_post();?>


<?if($isShowCompany):?>
		<?if($arRegion):
			$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
			?>
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
												
																															<p ><span class="fa fa-phone" style="padding-right:10px;"></span><a rel="nofollow" href="tel:+<?=$dump?>">#REGION_TAG_PHONE#</a></p>
															

														
														<div class="block" style="margin-top:10px;">
														<span class="btn white animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="question" style="width:100%;"><span>Заказать звонок</span></span>
														</div>
														<div>Скидывайте информацию для расчета на почту</div>	
																												<span class="fa fa-envelope-o" style="padding-right:10px;"></span><a href="mailto:#REGION_TAG_MAIL#">#REGION_TAG_MAIL#</a>

										
							</div>
					</div>
				</div>
		<?endif;?>						
<?endif;?>