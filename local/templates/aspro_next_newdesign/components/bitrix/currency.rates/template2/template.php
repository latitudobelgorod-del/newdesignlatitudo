<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<div class="course_currency" id="course_currency">
<?=GetMessage("CURRENCY_CBRF")?> на <?php echo date("d.m.Y");  ?>:

<?foreach ($arResult["CURRENCY_CBRF"] as $arCurrency):?>
	
		<?=$arCurrency["FROM"]?> = <?=$arCurrency["BASE"]?>;
	<?endforeach?>
	
	
</div>