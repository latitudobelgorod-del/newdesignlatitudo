<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>



<?$this->setFrameMode(true);?>
<?global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>
<? if (isset($_REQUEST['PAGEN_1']) && $_REQUEST['PAGEN_1'] > 1): ?>
<? $goy = $arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"];
//$goy = $goy . ' | Страница ' . $_REQUEST["PAGEN_1"];
$APPLICATION->AddHeadString('<meta name="yandex" content="noindex, follow" />',true);
?>
<? else: ?>
<? $goy = $arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"]; ?>
<? endif; ?>






<h1 id="pagetitle"><?= $goy ?></h1>		

		<?//КМ верхний?>
			<? include_once(__DIR__ . "/km_top.php") ?>
		<?//КМ верхний?>	
			
			
			
<?if($arResult['ITEMS']):?>
<div class="item-views table-type-block table-elements <?=$templateName;?>">

	<?// top pagination?>
	<?if($arParams['DISPLAY_TOP_PAGER']):?>
		<?=$arResult['NAV_STRING']?>
	<?endif;?>

		<?
		$bHasSection = false;
		if($arParams['PARENT_SECTION'] && (isset($arResult['SECTIONS']) && $arResult['SECTIONS']))
		{
			if(isset($arResult['SECTIONS'][$arParams['PARENT_SECTION']]) && $arResult['SECTIONS'][$arParams['PARENT_SECTION']])
				$bHasSection = true;
		}
		if($bHasSection)
		{
			// edit/add/delete buttons for edit mode
			$arSectionButtons = CIBlock::GetPanelButtons($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['IBLOCK_ID'], 0, $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'], array('SESSID' => false, 'CATALOG' => true));
			$this->AddEditAction($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['IBLOCK_ID'], 'SECTION_EDIT'));
			$this->AddDeleteAction($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
			?>
			<div class="section" id="<?=$this->GetEditAreaId($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'])?>">
			<?
		}?>
		
		
		<? if (($_SERVER['REQUEST_URI'] !== $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['SECTION_PAGE_URL']) && !(isset($_REQUEST['PAGEN_1']) && $_REQUEST['PAGEN_1'] > 1)): ?>

        <? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
        <? endif; ?>
        <? if (!$bHasSection && $arParams['SET_STATUS_404'] === 'Y'): ?>
            <? CNext::goto404Page(); ?>
        <? endif; ?>


			<div class="items row flexbox portfolio_list">
			<?$arParams['LINE_ELEMENT_COUNT_LIST'] = ($arParams['LINE_ELEMENT_COUNT_LIST'] <=0 ? 3 : $arParams['LINE_ELEMENT_COUNT_LIST']);?>
			<?foreach($arResult['ITEMS'] as $i => $arItem):?>
				<?
				// edit/add/delete buttons for edit mode
				$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
				$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
				// use detail link?
				$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
				$bImage = $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'];
				$imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : SITE_TEMPLATE_PATH.'/images/noimage.png');
					$res_images = CFile::ResizeImageGet($arItem["PREVIEW_PICTURE"], Array("width" => 355, "height" => 200), BX_RESIZE_IMAGE_EXACT, false);
    
								
				
				$imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
				// show active date period
				$bActiveDate = strlen($arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE']) || ($arItem['DISPLAY_ACTIVE_FROM'] && in_array('DATE_ACTIVE_FROM', $arParams['FIELD_CODE']));

				$class = '';
				if(isset($arItem['SECTIONS']) && $arItem['SECTIONS'])
				{
					foreach($arItem['SECTIONS'] as $id => $name)
					{
						$class .= ' s-'.$id;
					}
				}
				?>
				   <div class="col-md-3 col-sm-3 col-xs-12 <?= $class; ?>" data-ref="mixitup-target">
		           <div class="item shine shadow slice-item noborder<?= ($bImage ? '' : ' wti') ?><?= ($bActiveDate ? ' wdate' : '') ?>"
					id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
					<? if ($imageSrc): ?>
                    <div class="image">
                    <? if ($bDetailLink): ?>
					<a href="<?= $arItem['DETAIL_PAGE_URL'] ?>"><? endif; ?>
                    <img class="img-responsive" src="<?= $res_images["src"] ?>" alt="<?= ($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME']) ?>" title="<?= ($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME']) ?>">
                    <? if ($bDetailLink): ?>
                    </a>
                    <? endif; ?>

                        <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>" class="dark_block_animate">
                            <div class="text">
                                <div class="cont">
                                    <div class="titles">
                                        <div class="text_more">
                                            <div class="mores"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
				<? if ($arItem['PROPERTIES']['SET_BRAND']['VALUE']): ?>
                 <div class="<?=$arItem['PROPERTIES']['SET_BRAND']['VALUE_XML_ID']?>"><?=$arItem['PROPERTIES']['SET_BRAND']['VALUE']?></div>
               <?endif;?>
                    </div>
                <? endif; ?>

                <div class="body-info">

                    <?
//                    echo "<pre>"; print_r($arItem['PROPERTIES']['REVIEW']); echo "</pre>";
//                    echo "<pre>"; var_dump(isset($arItem['PROPERTIES']['REVIEW'])); echo "</pre>";
                    ?>
                    <? if (((isset($arItem['PROPERTIES']['REVIEW']) && is_array($arItem['PROPERTIES']['REVIEW']['VALUE']) && $arItem['PROPERTIES']['REVIEW']['~VALUE']['TEXT'])) || ($arItem['PROPERTIES']['LINK_YOUTUBE']['VALUE']) || ($arItem['PROPERTIES']['GALLEY_BIG']['VALUE'])): ?>
                        <div class="stickers">

                            <? /*if($arItem['PROPERTIES']['LINK_YOUTUBE']['VALUE']):?>
					<div class="sticker_video"><div class="fa-caret" ><i class="fa fa-caret-right"></i></div>Есть видео</div>
					<?endif;*/ ?>
                            <? if ($arItem['PROPERTIES']['VIDEO']['VALUE']): ?>
                                <div class="sticker_video">
                                    <div class="fa-caret"><i class="fa fa-caret-right"></i></div>
                                </div>
                            <? endif; ?>


                            <? $ph = count($arItem["PROPERTIES"]["GALLEY_BIG"]["VALUE"]); ?>
                            <? $arr_count[$arItem['ID']] = $ph; ?>
                            <? if ($arItem['PROPERTIES']['GALLEY_BIG']['VALUE']): ?>
                                <div data-id="<?= $arItem['ID'] ?>" class="sticker_photo"></div>
                            <? endif; ?>

                            <? if ((isset($arItem['PROPERTIES']['REVIEW']) && is_array($arItem['PROPERTIES']['REVIEW']['VALUE']) && $arItem['PROPERTIES']['REVIEW']['~VALUE']['TEXT'])): ?>
                                <div class="sticker_review"></div>
                            <? endif; ?>

                        </div>
                    <? endif; ?>
                    <? // element name?>
                    <? if (strlen($arItem['FIELDS']['NAME'])): ?>
                        <div class="title">
                            <? if ($bDetailLink): ?>
                            <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>" class="dark-color"><? endif; ?>
                                <?= $arItem['NAME'] ?>
                                <? if ($bDetailLink): ?>

                            </a><? endif; ?>
                        </div>
                    <? endif; ?>
                </div>
            </div>
				</div>
			
				
			<?endforeach;?>
		</div>
		




		<?if($bHasSection):?>
			</div>
		<?endif;?>


	<?// bottom pagination?>
	<?if($arParams['DISPLAY_BOTTOM_PAGER']):?>
		<div class="bottom_nav"><?=$arResult['NAV_STRING']?></div>
	<?endif;?>
	
		</div>
	
<?endif;?>


		
		
		
		<?//КМ нижний?>
			<? include_once(__DIR__ . "/km_bottom.php") ?>
		<?//КМ нижний?>	
		
		 <? $json = json_encode($arr_count); ?>
		<script type="text/javascript">
        let name = <?= $json ?>;
        for (const i in name) {
            $('.sticker_photo[data-id=' + i + ']').text(name[i] + ' фото');
        }
		</script>

