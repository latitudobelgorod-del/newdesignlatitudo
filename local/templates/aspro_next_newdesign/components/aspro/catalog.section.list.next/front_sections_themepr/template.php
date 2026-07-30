<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<?if($arResult['SECTIONS']):?>
	<div class="sections_wrapper">
		
		<?include_once($arParams['TEMPLATE'].'.php');?>


	</div>
<?endif;?>