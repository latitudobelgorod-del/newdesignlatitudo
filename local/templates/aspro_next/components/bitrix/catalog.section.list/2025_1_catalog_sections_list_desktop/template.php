<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<style>
/*front sections*/
.sections_wrapper_list {
    margin: 0px 0px 60px;
}

.sections_wrapper_list .list .row > div.front-sect {
   /* margin-bottom: 20px;*/
}


.sections_wrapper_list .list .item.section_item {
    text-align: left;
  margin:0px 0px -1px -1px;
padding:30px;
    line-height: 20px;
    font-size: 13px !important;
    line-height: 1.5em !important;
    padding: 30px 30px;
    zoom:1;vertical-align: top;
    border: 1px solid #f2f2f2;
    transition: box-shadow ease .2s,border ease-out .2s;
    height: 100%;
    
}

.sections_wrapper_list .list .item.section_item:nth-child(2n + 1) {
    clear: left;
}

.sections_wrapper_list .list .row > div.front-sect {padding:0;}
.sections_wrapper_list .list .item.section_item {line-height: 20px;
    font-size: 12px;
    padding: 30px 30px;
    zoom:1;vertical-align: top;
    border: 1px solid #f2f2f2;
    transition: box-shadow ease .2s,border ease-out .2s;}

.section_block .sections_wrapper_list .list .row > div {
    margin-bottom: 30px;
}

.section_block .sections_wrapper_list .list .item {
    margin: 0px 0px -1px -1px;
}


.sections_wrapper_list .list .item .name {
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
.sections_wrapper_list .sect {/*padding-left:50px;*/} 
.sections_wrapper_list .list .item .name a {
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
.section_info a {
    color: #666666 !important;
}
.section_info a:hover {
    color: #9e1414 !important;
}


</style>
<h1 id="pagetitle">Каталог ДПК</h1>
<?//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xxx250.txt', print_r($arResult));?>
<?if($arResult["SECTIONS"]){?>
<div class="sections_wrapper_list">
<div class="list items">
	
		<? 
		// Разбиваем разделы на chunks по 3
		$chunks = array_chunk($arResult['SECTIONS'], 3);
		$totalChunks = count($chunks);
		$chunkIndex = 0;
		
		foreach($chunks as $arSect):
			$chunkIndex++;
			$isLastChunk = ($chunkIndex == $totalChunks);
		?>
			
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
								<? if ($arItem["UF_SECTION_IN_MENU"] == "1"): ?>	
								<div class="sect"><a href="<?=$arItem["SECTION_PAGE_URL"]?>" class="dark_link"><?=$arItem["NAME"]?><? echo $arItem["ELEMENT_CNT"]?'&nbsp;<span>'.$arItem["ELEMENT_CNT"].'</span>':'';?></a></div>	
								<?else:?>
								<? endif; ?>
								<?}
								}?>
							</div>
								
									<?$fSections_sect = CIBlockSection::GetList(Array($by=>$order), $arFilter = Array("IBLOCK_ID"=>19, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', "ID"=>$arSection["ID"]), true,$arSelect=Array("UF_MENULINK_TOP", "UF_SECTION_IN_MENU"));
									 while($ar_result = $fSections_sect->GetNext()):   
									?> 
									<?foreach($ar_result["UF_MENULINK_TOP"] as $ankor):?> 
									<div class="sect dark_link" ><?= html_entity_decode($ankor) ?></div>
									<?endforeach?> 
									<?endwhile?>
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
			<?//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xxx14414141.txt', print_r($arSection));?>

			</div>
			<?endforeach;?>
			
			<? // Добавляем "псевдо-раздел" в последний ряд
			if ($isLastChunk): ?>
			<div class="col-md-4 col-sm-4 col-xs-12 front-sect">
				<div class="section_item item" id="bx_1847241719_503">
					<div class="section_info">
						<div>
							<div class="name">
								<a href="/materials/umnaya-pergola-3kh3-s-mebelyu-i-led-podsvetkoy-gotovyy-komplekt-dlya-idealnogo-otdykha/" class="dark_link"><span>Перголы</span></a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<? endif; ?>
			
			</div>
		<?endforeach;?>
		
</div>
<?}?>