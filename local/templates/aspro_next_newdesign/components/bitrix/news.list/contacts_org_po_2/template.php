<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

<?global $arRegion ,$arTheme, $APPLICATION;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>
<?=bitrix_sessid_post();?>
<?
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
if ($val=='utm_source')
	$utm_source =$v;
}
?>

<div class="news-list-regions  item-views table-type-block table-elements">
	<div class="items row flexbox" style="margin-bottom:40px;">
		
<?foreach($arResult["ITEMS"] as $arItem):?>
	<?
	$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
	         $bImage = $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'];
        $imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : SITE_TEMPLATE_PATH . '/images/noimage.png');
		$res_images = CFile::ResizeImageGet($arItem["PREVIEW_PICTURE"], array("width" => 355, "height" => 286), BX_RESIZE_IMAGE_EXACT, false);
	       $imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
	   ?>

  
	<div class="col-md-6" >
	<div class="item shine shadow slice-item noborder<?= ($bImage ? '' : ' wti') ?><?= ($bActiveDate ? ' wdate' : '') ?>" id="<?=$this->GetEditAreaId($arItem['ID']);?>" >
	
<?$arFilter = Array("IBLOCK_ID"=>7, "ID"=>$arItem['PROPERTIES']['LINK_REGION']['VALUE']);
$res = CIBlockElement::GetList(Array(), $arFilter);
if ($ob = $res->GetNextElement()){;
    $arFields = $ob->GetFields(); // поля элемента
    $arProps = $ob->GetProperties(); // свойства элемента
   }
  // print "<pre>"; print_r($arProps); print "</pre>";

   ?>
  		

<div itemscope itemtype="http://schema.org/Organization">
<meta itemprop="name" content="<?=$arItem['NAME']?>">
<link itemprop="url" href="https://<?=$arProps["MAIN_DOMAIN"]["~VALUE"];?>/contacts/">
	
	<?if (!isMobilelat()):?><div class="col-md-5" >
	
	<?if($imageSrc):?>
							<div class="image" >
								<?if($bDetailLink):?>
									<a href="https://<?=$arProps["MAIN_DOMAIN"]["~VALUE"];?>/contacts/">
								<?endif;?>
								<?if($arItem["PREVIEW_PICTURE"]):?>
								
									<img itemprop="image" src="<?=$res_images["src"]?>"  alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="lazy img-responsive" />
				<?else:?>
										<div style="width: 355px;    height: 200px;    background: url(/images/no_photo_medium.png);    background-size: contain;    background-repeat: no-repeat;    background-position: center;"></div>
									<?endif;?>	
								<?if($bDetailLink):?>
									</a>
								<?endif;?>
								<?if($arParams['SHOW_MORE'] != 'N'):?>
									<a href="https://<?=$arProps["MAIN_DOMAIN"]["~VALUE"];?>/contacts/" class="dark_block_animate">
										<div class="text">
											<div class="cont">
												<div class="titles">
													<div class="text_more"><div class="mores">Перейти</div></div>
												</div>			
											</div>
										</div>
									</a>
								<?endif;?>
							</div>
						<?endif;?>
						
						
	</div>	<?endif;?>
	<div class="col-md-7">
				<div class="body-info">
							<?// element name?>
								<div class="title">
									<a href="https://<?=$arProps["MAIN_DOMAIN"]["~VALUE"];?>/contacts/" class="dark-color">	<?=$arItem['NAME']?>		</a>
								</div>
													<?if ($arItem["DISPLAY_PROPERTIES"]["VIDEO_OFFICE"]):?>
																								<span class="fa fa-play-circle-o" ></span>
													<a class="popup_video _border various video_link" href="<?=$arItem["DISPLAY_PROPERTIES"]["VIDEO_OFFICE"]["VALUE"]?>">видео офиса</a>
			<?endif;?>
			
						<?if( isMobilelat() ):?>
							<?if($imageSrc):?>
									<div class="image" >
										<?if($bDetailLink):?>
											<a href="https://<?=$arProps["MAIN_DOMAIN"]["~VALUE"];?>/contacts/">
										<?endif;?>
										<?if($arItem["PREVIEW_PICTURE"]):?>
										<img itemprop="image" src="<?=$res_images["src"]?>"  alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="lazy img-responsive" />
										<?else:?>
										<div style="width: 355px;    height: 200px;    background: url(/images/no_photo_medium.png);    background-size: contain;    background-repeat: no-repeat;    background-position: center;"></div>
										<?endif;?>	
										<?if($bDetailLink):?>
										</a>
										<?endif;?>
										<?if($arParams['SHOW_MORE'] != 'N'):?>
											<a href="https://<?=$arProps["MAIN_DOMAIN"]["~VALUE"];?>/contacts/" class="dark_block_animate">
												<div class="text">
													<div class="cont">
														<div class="titles">
															<div class="text_more"><div class="mores">Перейти</div></div>
														</div>			
													</div>
												</div>
											</a>
										<?endif;?>
									</div>
						<?endif;?>
						

						
						 <?endif;?>
								
						<div class="title-info">
						<?if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS']) :?>
										<div>
											<span itemprop="address">
												<? if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS']){?> 
												<?$address = ($arItem['PROPERTIES']['ADDRESS']['VALUE'] ? " " . $arItem['PROPERTIES']['ADDRESS']['VALUE'] : "");?>
												<?= html_entity_decode($address) ?>
												<?}?> 
											</span>
										</div>
						<?endif;?>
						<?if (str_contains($utm_source, "ya") || str_contains($utm_source, "tg") || str_contains($utm_source, "vk") || str_contains($utm_source, "maps")) :?> 
								<?if ($arItem["DISPLAY_PROPERTIES"]['PHONE_PODMENA']) :?>
											<div>
												<span>
												<? $href1 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["DISPLAY_PROPERTIES"]["PHONE_PODMENA"]["VALUE"]);?>
												<a itemprop="telephone" rel="nofollow" href="<?=$href1?>"><?=$arItem["DISPLAY_PROPERTIES"]["PHONE_PODMENA"]["VALUE"]?></a><br>
												</span>	
											</div>
								<?else:?>
									 <?if ($arItem["DISPLAY_PROPERTIES"]['PHONE']) :?>
										<div>
												<span>
													<?foreach($arItem["DISPLAY_PROPERTIES"]["PHONE"]["VALUE"] as $value):?>
													<? $dump = preg_replace("/[^0-9]/", '', $value); 
												$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $value);?>
												<a itemprop="telephone" rel="nofollow" href="<?=$href?>"><?=$value?></a><br>

												<?endforeach;?>
												</span>
										</div>
									<?endif;?>
								<?endif;?>
						<?else:?>
									<?if ($arItem["DISPLAY_PROPERTIES"]['PHONE']) :?>
										<div>
												<span>
													<?foreach($arItem["DISPLAY_PROPERTIES"]["PHONE"]["VALUE"] as $value):?>
													<? $dump = preg_replace("/[^0-9]/", '', $value); 
												$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $value);?>
												<a itemprop="telephone" rel="nofollow" href="<?=$href?>"><?=$value?></a><br>

												<?endforeach;?>
												</span>
										</div>
									
									<?endif;?>
						
						<?endif;?>
			
						<?if ($arItem["DISPLAY_PROPERTIES"]['EMAIL']) :?>
						<div>
							<span>
							<a itemprop="email" href="mailto:<?=$arItem['DISPLAY_PROPERTIES']['EMAIL']['DISPLAY_VALUE']?>"><?=$arItem['DISPLAY_PROPERTIES']['EMAIL']['DISPLAY_VALUE']?></a>
							</span>	
						</div>
						<?endif;?>
					
					<?if ($arItem["DISPLAY_PROPERTIES"]['SCHEDULE']) :?>
						<div><?=htmlspecialcharsBack($arItem["DISPLAY_PROPERTIES"]["SCHEDULE"]["VALUE"]["TEXT"])?></div>
					<?endif;?>
					
					<? if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS_SKLAD']):?>
							<hr>
							<div style="font-weight:bold;"><span>Адрес склада</span></div>
							<span>
							<?=$arItem['DISPLAY_PROPERTIES']['ADDRESS_SKLAD']['DISPLAY_VALUE']?>
							</span>	
					<?endif;?>
					
					</div>
					</div>
	
	
	</div>
	</div>
	</div>
	
</div>
<?endforeach;?>
</div>
</div>						