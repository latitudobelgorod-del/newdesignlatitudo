<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<?if($arResult):?>
	<ul class="left_menu">
		<?foreach($arResult as $arItem):?>
			<?if($arParams["MAX_LEVEL"] == 1 && $arItem["DEPTH_LEVEL"] > 1) continue;?>
			<li class="<?if($arItem["SELECTED"]){?> current <?}?>  item <?=(strlen($arItem["PARAMS"]["class"]) ? $arItem["PARAMS"]["class"] : '')?>">
				<a class="icons_fa" href="<?=$arItem["LINK"]?>">
					<span><?=$arItem["TEXT"]?></span>
				</a>

			</li>
		<?endforeach;?>
	</ul>
	<script>
		$('.left_menu').ready(function(){
			$('.left_menu > li').each(function(){
				if($(this).find('.child_container li.current').length){
					$(this).addClass('current');
				}
			});
		})
	</script>
<?endif;?>
