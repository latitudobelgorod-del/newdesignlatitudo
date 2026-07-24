<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$rand = '_1'.md5($_SERVER['REQUEST_TIME_FLOAT']); ?>
	 
<div class="maxwidth-theme" style="
    border: 1px solid #f0f0f0;
    padding: 15px;
    margin-bottom: 20px;
">
	 <div style="" class="form inline <?=$arResult["arForm"]["SID"]?> <?=$rand?>">

	<!--noindex-->
	<div class="form_head">
		<div id="mynameform"></div>
		<?if($arResult["isFormTitle"] == "Y"):?>
		<div id="inlineformhead" style="text-align:center; font-size: 1.6em; line-height:1.2em; margin: 0px 0 20px; font-weight: bold;color:#383838;">Заяка на консультацию</div>
		<?endif;?>
		<?if($arResult["isFormDescription"] == "Y"):?>
			<div class="form_desc"><?=$arResult["FORM_DESCRIPTION"]?></div>
		<?endif;?>
	</div>
		<?=$arResult["FORM_HEADER"]?>
	<?=bitrix_sessid_post();?>

				<div class="form_body">
<?
//if(isset($_SESSION['UTM']) && !empty($_SESSION['UTM'])){

foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_geo') as $val) {
    if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
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
			<input type="hidden"  id="form_nameid" name="NAMEFORMINLINE" data-sid="NAMEFORMINLINE" value=""/>
<input type="hidden"  name="URLFORM" data-sid="URLFORM" value=""/>
	<input type="hidden" name="DOMENURL"  data-sid="DOMENURL" value="" />
			<div class="clearboth"></div>
	
		</div>

<?if($arResult["isFormErrors"] == "Y" || strlen($arResult["FORM_NOTE"])):?>
		<div class="form_result <?=($arResult["isFormErrors"] == "Y" ? 'error' : 'success')?>">
			<?if($arResult["isFormErrors"] == "Y"):?>
				<?=$arResult["FORM_ERRORS_TEXT"]?>
			<?else:?>
				<script>

$("#hidden").fancybox({
       "padding" : 20,
       "frameWidth" : 400,
  "frameHeight" : 200,
	"showCloseButton" : false,
  "overlayOpacity" : 0.8,
});

</script>
					
<?endif;?>

		</div>
			<?endif;?>
		<div class="form_footer">
		<input type="submit" class="btn btn-default" value="<?=$arResult["arForm"]["BUTTON"]?>" name="web_form_submit">

		<script>
var urlform = window.location.href;domenurl = window.location.host;b = $('h2').text();
	var headform_inline0 = $('#inlineformhead').text();
	$(document).ready(function(){
$('div.<?=$rand?> form input[data-sid="NAMEFORM"]').val(headform_inline0);
$('div.<?=$rand?> form input[data-sid="NAMEFORMINLINE"]').val(headform_inline0);
	if($('div.<?=$rand?> form input[data-sid="DOMENURL"]').length)
				$('div.<?=$rand?> form input[data-sid="DOMENURL"]').val(domenurl);
			if($('div.<?=$rand?> form input[data-sid="URLFORM"]').length)
				$('div.<?=$rand?> form input[data-sid="URLFORM"]').val(urlform);
					$('form[name="<?=$arResult["arForm"]["VARNAME"]?>"]').validate({
				highlight: function( element ){
					$(element).parent().addClass('error');
				},
				unhighlight: function( element ){
					$(element).parent().removeClass('error');
				},
				submitHandler: function( form ){
					if( $('form[name="<?=$arResult["arForm"]["VARNAME"]?>"]').valid() ){
						setTimeout(function() {
							$(form).find('button[type="submit"]').attr("disabled", "disabled");
						}, 300);
						var eventdata = {type: 'form_submit', form: form, form_name: '<?=$arResult["arForm"]["VARNAME"]?>'};
						BX.onCustomEvent('onSubmitForm', [eventdata]);
					}
				},
				errorPlacement: function( error, element ){
					error.insertBefore(element);
				},
				messages:{
			      licenses_inline: {
			        required : BX.message('JS_REQUIRED_LICENSES')
			      }
				}
			});
			
			if(arNextOptions['THEME']['PHONE_MASK'].length){
				var base_mask = arNextOptions['THEME']['PHONE_MASK'].replace( /(\d)/g, '_' );
				$('form[name=<?=$arResult["arForm"]["VARNAME"]?>] input.phone, form[name=<?=$arResult["arForm"]["VARNAME"]?>] input[data-sid=PHONE]').inputmask('mask', {'mask': arNextOptions['THEME']['PHONE_MASK'] });
				$('form[name=<?=$arResult["arForm"]["VARNAME"]?>] input.phone, form[name=<?=$arResult["arForm"]["VARNAME"]?>] input[data-sid=PHONE]').blur(function(){
					if( $(this).val() == base_mask || $(this).val() == '' ){
						if( $(this).hasClass('required') ){
							$(this).parent().find('label.error').html(BX.message('JS_REQUIRED'));
						}
					}
				});
			}
		});
		</script>
	</div>
	<?=$arResult["FORM_FOOTER"]?>
	
	
	
	<!--/noindex-->

</div>


</div>
<div id="hidden" style="display: none; width: 500px;text-align:center;">
	<p style="margin-bottom:0;">Ваше сообщение успешно отправлено</p>
</div>
