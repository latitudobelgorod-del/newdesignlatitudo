<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>

<?if($arResult["ITEMS"]):?>

			<div>
			<div class="row">
		
				<?foreach( $arResult["ITEMS"] as $arItem ){
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
					?>
					
					<div id="<?=$this->GetEditAreaId($arItem['ID']);?>">
					<?if($arItem['PROPERTIES']['INSTRUCTIONS']['VALUE']):?>

					<div class="col-md-4 col-sm-6">
	<div class="wraps">
	
		
		<div class="files_block">
		
		
		
				<?foreach($arItem['PROPERTIES']['INSTRUCTIONS']['VALUE'] as $arItemO):?>
					
						<?$arFile=CNext::GetFileInfo($arItemO);?>
						<div class="file_type clearfix <?=$arFile["TYPE"];?>">
							<i class="icon"></i>
							<div class="description" itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue">
								<meta itemprop="name" content="<?=$arItem["NAME"]?>">
								<link itemprop="url" href="<?=$arFile["SRC"];?>">
								<a target="_blank" href="<?=$arFile["SRC"];?>" class="dark_link"><?=$arItem["NAME"]?></a>
								<span class="size">
									<?=$arFile["FILE_SIZE_FORMAT"];?>
								</span>
							</div>
						</div>
					
				<?endforeach;?></div>
			</div>
		</div>

			<?endif;?>
			</div>
				<?}?>
		
		
	</div>	</div>
<?endif;?>

