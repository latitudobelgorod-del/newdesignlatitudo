<?if( !defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true ) die();
$randinline = '_'.md5($_SERVER['REQUEST_TIME_FLOAT']);
?>
<?$frame = $this->createFrame()->begin('')?>
		<div >
				<div class="form  <?=$randinline?> contacts<?=($arResult['isFormNote'] == 'Y' ? ' success' : '')?><?=($arResult['isFormErrors'] == 'Y' ? ' error' : '')?>">
					<?if( $arResult["isFormNote"] == "Y" ):?>
						<div class="form-header">
								<div class="text">
								<div class="title"><?=GetMessage("SUCCESS_TITLE")?></div>
								<?=$arResult["FORM_NOTE"]?>
							</div>
						</div>
						<?endif;?>
						<?=$arResult["FORM_HEADER"]?>
						<input type="hidden" name="WEB_FORM_ID" value="<?=$arParams["WEB_FORM_ID"]?>">
						<?=bitrix_sessid_post();?>
																
										<?if($arResult['isFormErrors'] == 'Y'):?>
											
												<div class="form-error alert alert-danger">
													<?=$arResult['FORM_ERRORS_TEXT']?>
												</div>
											
										<?endif;?>							
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
			
<input type="hidden" id="form_nameid" name="NAMEFORM" data-id="over" data-sid="NAMEFORM" value="" />

	

					<div class="licence_block filter label_block">
						<input type="checkbox" id="licenses_popup_OCB" <?=(COption::GetOptionString("aspro.next", "LICENCE_CHECKED", "N") == "Y" ? "checked" : "");?> name="licenses_popup_OCB" required value="Y">
						<label for="licenses_popup_OCB" class="license">Я согласен на обработку предоставленных данных</label>
					</div>
				
				
				
				
<div class="block">
<input style="width:100%;" type="submit" onclick="" class="btn white" value="<?=$arResult["arForm"]["BUTTON"]?>" name="web_form_submit">			
</div>
<?=$arResult["FORM_FOOTER"]?>
</div>
</div>
<script type="text/javascript">
var urlform = window.location.href;domenurl = window.location.host;b = 'Заявка на консультацию';
	$(document).ready(function(){
$('div.<?=$randinline?> form input[data-sid="NAMEFORM"]').val(b);
	if($('div.<?=$randinline?> form input[data-sid="DOMENURL"]').length)
				$('div.<?=$randinline?> form input[data-sid="DOMENURL"]').val(domenurl);
			if($('div.<?=$randinline?> form input[data-sid="URLFORM"]').length)
				$('div.<?=$randinline?> form input[data-sid="URLFORM"]').val(urlform);
			
		<?if($arResult["arForm"]["VARNAME"] == 'CHEAPER'):?>
			$.extend( $.validator.messages, {
				required: BX.message('JS_REQUIRED'),
				email: BX.message('JS_FORMAT'),
				equalTo: BX.message('JS_PASSWORD_COPY'),
				minlength: BX.message('JS_PASSWORD_LENGTH'),
				remote: BX.message('JS_ERROR')
			});
			
			$.validator.addMethod(
				'regexp', function( value, element, regexp ){
					var re = new RegExp( regexp );
					return this.optional( element ) || re.test( value );
				},
				BX.message('JS_FORMAT')
			);
			
		
			
			$.validator.addClassRules({
				'phone':{
					regexp: arNextOptions['THEME']['VALIDATE_PHONE_MASK']
				}
			});
		<?endif;?>
		
		$('div.<?=$randinline?> form').validate({
			highlight: function( element ){
				$(element).parent().addClass('error');
			},
			unhighlight: function( element ){
				$(element).parent().removeClass('error');
			},
			submitHandler: function( form ){
				if( $('div.<?=$randinline?> form').valid() ){
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
		      licenses_popup: {
		        required : BX.message('JS_REQUIRED_LICENSES')
		      }
			}
		});

		if(arNextOptions['THEME']['PHONE_MASK'].length){
			var base_mask = arNextOptions['THEME']['PHONE_MASK'].replace( /(\d)/g, '_' );
			$('div.<?=$randinline?> form input.phone').inputmask('mask', {'mask': arNextOptions['THEME']['PHONE_MASK'] });
			$('div.<?=$randinline?> form input.phone').blur(function(){
				if( $(this).val() == base_mask || $(this).val() == '' ){
					if( $(this).hasClass('required') ){
						$(this).parent().find('label.error').html(BX.message('JS_REQUIRED'));
					}
				}
			});
		}
		// $('.popup').jqmAddClose('a.jqmClose');
		$('.jqmClose').on('click', function(e){
			e.preventDefault();
			$(this).closest('.jqmWindow').jqmHide();
		})
		$('.popup').jqmAddClose('button[name="web_form_reset"]');
	});
	</script>
<?$frame->end()?>