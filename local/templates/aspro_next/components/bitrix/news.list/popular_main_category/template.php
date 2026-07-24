<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);?>

<?if($arResult['ITEMS']):?>
<div class="items landings_list_inline">

		<div class="wrap">
			<div class="clearfix">
				<?$compare_field = (isset($arParams["COMPARE_FIELD"]) && $arParams["COMPARE_FIELD"] ? $arParams["COMPARE_FIELD"] : "DETAIL_PAGE_URL");
				$bProp = (isset($arParams["COMPARE_PROP"]) && $arParams["COMPARE_PROP"] == "Y");?>
				<?foreach($arResult['ITEMS'] as $arItem):?>
					<?
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));?>
					<div class="item" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
						<div>
							<?if($arItem['PROPERTIES']['FILTER_URL']['VALUE']):?>
								<a href="<?=$arItem['PROPERTIES']['FILTER_URL']['VALUE']?>" ><?=$arItem['NAME']?></a>
							<?else:?>
								<span><?=$arItem['NAME']?></span>
							<?endif?>
						</div>
					</div>

				<?endforeach?>
			</div>
			
		</div>
	
	</div>
<?endif?>
