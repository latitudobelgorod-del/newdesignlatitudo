<div class="sect_wr_cat">
<div class="list items">
		<?foreach(array_chunk($arResult['SECTIONS'], 3) as $arSect):
			//$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_EDIT"));
			//$this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));?>
			<div class="row margin0 flexbox">
					<?foreach ($arSect as $arSection):?>
					<div class="col-md-4 col-sm-4 col-xs-12 front-sect col">
						<div class="item components-demo" id="<?=$this->GetEditAreaId($arSection['ID']);?>">
									<?if($arSection["PICTURE"]["SRC"]):?>
									<?$img = CFile::ResizeImageGet($arSection["PICTURE"]["ID"], array( "width" => 440, "height" => 440 ), BX_RESIZE_IMAGE_EXACT, true );?>
										<div class="image shine">
										<a href="<?=$arSection['SECTION_PAGE_URL']?>">
										<img class="lazy img-responsive" src="/assets/lazyload/loading.gif" data-original="<?=$img["src"]?>" 
										alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" 
										title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" />
										</a>
										</div>
									<?endif;?>
										<div class="name" >
											<div class="blk"> 
											<div class="left col-md-6 col-sm-12"><a href="<?=$arSection['SECTION_PAGE_URL'];?>" class="white_link" ><?=$arSection['NAME']?></a></div>
											<div class="right col-md-6 col-sm-12"><a  class="sect_button animate-load white btn-default btn" href="<?=$arSection['SECTION_PAGE_URL']?>">Смотреть</a></div>
											</div>
										</div>
						</div>
					</div>
					<?endforeach;?>
			</div>
		<?endforeach;?>
	
</div>
</div>