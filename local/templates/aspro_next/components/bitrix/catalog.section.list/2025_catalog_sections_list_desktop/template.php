<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<style>
/*front sections*/
.sections_wrapper_catalog_catalog {
    margin: 0px 0px 30px;
}

.sections_wrapper_catalog .list .row > div.front-sect {
    margin-bottom: 20px;
}


.sections_wrapper_catalog .list .item.section_item {
    text-align: left;
  /*  padding-left: 40px;*/
  background-color: #fff;
    height: 100%;
    -webkit-box-shadow: 0 6px 12px -6px rgb(24 39 75 / 12%), 0 8px 24px -4px rgb(24 39 75 / 8%);
    box-shadow: 0 6px 12px -6px rgb(24 39 75 / 12%), 0 8px 24px -4px rgb(24 39 75 / 8%);
padding:30px;
}

.sections_wrapper_catalog .list .item.section_item::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(135deg, #b41818 0%, #901313 100%);
  transform: scaleX(0);
  transition: transform 0.3s ease;
}

.sections_wrapper_catalog .list .item.section_item:hover::before {
  transform: scaleX(1);
}


.sections_wrapper_catalog .list .item.section_item .section_info {
    /* padding-left: 105px; */
}

.section_block .sections_wrapper_catalog .list .row > div {
    margin-bottom: 30px;
}

.section_block .sections_wrapper_catalog .list .item {
    margin: 0px 0px -1px -1px;
}


.sections_wrapper_catalog .list .item .name {
    /*position: absolute;
    bottom: 0px;
    right: 0;
    left: 0; color:#fff;
    /* font-weight:
  bold; */
    /* position: absolute; */
    /* display: flex; */
    /* flex-direction: column; */
    /* justify-content: flex-end; */
    /* padding: 40px 10px; */
    */ /* font-size: 12px; */
    /* line-height: 1.4em; */
    background: linear-gradient(to top, rgba(0, 0, 0, 0.85), transparent);
    /* position: absolute; */
    /* bottom: 0px; */
    /* left: 0px; */
    /* background-color: rgba(0,0,0,.6); */
    /* padding: 15px 15px; */
    /* width: 100%; */
    /* right: 0; */
    padding: 0;
	margin:0 0px 15px 0px;
	line-height:24px;
	font-size:1.4em;
}
.sections_wrapper_catalog .sect {/*padding-left:50px;*/} 
.sections_wrapper_catalog .list .item .name a {
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    font-size: 18px !important;
    font-weight: bold;
    text-align: left;
}

.section_info .dark_link span {
    color: #222222 !important;
}

</style>
<h1 id="pagetitle">Каталог ДПК</h1>
<?if($arResult["SECTIONS"]){?>
<div class="sections_wrapper_catalog">
<div class="list items">
	
		<?foreach(array_chunk($arResult['SECTIONS'], 3) as $arSect):
			
			$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_EDIT"));
			$this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));?>
			
			<div class="row margin0 flexbox">
				  <?foreach ($arSect as $arSection):?>
			<div class="col-md-4 col-sm-4 col-xs-12 front-sect">
			
		<div class="section_item item" id="<?=$this->GetEditAreaId($arSection['ID']);?>">
				<div class="">
					<div>
		
						<div class="section_info">
							<div>
								<div class="name">
									<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="dark_link"><span><?=$arSection["NAME"]?></span></a>
								</div>
								<?if($arSection["SECTIONS"]){
									foreach( $arSection["SECTIONS"] as $arItem ){?>
										<div class="sect"><a href="<?=$arItem["SECTION_PAGE_URL"]?>" class="dark_link"><?=$arItem["NAME"]?><? echo $arItem["ELEMENT_CNT"]?'&nbsp;<span>'.$arItem["ELEMENT_CNT"].'</span>':'';?></a></div>
									<?}
								}?>
							</div>
						</div>
					</div>
					<?if($arParams["SECTIONS_LIST_PREVIEW_DESCRIPTION"]!="N"):?>
						<?$arSection = $section=CNextCache::CIBlockSection_GetList(array('CACHE' => array("MULTI" =>"N", "TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), array('GLOBAL_ACTIVE' => 'Y', "ID" => $arSection["ID"],
						"IBLOCK_ID" => $arParams["IBLOCK_ID"]), false, array("ID", $arParams["SECTIONS_LIST_PREVIEW_PROPERTY"]));?>
						<?if ($arSection[$arParams["SECTIONS_LIST_PREVIEW_PROPERTY"]]):?>
							<div><div class="desc" <?=($collspan? 'colspan="'.$collspan.'"':"");?>><span class="desc_wrapp"><?=$arSection[$arParams["SECTIONS_LIST_PREVIEW_PROPERTY"]]?></span></div></div>
						<?else:?>
							<div><div class="desc" <?=($collspan? 'colspan="'.$collspan.'"':"");?>><span class="desc_wrapp"><?=$arSection["DESCRIPTION"]?></span></div></div>
						<?endif;?>
					<?endif;?>
				</div>
			</div>
			</div>
			<?endforeach;?>
			</div>
		<?endforeach;?>
</div>
</div>
<?}?>