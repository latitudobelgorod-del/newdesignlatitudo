<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>
<?global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');
$REGION_TAG_PHONE = "#REGION_TAG_PHONE#";

?>

<?=bitrix_sessid_post();?>


<div class="mb-12">
		<?foreach($arResult["ITEMS"] as $arItem):
		$href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']);
		?>
					<div><span class="bold"><?=$arItem["NAME"]?></span>
														<a style="margin-left:20px;" rel="nofollow" href="<?=$href?>"><?=$arItem["PROPERTIES"]["REGION_TAG_PHONE"]['VALUE']?></a>

				</div>
	
		<?endforeach;?>
</div>