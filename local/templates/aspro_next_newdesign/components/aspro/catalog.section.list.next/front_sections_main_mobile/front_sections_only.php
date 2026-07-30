<style>
 .sec_list{margin-bottom:25px}
.sec_list-wrap{display:-webkit-box;display:-webkit-flex;display:-moz-box;display:-ms-flexbox;display:flex;-webkit-flex-wrap:wrap;-ms-flex-wrap:wrap;flex-wrap:wrap;margin-left:-5px;margin-right:-5px;-webkit-box-align:stretch;-webkit-align-items:stretch;-moz-box-align:stretch;-ms-flex-align:stretch;align-items:stretch;margin-top:4px;}
.sec_list-item{padding:5px;-webkit-box-flex:1;-webkit-flex:1 1 33.3%;-moz-box-flex:1;-ms-flex:1 1 33.3%;flex:1 1 33.3%;max-width:33.3%;text-align:center;}
.sec_list-item-link{color:#000;font-size:1.2rem;line-height:1.4rem;display:block;background-color:#fff;padding:10px;padding-bottom:5px;height:100%;background:#f9f9f9;border:1px solid #f2f2f2 !important;}
.sec_list-item-link:hover{color:#A5000E;border-color:#A5000E}
.sec_list-item-link .icon{display:block;margin-left:auto;margin-right:auto;width:47px;height:47px;position:relative;margin-bottom:7px}
.sec_list-item-link .icon img{display:block;width:100%;height:100%;-o-object-fit:contain;object-fit:contain}
</style>

<div class="sec_list">
<div class="sec_list-wrap">

<div class="sect_wr_cat">
<div class="list items">
		<?foreach(array_chunk($arResult['SECTIONS'], 3) as $arSect):
			//$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_EDIT"));
			//$this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));?>
			<div class="row margin0 flexbox">
					<?foreach ($arSect as $arSection):?>
					
					
			<div class="sec_list-item" id="<?=$this->GetEditAreaId($arSection['ID']);?>">
			<a  href="<?=$arSection['SECTION_PAGE_URL']?>" class="sec_list-item-link">
			<span class="icon">
                    <picture class="loaded">
						<?if($arSection["PICTURE"]["SRC"]):?>
									<?$img = CFile::ResizeImageGet($arSection["PICTURE"]["ID"], array( "width" => 80, "height" => 80 ), BX_RESIZE_IMAGE_EXACT, true );?>
									<img width="80" height="80" src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" />	
								<?elseif($arSection["~PICTURE"]):?>
									<?$img = CFile::ResizeImageGet($arSection["~PICTURE"], array( "width" => 80, "height" => 80 ), BX_RESIZE_IMAGE_EXACT, true );?>
									<img width="80" height="80" src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>"	title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" />
												<?endif;?>
					
                    </picture>
                </span>
				
				<span class="text"><?=$arSection["NAME"]?></span>
					
	
				</a>
			</div>
		
					
				
					
					<?endforeach;?>
			</div>
		<?endforeach;?>
	
</div>
</div></div>
</div>