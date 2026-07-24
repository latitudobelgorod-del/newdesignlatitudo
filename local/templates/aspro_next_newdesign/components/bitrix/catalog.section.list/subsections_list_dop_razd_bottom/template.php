<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? global $arSectionBottomPlace; ?>
<? $this->setFrameMode( true ); ?>
<style>
.section-compact-list .row.margin0>div[class*=col-] {
    padding: 0;
    margin: 0 0 -1px -1px
}

.section-compact-list__info {
    padding: 0 0 0 20px;
    line-height: 20px
}
a.section-compact-list__link {font-weight:700;}
.section-compact-list__item {
    padding: 24px 25px;
}

.bordered {text-align: center;
    border: 1px solid #f2f2f2;
    padding: 30px 10px 30px;
    transition: all ease .2s}

.bordered:hover{
    border-color: #fff;
    box-shadow: 0 0 20px 0 rgba(0,0,0,.15)
}



</style>
<?if($arResult["SECTIONS"]){?>
<div class="section_block" style="margin:30px 0;">
<div class="section-compact-list">
		<?foreach(array_chunk($arResult['SECTIONS'], 3) as $arSect):
			//$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_EDIT"));
			//$this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));?>
			<div class="row margin0 flexbox">
			<?foreach ($arSect as $arSection):?>
			<div class="col-md-4 col-sm-4 col-xs-12">
<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="section-compact-list__link dark_link option-font-bold">
				<div class="section-compact-list__item item bordered box-shadow flexbox <?=(empty($arSection["PICTURE"]["SRC"]) ? 'noimng' : '')?>" id="<?=$this->GetEditAreaId($arSection['ID']);?>">
					<div class="section-compact-list__info">
						
						<?=$arSection['NAME'];?>
						
					</div>	
				</div>
				</a>
			</div>
			<?endforeach;?>
			</div>
			<?endforeach;?>
</div>	
</div>

<?}?>
