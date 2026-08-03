<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>
<style>
.news-list-regions a {color:#222;}
.news-list-regions .title-info {padding:20px 10px; font-size:13px;}
.news-list-regions .title-info div {margin:5px 0;}
</style>
<?global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>
<div class="news-list-regions maxwidth-theme item-views table-type-block table-elements">
	<div class="items row flexbox" style="margin-bottom:40px;">
		
<?foreach($arResult["ITEMS"] as $arItem):?>
	<?
	$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
	         $bImage = $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'];
        $imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : SITE_TEMPLATE_PATH . '/images/noimage.png');
		$res_images = CFile::ResizeImageGet($arItem["PREVIEW_PICTURE"], array("width" => 355, "height" => 200), BX_RESIZE_IMAGE_EXACT, false);
	       $imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
	   ?>

	<div class="col-md-5th">
	<div class="item shine shadow slice-item noborder<?= ($bImage ? '' : ' wti') ?><?= ($bActiveDate ? ' wdate' : '') ?>" id="<?=$this->GetEditAreaId($arItem['ID']);?>" itemscope itemtype="http://schema.org/Organization">
	
<?$arFilter = Array("IBLOCK_ID"=>7, "ID"=>$arItem['PROPERTIES']['LINK_REGION']['VALUE']);
$res = CIBlockElement::GetList(Array(), $arFilter);
if ($ob = $res->GetNextElement()){;
    $arFields = $ob->GetFields(); // поля элемента
    $arProps = $ob->GetProperties(); // свойства элемента
   }
  // print "<pre>"; print_r($arProps); print "</pre>";

   ?>
  					
	
	<?if($imageSrc):?>
							<div class="image">
								<?if($bDetailLink):?>
									<a href="https://<?=$arProps["MAIN_DOMAIN"]["~VALUE"];?>/contacts/">
								<?endif;?>
								<?if($arItem["PREVIEW_PICTURE"]):?>
								
									<img src="<?=$res_images["src"]?>"  alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="lazy img-responsive" />
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
						
								<div class="body-info">
							<?// element name?>
				
								<div class="title">
									<a href="https://<?=$arProps["MAIN_DOMAIN"]["~VALUE"];?>/contacts/" class="dark-color">	<?=$arItem['NAME']?>		</a>
								</div>
									<div class="title-info">
						 <?if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS']) :?>
										<div>
										<span itemprop="address">
							<? if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS']){?> 
						  <?$address = ($arItem['PROPERTIES']['ADDRESS']['VALUE'] ? " " . $arItem['PROPERTIES']['ADDRESS']['VALUE'] : "");?>
						   <?= html_entity_decode($address) ?>
							<?}?> 
							</span></div>
						<?endif;?>
						
						 <?if ($arItem["DISPLAY_PROPERTIES"]['PHONE']) :?>
									<div><span itemprop="telephone">

							<?foreach($arItem["DISPLAY_PROPERTIES"]["PHONE"]['VALUE'] as $value):?>
							<? $dump = preg_replace("/[^0-9]/", '', $value); 
						$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $value);?>
						<a rel="nofollow" href="<?=$href?>"><?=$value?></a><br>

<?endforeach;?>
						</span>	</div>
						
						<?endif;?>
						
						
						
						
										<?if ($arItem["DISPLAY_PROPERTIES"]['EMAIL']) :?>
						<div>
							<span itemprop="email">

<a href="mailto:<?=$arItem['DISPLAY_PROPERTIES']['EMAIL']['DISPLAY_VALUE']?>"><?=$arItem['DISPLAY_PROPERTIES']['EMAIL']['DISPLAY_VALUE']?></a>




 </span>	</div>
						<?endif;?>
					

					<?if ($arItem["DISPLAY_PROPERTIES"]['SCHEDULE']) :?>
						<div><?=htmlspecialcharsBack($arItem["DISPLAY_PROPERTIES"]["SCHEDULE"]["VALUE"]["TEXT"])?></div>
					<?endif;?>
					
					<? if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS_SKLAD']):?>
							<hr>
							<div style="font-weight:bold;"><span>Адрес склада</span></div>
							<span itemprop="address">
							<?=$arItem['DISPLAY_PROPERTIES']['ADDRESS_SKLAD']['DISPLAY_VALUE']?>
							</span>
							
					<?endif;?>
					
					
					</div>

					</div>
						
		
	</div>
</div>
<?endforeach;?>
</div>
</div>


	<?/* отладочный дамп, отключён */ //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xxx1277.txt', print_r($ar_res, 1));?>
							
							