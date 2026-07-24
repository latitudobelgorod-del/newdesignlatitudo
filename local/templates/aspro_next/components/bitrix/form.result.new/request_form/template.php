<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$randinline = '_'.md5($_SERVER['REQUEST_TIME_FLOAT']);
?>

<?if( isMobilelat() ):?>
<?$device_t = 'mobile';?>
<?else:?>
<?$device_t = 'desktop';?>
<?endif;?>


<style>
	#hidden555 .fancybox-close-small {display:none;}
</style>
<div id="uniqBitrixFormId" class="form  <?=$randinline?>">


<?if ($arResult["isFormNote"] == "Y"): ?>
<?=$arResult["FORM_NOTE"]?>
    Спасибо, ваша заявка принята!
<?else:?>
    <?=$arResult["FORM_HEADER"]?>
<div class="error-msg"></div>
    <?if ($arResult["isFormErrors"] === "Y"): ?>
        <div class="errors">
            <?=$arResult["FORM_ERRORS_TEXT"]?>
        </div>
    <?endif; ?>
 
     <?
//if(isset($_SESSION['UTM']) && !empty($_SESSION['UTM'])){

foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_geo') as $val) {
	
    if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
	if (($val=='utm_content')&&($v=='empty'))
	    $utm .=$val.': '.$device_t.'<br>';
	else 
		$utm .=$val.': '.$v.'<br>';
}
//    echo '<pre>', print_r($utm), '</pre>' ;
    $arResult["QUESTIONS"]['UTM']['VALUE'] = $utm;
    $arResult["QUESTIONS"]['UTM']['STRUCTURE'][0]['VALUE'] = $utm;
//}
?>
			<?if(is_array($arResult["QUESTIONS"])):?>
				<?foreach($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion):?>
					<?CNext::drawFormField($FIELD_SID, $arQuestion);?>
				<?endforeach;?>				
			<?endif;?>
 		<div class="licence_block filter label_block" style="margin:0 0 20px 0;">
						<input type="checkbox" id="licenses_popup_OCB" <?=(COption::GetOptionString("aspro.next", "LICENCE_CHECKED", "N") == "Y" ? "checked" : "");?> name="licenses_popup_OCB" required value="Y">
						<label style="margin:0;" for="licenses_popup_OCB" class="license">Я даю согласие на обработку персональных данных</label>
					</div>
				
	<div class="block"> <input type="submit" class="btn white" style="width:100%;" value="<?=$arResult["arForm"]["BUTTON"]?>"></div>


    <?=$arResult["FORM_FOOTER"]?>
<?endif;?>
	</div>

	<?$templateFolder = '/bitrix/templates/aspro_next/components/bitrix/form.result.new/request_form';?>

<script>

	var urlform = window.location.href;domenurl = window.location.host;b = 'Заявка на консультацию';
ajaxForm(document.getElementsByName('<?=$arResult['arForm']['SID']?>')[0], '<?=$templateFolder?>/ajax.php');

	$(document).ready(function(){
		$('div.<?=$randinline?> form input[data-sid="NAMEFORM"]').val(b);
	if($('div.<?=$randinline?> form input[data-sid="DOMENURL"]').length)
				$('div.<?=$randinline?> form input[data-sid="DOMENURL"]').val(domenurl);
			if($('div.<?=$randinline?> form input[data-sid="URLFORM"]').length)
				$('div.<?=$randinline?> form input[data-sid="URLFORM"]').val(urlform);
			
			
			
});

	</script>

<div style="display: none;">
    <div class="box-modal" id="hidden555">
<a href="#" class="close jqmClose" ><i></i></a>


       Ваше сообщение успешно отправлено
    </div>
