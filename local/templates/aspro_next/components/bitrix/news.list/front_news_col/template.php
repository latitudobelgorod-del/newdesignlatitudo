<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); 
global $LinkCatalog; 
?>
<?if($arResult["ITEMS"]){?>
	<div class="news_blocks front">
		<div class="top_block">
		<?
		$title_block=($arParams["TITLE_BLOCK"] ? $arParams["TITLE_BLOCK"] : GetMessage('NEWS_TITLE'));
		$title_block_all=($arParams["TITLE_BLOCK_ALL"] ? $arParams["TITLE_BLOCK_ALL"] : GetMessage('ALL_NEWS'));
		$url=($arParams["ALL_URL"] ? $arParams["ALL_URL"] : "/blog/");
		?>
		<p class="h2"><?/*=$title_block;*/?>Полезные советы</p>
		<div class="clearfix"></div>
		</div>
		<div class="tizers_block">
		<div class="row">
				<?foreach($arResult["ITEMS"] as $arItem){
				//$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
				//$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
					?>
			<div class="col-md-3 col-sm-12 col-xs-6">
				<div id="<?=$this->GetEditAreaId($arItem['ID']);?>" class="item box-sizing dl">
					<?if($arItem["PREVIEW_PICTURE"]["SRC"]){?>
						<div class="img">
							<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>
								<a class="name" href="<?=$arItem["PROPERTIES"]["LINK"]["VALUE"]?>">
							<?endif;?>
							<img src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$name;?>" title="<?=$name;?>" class="img-responsive"/>
							<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>
								</a>
							<?endif;?>
						</div>
					<?}?>
					<div class="title"><a class="name dark_link" href="<?=$arItem["DETAIL_PAGE_URL"]?>"><?=$arItem["NAME"]?></a></div>
				</div>
			</div>
				<?}?>
		</div>
		</div>
	</div>
<?}?>