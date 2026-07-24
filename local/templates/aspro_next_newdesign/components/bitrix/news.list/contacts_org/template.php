<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

<?if ($_SERVER['REQUEST_URI'] !== '/contacts/'):?>
 <? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>
<?// shot top banners start?>



<?// shot top banners end?>

<?global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>
<div class="news-list">

<?foreach($arResult["ITEMS"] as $arItem):?>
	<?
	$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
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


<div class="contacts maxwidth-theme" id="<?=$this->GetEditAreaId($arItem['ID']);?>" itemscope itemtype="http://schema.org/Organization">
	<h1>
				<?=$arItem["NAME"]?>
			</h1>

	<div style="margin-bottom:40px;" class="editor">
        <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
    </div>
	<div class="row" style="margin-bottom:40px;">
		
				

	<div class="col-md-4">
<div class="print-6">
	<?=$arItem["DETAIL_TEXT"]?>
</div>
			<table>
				<tbody>
 <?if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS']) :?>
 <tr class="print-6">
						<td align="left" valign="top"><i class="fa big-icon fa-map-marker"></i></td><td align="left" valign="top"><span class="dark_table">Адрес офиса</span>
							<br />
							<span itemprop="address">
							<? if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS']){?> 
						  <?$address = ($arItem['PROPERTIES']['ADDRESS']['VALUE'] ? " " . $arItem['PROPERTIES']['ADDRESS']['VALUE'] : "");?>
						   <?= html_entity_decode($address) ?>
							<?}?> 
							</span>
						</td>
					</tr>
					
					<?endif;?>
					 <?if ($arItem["DISPLAY_PROPERTIES"]['PHONE']) :?>
					<tr class="print-6">
						<td align="left" valign="top"><i class="fa big-icon  fa-phone"></i></td>
						<td align="left" valign="top"> <span class="dark_table">Телефон офиса</span>
							<br />
							<span itemprop="telephone">

							<?foreach($arItem["DISPLAY_PROPERTIES"]["PHONE"]['VALUE'] as $value):?>
							<? $dump = preg_replace("/[^0-9]/", '', $value); 
						$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $value);?>
						<a rel="nofollow" href="<?=$href?>"><?=$value?></a><br>

<?endforeach;?>
						</span>
						</td>
					</tr>
					
						<?endif;?>
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
						
							<?if ($arItem["DISPLAY_PROPERTIES"]['SCHEDULE']) :?>
					<tr class="print-6">
					<td align="left" valign="top"><i class="fa big-icon  fa-clock-o"></i></td>
					<td align="left" valign="top"> <span class="dark_table">Режим работы офиса</span>
					<br />
					<?=htmlspecialcharsBack($arItem["DISPLAY_PROPERTIES"]["SCHEDULE"]["VALUE"]["TEXT"])?>
					</td>
					</tr>
					<?endif;?>
					<? if ($arItem["DISPLAY_PROPERTIES"]['ADDRESS_SKLAD']):?>
					<tr class="print-6">
						<td align="left" valign="top"><i class="sklad_bg"></i></td><td align="left" valign="top"><span class="dark_table">Адрес склада</span>
							<br />
							<span itemprop="address">
						
							<?=$arItem['DISPLAY_PROPERTIES']['ADDRESS_SKLAD']['DISPLAY_VALUE']?>
							
							</span>
						</td>
					</tr>
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
                "PROPERTY_CODE" => "EDITOR2",
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
	
	
	
	

	
	
	
	
	
	
	<?endforeach;?>

</div>


