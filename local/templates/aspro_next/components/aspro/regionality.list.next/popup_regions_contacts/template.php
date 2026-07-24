<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
$this->setFrameMode(true);
use \Bitrix\Main\Localization\Loc;
if($arResult['CURRENT_REGION'])
{?>
	<?if(!$arResult['POPUP']):?>
		<?global $arTheme;?>
		<div class="region_wrapper">

			<div class="city_title"><?=Loc::getMessage('CITY_TITLE');?></div>
			<div class="js_city_chooser colored" data-event="jqm" data-name="city_chooser" data-param-url="<?=urlencode($APPLICATION->GetCurUri());?>" data-param-form_id="city_chooser">
				<span class="fa fa-map-marker"></span>  <span><?=$arResult['CURRENT_REGION']['NAME'];?></span><span class="arrow"><i></i></span>
			</div>
			<?if($arResult['SHOW_REGION_CONFIRM']):?>
				<div class="confirm_region">
<a href="#" class="close_popup js-close-popup"><i></i></a>
					<?
					$href = 'data-href="'.$arResult['REGIONS'][$arResult['REAL_REGION']['ID']]['URL'].'"';
					if($arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_TYPE']['VALUE'] == 'SUBDOMAIN' && ($arResult['HOST'].$_SERVER['HTTP_HOST'].$arResult['URI'] == $arResult['REGIONS'][$arResult['REAL_REGION']['ID']]['URL']))
					$href = '';?>
					<div class="title"><?=Loc::getMessage('CITY_TITLE');?> <?=$arResult['REAL_REGION']['NAME'];?> ?</div>
					<div class="buttons">
						<span class="btn btn-default aprove" data-id="<?=$arResult['REAL_REGION']['ID'];?>" <?=$href;?>><?=Loc::getMessage('CITY_YES');?></span>
						<span class="btn btn-default white js_city_change"><?=Loc::getMessage('CITY_CHANGE');?></span>
					</div>
				</div>
			<?endif;?>
		</div>
	<?else:?>
		<div class="popup_regions">
		<div class="favorits_3city h-search">	
		<?if($arResult['FAVORITS']):?>
				<?$count=0;?>
					<div class="row flexbox">
							<?foreach($arResult['FAVORITS'] as $arItem):?>
							<?$bCurrentFavourites = ($arResult['CURRENT_REGION']['ID'] == $arItem['ID']);?>						
														
										
							<?if ($count < 4):?>
							
							
							
								<div class="col-md-3 shadow ">

								<div class="item fav_links" onclick='window.location.href="<?=$arItem['URL'];?>"' >
								<div class="name"><span><?=$arItem['NAME'];?></span><span class="<?=($bCurrentFavourites ? 'current_shownf' : '');?>"></span></div>
									<div class="phone"><?=$arItem['PHONES']['0'];?></div>
									<div class="address"><?=$arItem['PROPERTY_REGION_TAG_ADDRESSMY_VALUE'];?></div>
									<div class="row flexbox">
									<ul>
									<li><i class="fa fa-check "></i>Есть шоу-рум</li>
									<li><i class="fa fa-check "></i>Выезд на замер</li>
									<li><i class="fa fa-check "></i>Монтаж под ключ</li>
									</ul>
									</div>
									
								</div>
							
								<?$count=$count+1;?>
								</div>
								<?endif;?>
								<?endforeach;?>
								
								<?file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xxx458.txt', print_r($arItem, 1));?>
</div>
				<?endif;?>
			
		</div>

				<script>
				var arRegions = <?=CUtil::PhpToJsObject($arResult['JS_REGIONS']);?>
			</script>
		</div>
	<?endif;?>
<?}?>
<script>
    $('.js-close-popup').click(function () {
        $('.confirm_region').hide();
    });
	$(".current_shownf").text("выбран сейчас");
</script>