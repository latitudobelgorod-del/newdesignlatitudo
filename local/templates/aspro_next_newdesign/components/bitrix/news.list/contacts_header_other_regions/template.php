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

$REGION_TAG_PHONE_PODP = "#REGION_TAG_PHONEPODP#";
$REGION_TAG_PHONESKLAD = "#REGION_TAG_PHONESKLAD#";
$REGION_TAG_PHONESKLAD_PODP = "#REGION_TAG_PHONESKLAD_PODP#";
$REGION_TAG_LINKVIDEO = "#REGION_TAG_LINKVIDEO#";
$REGION_TAG_MAIL = "#REGION_TAG_MAIL#";

?>

<?=bitrix_sessid_post();?>




<div class="row dop_regions_header">
		<?foreach($arResult["ITEMS"] as $arItem):
		$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']);
		?>
			<div class="main-filials contacts  col-md-5th">
				
				<div class="col" >
					<div class="item">
					<div class="name"><a href="https://<?=$arItem["PROPERTIES"]["MAIN_DOMAIN"]['VALUE']?>"><?=$arItem["NAME"]?></a></div>
														<a rel="nofollow" href="<?=$href?>"><?=$arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']?></a>	<br>

					</div>
				</div>
				
			</div>
		<?endforeach;?>
</div>

