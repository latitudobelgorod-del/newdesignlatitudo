<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>
<?global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');
$REGION_TAG_PHONE = "#REGION_TAG_PHONE#";
$REGION_TAG_PHONE_PODMENA = "#REGION_TAG_PHONE_PODMENA#";
?>

<?=bitrix_sessid_post();?>
<?
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
if ($val=='utm_medium')
	$utm_medium =$v;	
}
?>

<div class="mb-12">
		<?foreach($arResult["ITEMS"] as $arItem):
		$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']);
		$href1 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["PROPERTIES"]["REGION_TAG_PHONE_PODMENA"]['VALUE']);?>										
					<div><span class="bold"><?=$arItem["NAME"]?></span>
						<?if ($utm_medium == "cpc") :?> 
							<?if ($arItem["PROPERTIES"]["REGION_TAG_PHONE_PODMENA"]['VALUE']):?>
								<a style="margin-left:20px;" rel="nofollow" href="<?=$href1?>"><?=$arItem["PROPERTIES"]["REGION_TAG_PHONE_PODMENA"]['VALUE']?></a>			
							<?else:?>
								<a style="margin-left:20px;" rel="nofollow" href="<?=$href?>"><?=$arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']?></a>	
							<?endif;?>
							<?else:?>
								<a style="margin-left:20px;" rel="nofollow" href="<?=$href?>"><?=$arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']?></a>	
						<?endif;?>
				</div>
	
		<?endforeach;?>
</div>