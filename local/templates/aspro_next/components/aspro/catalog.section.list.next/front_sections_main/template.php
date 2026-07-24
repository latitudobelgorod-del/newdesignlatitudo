<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode( true ); ?>

<?if($arResult['SECTIONS']):?>
	<div class="sections_wrapper">
	<?include_once($arParams['TEMPLATE'].'.php');?>
	<div class="center m-60">
<a class="all_url" href="<?=SITE_DIR.$arParams["ALL_URL"];?>">
<span class="btn btn-default btn-lg  animate-load" >
<span><?=$arParams["TITLE_BLOCK_ALL"] ;?></span></span></a>
		</div>
	</div>
<?endif;?>