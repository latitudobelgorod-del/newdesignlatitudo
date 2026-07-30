<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);?>

<?if($arResult['ITEMS']):?>
	<?
	$compare_field = (isset($arParams["COMPARE_FIELD"]) && $arParams["COMPARE_FIELD"] ? $arParams["COMPARE_FIELD"] : "DETAIL_PAGE_URL");
	$bProp = (isset($arParams["COMPARE_PROP"]) && $arParams["COMPARE_PROP"] == "Y");
	$bShowSections = $arParams['USE_LANDINGS_GROUP'] === 'Y';	
	?>
	<div class="items landings_list landings_list--chip landings_list--padding clear">
		<?if($arParams["TITLE_BLOCK"] && !$bShowSections):?>
			<h4><?=$arParams["TITLE_BLOCK"];?></h4>
		<?endif;?>

		<?foreach($arResult['SECTIONS'] as $arSection):?>
			<?$i = 0;?>
			<div class="landings__section">
				<div class="landings__section-items">
					<?if ($arSection['NAME'] && $bShowSections):?>
						<span class="landings__section-title"><?=$arSection['NAME'];?></span>
					<?endif;?>
					
					<?foreach ($arSection['ITEMS'] as $arItem):?>
						<?
						$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
						$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

						++$i;
						$bHidden = $i > $arParams["SHOW_COUNT"] && $arParams["SHOW_COUNT"] >= 1;
						$url = $arItem[$compare_field];
						if ($bProp) {
							$url = $arItem["PROPERTIES"][$compare_field]["VALUE"];
						}
						?>
						<?if (strlen($url)):?>
							<a class="landings__item<?=$bHidden ? ' hidden_items' : '';?><?=$APPLICATION->GetCurDir() === $url ? ' active' : '';?>" href="<?=$url;?>" title="<?=$arItem['NAME']?>"><?=$arItem['NAME'];?></a>
						<?endif?>
					<?endforeach?>

					<?if ($bHidden):?>
						<button type="button" class="landings__show-all more icons_fa" data-opened="N" data-text="<?=GetMessage("HIDE");?>"><?=GetMessage("SHOW_ALL");?></button>
					<?endif?>
				</div>
			</div>
		<?endforeach;?>
	</div>
<?endif?>