<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

<?global $arTheme, $APPLICATION;
$arRegions = CNextRegionality::getRegions();
$regionID = ($arRegion ? $arRegion['ID'] : '');
if($arRegion)
	$bPhone = ($arRegion['PHONES'] ? true : false);

else
$bPhone = ((int)$arTheme['HEADER_PHONES'] ? true : false);
$REGION_TAG_PHONE = "#REGION_TAG_PHONE#";
$REGION_TAG_PHONE_PODMENA = "#REGION_TAG_PHONE_PODMENA#";
$REGION_TAG_PHONE_PODP = "#REGION_TAG_PHONEPODP#";
$REGION_TAG_PHONESKLAD = "#REGION_TAG_PHONESKLAD#";
$REGION_TAG_PHONESKLAD_PODP = "#REGION_TAG_PHONESKLAD_PODP#";
$REGION_TAG_LINKVIDEO = "#REGION_TAG_LINKVIDEO#";
$REGION_TAG_MAIL = "#REGION_TAG_MAIL#";

?>

<?=bitrix_sessid_post();?>
<?
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
if ($val=='utm_source')
	$utm_source =$v;
}
?>



<div class="row dop_regions_header">
		<?foreach($arResult["ITEMS"] as $arItem):
		$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']);
		$href1 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["PROPERTIES"]["REGION_TAG_PHONE_PODMENA"]['VALUE']);?>				
			<div class="main-filials contacts  col-md-5th">
				
				<div class="col" >
					<div class="item">
					<div class="name"><a href="https://<?=$arItem["PROPERTIES"]["MAIN_DOMAIN"]['VALUE']?>"><?=$arItem["NAME"]?></a></div>
						<?if (str_contains($utm_source, "ya") || str_contains($utm_source, "tg") || str_contains($utm_source, "vk") || str_contains($utm_source, "maps")) :?>
							 
							<?if ($arItem["PROPERTIES"]["REGION_TAG_PHONE_PODMENA"]['VALUE']):?>
								<a rel="nofollow" href="<?=$href1?>"><?=$arItem["PROPERTIES"]["REGION_TAG_PHONE_PODMENA"]['VALUE']?></a>	<br>		
							<?else:?>
								<a rel="nofollow" href="<?=$href?>"><?=$arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']?></a>	<br>
							<?endif;?>
							<?else:?>
								<a rel="nofollow" href="<?=$href?>"><?=$arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']?></a>	<br>
						<?endif;?>
					</div>
				</div>
				
			</div>
		<?endforeach;?>
</div>

