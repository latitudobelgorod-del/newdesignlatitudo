<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$randinline = '__'.md5($_SERVER['REQUEST_TIME_FLOAT']);
?>
<?if( isMobilelat() ):?>
<?$device_t = 'mobile';?>
<?else:?>
<?$device_t = 'desktop';?>
<?endif;?>
		  
<style>
	#hidden555 .fancybox-close-small {display:none;}
</style>

<div id="uniqBitrixFormId0" class="form  <?=$randinline?>">


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
	
if (($val=='utm_content')&&($v=='empty'))
	$utm_content = $device_t;
else if ($val=='utm_content')
	$utm_content =$v;

if ($val=='utm_source')
	$utm_source =$v;
if ($val=='utm_medium')
	$utm_medium =$v;
if ($val=='utm_campaign')
	$utm_campaign =$v;

if ($val=='utm_term')
	$utm_term =$v;
if ($val=='utm_geo')
	$utm_geo =$v;
}

$arResult["QUESTIONS"]['UTM_SOURCE']['VALUE'] = $utm_source;
$arResult["QUESTIONS"]['UTM_MEDIUM']['VALUE'] = $utm_medium;
$arResult["QUESTIONS"]['UTM_CAMPAIGN']['VALUE'] = $utm_campaign;
$arResult["QUESTIONS"]['UTM_CONTENT']['VALUE'] = $utm_content;
$arResult["QUESTIONS"]['UTM_TERM']['VALUE'] = $utm_term;
$arResult["QUESTIONS"]['UTM_GEO']['VALUE'] = $utm_geo;

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
 
	<div class="block"> <input type="submit" onclick="submitForm()" class="btn white" style="width:100%;" value="<?=$arResult["arForm"]["BUTTON"]?>"></div>


    <?=$arResult["FORM_FOOTER"]?>
<?endif;?>
	</div>

	<?$templateFolder = '/bitrix/templates/aspro_next/components/bitrix/form.result.new/form_inline_services';?>

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
    </div> </div>
	
	