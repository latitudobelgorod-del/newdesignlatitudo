<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<?if($arResult['SECTIONS']):?>
	<div class="sections_wrapper">
		
		<?include_once($arParams['TEMPLATE'].'.php');?>

<a class="all_url" href="<?=SITE_DIR.$arParams["ALL_URL"];?>"><?=$arParams["TITLE_BLOCK_ALL"] ;?></a>

	</div>
<?endif;?>