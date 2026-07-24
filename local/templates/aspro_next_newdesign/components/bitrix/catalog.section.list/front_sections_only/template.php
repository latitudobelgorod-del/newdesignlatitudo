<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
 ?>
<? $this->setFrameMode( true );?>
<? global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>

<?if($arResult['SECTIONS']):?>
		
	<div class="sections_wrapper">
		<?if($arParams["TITLE_BLOCK"] || $arParams["TITLE_BLOCK_ALL"]):?>
			<div class="top_block">
				<h3 class="title_block"><?=$arParams["TITLE_BLOCK"];?></h3>
				<a href="<?=SITE_DIR.$arParams["ALL_URL"];?>"><?=$arParams["TITLE_BLOCK_ALL"] ;?></a>
			</div>
		<?endif;?>
		<div class="list items" style="margin-top:30px;">
			<div class="row margin0 flexbox">
				<?foreach($arResult['SECTIONS'] as $arSection):
					$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "SECTION_EDIT"));
					$this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "SECTION_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_SECTION_DELETE_CONFIRM')));?>
					
					<?        $db_list = CIBlockSection::GetList(Array($by=>$order), $arFilter = Array("IBLOCK_ID"=>$arSection["IBLOCK_ID"], 
"ID"=>$arSection["ID"]), true,$arSelect=Array("UF_LINK_REGION")); 
$my_arr=array();         
		 while($ar_result = $db_list->GetNext()):   
          ?> 
          <?foreach($ar_result["UF_LINK_REGION"] as $link):?> 
		  <?$my_arr[] = $link;?> 
         
	       <?endforeach?> 
          <?endwhile?>
		  
		   
		  <div class="col-md-6 col-sm-6 col-xs-12" >
		   <?if (in_array($regionID, $my_arr)) :?>
						<div class="item" id="<?=$this->GetEditAreaId($arSection['ID']);?>" style="">
							<?if ($arParams["SHOW_SECTION_LIST_PICTURES"]!="N"):?>
								<div class="img shine">
									<?if($arSection["PICTURE"]["SRC"]):?>
										<?$img = CFile::ResizeImageGet($arSection["PICTURE"]["ID"], array( "width" => 600, "height" => 350 ), BX_RESIZE_IMAGE_EXACT, true );?>
										<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img  src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" /></a>
									<?elseif($arSection["~PICTURE"]):?>
										<?$img = CFile::ResizeImageGet($arSection["~PICTURE"], array( "width" => 600, "height" => 350 ), BX_RESIZE_IMAGE_EXACT, true );?>
										<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img  src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" /></a>


<?else:?>
							<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img src="<?=SITE_TEMPLATE_PATH?>/images/no_photo_medium.jpg" alt="<?=$arSection["NAME"]?>" title="<?=$arSection["NAME"]?>"  /></a>
						<?endif;?>

								</div>


							<?endif;?>
							
							<div class="name" >
							<div class="blk">
								<div class="left col-md-6 col-sm-12" ><a href="<?=$arSection['SECTION_PAGE_URL'];?>" class="white_link" ><?=$arSection['NAME'];?></a></div>
<div class="right col-md-6 col-sm-12" ><a  class="sect_button animate-load white btn-default btn" href="<?=$arSection['SECTION_PAGE_URL'];?>" class="">Смотреть фото и цены</a></div>
						</div>
							
							</div>
							
						


							
						
						</div>
				<?endif;?>
					
						
						
						
						
						
					</div>
				<?endforeach;?>
			</div>
		</div>
		
	</div>
<?endif;?>