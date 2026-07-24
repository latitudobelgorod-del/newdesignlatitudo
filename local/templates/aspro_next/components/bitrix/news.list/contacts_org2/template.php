<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>
<?=bitrix_sessid_post();?>
<?
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
if ($val=='utm_source')
	$utm_source =$v;
}
?>
<?if ($_SERVER['REQUEST_URI'] !== '/contacts/'):?>
 <? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>
<?// shot top banners start?>



<?// shot top banners end?>

<?global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>


<?foreach($arResult["ITEMS"] as $arItem):?>
	<?
	$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
	  $bImage = $arItem['FIELDS']['DETAIL_PICTURE']['SRC'];
        $imageSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : SITE_TEMPLATE_PATH . '/images/noimage.png');
		$res_images = CFile::ResizeImageGet($arItem["DETAIL_PICTURE"], BX_RESIZE_IMAGE_PROPORTIONAL, true); 
	       
	?>

<?$arFilter = Array("IBLOCK_ID"=>7, "ID"=>$arItem['PROPERTIES']['LINK_REGION']['VALUE']);
$res = CIBlockElement::GetList(Array(), $arFilter);
if ($ob = $res->GetNextElement()){;
    $arFields = $ob->GetFields(); // поля элемента
    $arProps = $ob->GetProperties(); // свойства элемента
   }
  // print "<pre>"; print_r($arProps); print "</pre>";

   ?>
  		

	<?if($arItem["PROPERTIES"]["BNR_TOP"]["VALUE"]):?>

	
	<?if( isMobilelat() ):?>
<?// shot top banners start?>
	<?$this->SetViewTarget("section_bnr_content");?>
		<?CNext::ShowTopDetailBanner($arItem, $arParams);?>
	<?$this->EndViewTarget();?>

<?// shot top banners end?>
 <?endif;?>	
 <?endif;?>	

<div itemscope itemtype="http://schema.org/Organization">

  
  
<div class="contacts maxwidth-theme" id="<?=$this->GetEditAreaId($arItem['ID']);?>" >
	
	<?if($arItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"])
		{
		$goy=$arItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"];}
		else {
		$goy=$arItem['NAME'];		
		}
		?>

		<h1 id="pagetitle"><?=$goy?></h1>


<meta itemprop="name" content="<?=$goy?>">
<link itemprop="logo" href="/images/company/logo.svg">
 <link itemprop="url" href="https://<?=$arProps["MAIN_DOMAIN"]["~VALUE"];?>/contacts/"> 
<span  style="display:none;"><img itemprop="image" src="<?=$res_images["src"]?>"  alt="<?=($bImage ? $arItem['DETAIL_PICTURE']['ALT'] : $arItem['NAME'])?>" 
									title="<?=($bImage ? $arItem['DETAIL_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="lazy img-responsive" /></span>

	<div class="row" style="margin-bottom:40px;">
	
<div class="col-md-4">
<div class="print-6">
	<?=$arItem["DETAIL_TEXT"]?>
</div>
			<table>
			<tbody>
 <?if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS']) :?>
 <tr class="print-6">
						<td align="left" valign="top"><i class="fa big-icon fa-map-marker"></i></td>
						<td align="left" valign="top"><span class="dark_table">
						
									
						Адрес офиса
						</span>
							<br />
							<span itemprop="address">
							<? if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS']){?> 
						  <?$address = ($arItem['PROPERTIES']['ADDRESS']['VALUE'] ? " " . $arItem['PROPERTIES']['ADDRESS']['VALUE'] : "");?>
						   <?= html_entity_decode($address) ?>
							<?}?> 
							</span>
							<?if ($arItem["ID"] == 22068):?>
							<div style="margin-top:15px;margin-bottom:15px;">
<div style="font-size: 12px;line-height: 18px;color:#999999;">Для пропуска нужен паспорт или права</div><a data-event="jqm" data-param-form_id="PROPUSK" data-name="spbuttonPROPUSKcontactIHud64541l64782XDMFi" class="whatsap" style="margin-left:0;">Заказать пропуск</a></div>
							<?endif;?>
							
						</td>
							<?if ($arItem["DISPLAY_PROPERTIES"]['EMAIL']) :?>
					<tr class="print-6">
						<td align="left" valign="top"><i class="fa big-icon  fa-envelope"></i></td><td align="left" valign="top"> <span class="dark_table">E-mail</span>
							<br />
							<span itemprop="email">

<a href="mailto:<?=$arItem['DISPLAY_PROPERTIES']['EMAIL']['DISPLAY_VALUE']?>"><?=$arItem['DISPLAY_PROPERTIES']['EMAIL']['DISPLAY_VALUE']?></a>




 </span>
						</td>
					</tr>
						<?endif;?>
					</tr>
					
					<?endif;?>
					 <?if ($arItem["DISPLAY_PROPERTIES"]['PHONE']) :?>
					<tr class="print-6">
						<td align="left" valign="top"><i class="fa big-icon  fa-phone"></i></td>
						<td align="left" valign="top"> <span class="dark_table">
						
							<?if (($arItem["ID"] == 22055) || ($arItem["ID"] == 22058)):?>
						Телефон
						<?else:?>				
						Телефон офиса
						<?endif;?>
						 </span>
							<br />
							
							
						<? if ($arItem["DISPLAY_PROPERTIES"]['USE_NUMBERS_PHONE']['VALUE'] == "Y") : ?>
						  			<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
										
															<?if ($arItem["DISPLAY_PROPERTIES"]['PHONE_8800']['VALUE']) :	
															$href1 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["DISPLAY_PROPERTIES"]["PHONE_8800"]['VALUE']);
															?>
															<span >
																<a itemprop="telephone" rel="nofollow" href="<?=$href1?>"><?=$arItem["DISPLAY_PROPERTIES"]['PHONE_8800']['VALUE']?></a><br>
															</span>
															<?endif;?>
															
												<?if ($arItem["DISPLAY_PROPERTIES"]['PHONE_MOBILE']['VALUE']) :	
															$href2 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["DISPLAY_PROPERTIES"]["PHONE_MOBILE"]['VALUE']);
															?>
															<span >
																<a itemprop="telephone"rel="nofollow" href="<?=$href2?>"><?=$arItem["DISPLAY_PROPERTIES"]['PHONE_MOBILE']['VALUE']?></a><br>
															</span>
															<?endif;?>
											
							<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
							<?else:?>
				
							<?/*ЕСЛИ НОМЕР ОБЫЧНЫЙ*/?>
												

	<?if (str_contains($utm_source, "ya") || str_contains($utm_source, "tg") || str_contains($utm_source, "vk") || str_contains($utm_source, "maps")) :?>    											
														<?if ($arItem["DISPLAY_PROPERTIES"]['PHONE_PODMENA']['VALUE']) :
															$href1 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["DISPLAY_PROPERTIES"]["PHONE_PODMENA"]['VALUE']);
															?>
															<span >
																<a itemprop="telephone" rel="nofollow" href="<?=$href1?>"><?=$arItem["DISPLAY_PROPERTIES"]['PHONE_PODMENA']['VALUE']?></a><br>
															</span>
																<?else:?>
																
												<?foreach($arItem["DISPLAY_PROPERTIES"]["PHONE"]['VALUE'] as $value):?>
												<? $dump = preg_replace("/[^0-9]/", '', $value); 
											$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $value);?>
											<a rel="nofollow" href="<?=$href?>"><?=$value?></a><br>

											<?endforeach;?>
											</span>
														<?endif;?>
												
												<?else:?>
														
														<span itemprop="telephone">

												<?foreach($arItem["DISPLAY_PROPERTIES"]["PHONE"]['VALUE'] as $value):?>
												<? $dump = preg_replace("/[^0-9]/", '', $value); 
											$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $value);?>
											<a rel="nofollow" href="<?=$href?>"><?=$value?></a><br>

											<?endforeach;?>
											</span>
														
														
														
												
												<?endif;?>
												<?/*ЕСЛИ НОМЕР ОБЫЧНЫЙ*/?>
												<?endif;?>
						</td>
					</tr>
					
						<?endif;?>
					
						
							<?if ($arItem["DISPLAY_PROPERTIES"]['SCHEDULE']) :?>
					<tr class="print-6">
					<td align="left" valign="top"><i class="fa big-icon  fa-clock-o"></i></td>
					<td align="left" valign="top"> <span class="dark_table">	<?if (($arItem["ID"] == 22055) || ($arItem["ID"] == 22058)):?>
						Режим работы
						<?else:?>				
						Режим работы офиса
						<?endif;?></span>
					<br />
					<?=htmlspecialcharsBack($arItem["DISPLAY_PROPERTIES"]["SCHEDULE"]["VALUE"]["TEXT"])?>
					</td>
					</tr>
					<?endif;?>
					
					
					
					
					
					<? if (($arItem["DISPLAY_PROPERTIES"]['ADDRESS_SKLAD']) || ($arItem["DISPLAY_PROPERTIES"]['SCHEDULE_SKLAD']) ):?>
						<? if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS_SKLAD']):?>
					<tr class="print-6 sklad-20">
						<td align="left" valign="top"><i class="sklad_bg"></i></td><td align="left" valign="top"><span class="dark_table">Адрес склада</span>
							<br />
							<span><?=$arItem['DISPLAY_PROPERTIES']['ADDRESS_SKLAD']['DISPLAY_VALUE']?></span>
						</td>
					</tr>
					<?endif;?>
					
					<?if ($arItem["DISPLAY_PROPERTIES"]['SCHEDULE_SKLAD']) :?>
					<tr class="print-6 sklad-20">
					<td align="left" valign="top"><i class="fa big-icon  fa-clock-o"></i></td>
					<td align="left" valign="top"> <span class="dark_table">Режим работы склада</span>
					<br />
					<?=htmlspecialcharsBack($arItem["DISPLAY_PROPERTIES"]["SCHEDULE_SKLAD"]["VALUE"]["TEXT"])?>
					</td>
					</tr>
					<?endif;?>
					
					<?endif;?>
					
				</tbody>
			</table>
		</div>
		
			<div class="col-md-8">
			
			
					<?/*Карта из конструктора*/?>
	
		<?
	$arYmap = array();
	if(isset($arItem["DISPLAY_PROPERTIES"]["YMAP_CONSTR"]["VALUE"])){
		if(is_array($arItem["DISPLAY_PROPERTIES"]["YMAP_CONSTR"]["VALUE"])){
			$arYmap = $arYmap + $arItem["DISPLAY_PROPERTIES"]["YMAP_CONSTR"]["~VALUE"];
		}
		elseif(strlen($arItem["DISPLAY_PROPERTIES"]["YMAP_CONSTR"]["VALUE"])){
			$arYmap[] = $arItem["DISPLAY_PROPERTIES"]["YMAP_CONSTR"]["~VALUE"];
		}
	}
	?>
	
	
	

<?if($arYmap):?>
<div class="wraps hidden_print">
			
			<div class="video_block box_ymap">
				<?if(count($arYmap) > 1):?>
					<table class="video_table">
						<tbody>
							<?foreach($arYmap as $v => $value):?>
								<?if(($v + 1) % 2):?>
									<tr>
								<?endif;?>
								<td width="50%"><?=str_replace('src=', 'width="458" height="257" src=', str_replace(array('width', 'height'), array('data-width', 'data-height'), $value));?></td>
								<?if(!(($v + 1) % 2)):?>
									</tr>
								<?endif;?>
							<?endforeach;?>
							<?if(($v + 1) % 2):?>
								</tr>
							<?endif;?>
						</tbody>
					</table>
				<?else:?>
					<?=$arYmap[0]?>
				<?endif;?>
			</div>
</div>
<?endif;?>
		
	<?/*Карта из конструктора*/?>
	
	
	</div>
		
	</div>
	

	<div class="editor">
        <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1",
		"NEWS_NAME" => $arResult["NAME"],
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
    </div>


<div class="editor">
        <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR2",
		"NEWS_NAME" => $arResult["NAME"],
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
    </div>

	
</div>
</div>
	
<?endforeach;?>



