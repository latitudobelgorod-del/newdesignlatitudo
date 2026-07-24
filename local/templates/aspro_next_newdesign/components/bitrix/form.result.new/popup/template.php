<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$rand = '_'.md5($_SERVER['REQUEST_TIME_FLOAT']); ?>
<?if( isMobilelat() ):?>
<?$device_t = 'mobile';?>
<?else:?>
<?$device_t = 'desktop';?>
<?endif;?>
		  
<div>
	<div class="form-container">


<div style="margin: auto;">

<a href="#" class="close jqmClose" ><i></i></a>


	  <div style="" class="form <?=$arResult["arForm"]["SID"]?> <?=$rand?>">

	<!--noindex-->
	<div class="form_head" >
		<div id="mynameform"></div>
		<?if($arResult["isFormTitle"] == "Y"):?>
		<h2 class="formnameru"><?/*=$arResult["FORM_TITLE"]*/?>
<?=empty($_GET['formhead']) ? $arResult["FORM_TITLE"] : $_GET['formhead'];?></h2>
		<?endif;?>

		<?if($arResult["isFormDescription"] == "Y"):?>
			<div class="form_desc"><?=$arResult["FORM_DESCRIPTION"]?></div>
		<?endif;?>

	</div>
	<?if(strlen($arResult["FORM_NOTE"])){?>
		<div class="form_result <?=($arResult["isFormErrors"] == "Y" ? 'error' : 'success')?>">
			<?if($arResult["isFormErrors"] == "Y"):?>
				<?=$arResult["FORM_ERRORS_TEXT"]?>
			<?else:?>
				<?$successNoteFile = SITE_DIR."include/form/success_{$arResult["arForm"]["SID"]}.php";?>
				<?if(file_exists($_SERVER["DOCUMENT_ROOT"].$successNoteFile)):?>
				<?$APPLICATION->IncludeFile($successNoteFile, array(), array("MODE" => "html", "NAME" => "Form success note"));?>
							
				<?else:?>
					<?=GetMessage("FORM_SUCCESS");?>
						
					<?endif;?>


				
				<script>
					if(arNextOptions['THEME']['USE_FORMS_GOALS'] !== 'NONE')
					{
						var eventdata = {goal: 'goal_webform_success' + (arNextOptions['THEME']['USE_FORMS_GOALS'] === 'COMMON' ? '' : '_<?=$arResult["arForm"]["ID"]?>')};
						BX.onCustomEvent('onCounterGoals', [eventdata]);
					}
					
				</script>
			<?endif;?>
		</div>
	<?}else{?>
		<?if($arResult["isFormErrors"] == "Y"):?>
			<div class="form_body error"><?=$arResult["FORM_ERRORS_TEXT"]?></div>
		<?endif;?>
		<?=$arResult["FORM_HEADER"]?>
		<?=bitrix_sessid_post();?>
		<div class="form_body">
		
		<?if($_REQUEST["form_id"] == "PROPUSK"):?>
	<div style="margin: 0 auto;text-align: center;margin: -20px 0 20px;color: red;">Для пропуска нужен паспорт или права</div>
<?else:?>

<?endif;?>
		
	
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
//    echo '<pre>', print_r($utm), '</pre>' ;

$arResult["QUESTIONS"]['UTM_SOURCE']['VALUE'] = $utm_source;
$arResult["QUESTIONS"]['UTM_MEDIUM']['VALUE'] = $utm_medium;
$arResult["QUESTIONS"]['UTM_CAMPAIGN']['VALUE'] = $utm_campaign;
$arResult["QUESTIONS"]['UTM_CONTENT']['VALUE'] = $utm_content;
$arResult["QUESTIONS"]['UTM_TERM']['VALUE'] = $utm_term;
$arResult["QUESTIONS"]['UTM_GEO']['VALUE'] = $utm_geo;
	
$arResult["QUESTIONS"]['UTM']['VALUE'] = $utm;
$arResult["QUESTIONS"]['UTM']['STRUCTURE'][0]['VALUE'] = $utm;
	
	
	
//}
?>
			<?if(is_array($arResult["QUESTIONS"])):?>
				<?foreach($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion):?>
					<?CNext::drawFormField($FIELD_SID, $arQuestion);?>
				<?endforeach;?>				
			<?endif;?>
			<div class="clearboth"></div>
			<?$bHiddenCaptcha = (isset($arParams["HIDDEN_CAPTCHA"]) ? $arParams["HIDDEN_CAPTCHA"] : COption::GetOptionString("aspro.next", "HIDDEN_CAPTCHA", "Y"));?>
			<?if($arResult["isUseCaptcha"] == "Y"):?>
				<div class="form-control captcha-row clearfix">
					<label><span><?=GetMessage("FORM_CAPRCHE_TITLE")?>&nbsp;<span class="star">*</span></span></label>
					<div class="captcha_image">
						<img src="/bitrix/tools/captcha.php?captcha_sid=<?=htmlspecialcharsbx($arResult["CAPTCHACode"])?>" border="0" />
						<input type="hidden" name="captcha_sid" value="<?=htmlspecialcharsbx($arResult["CAPTCHACode"])?>" />
						<div class="captcha_reload"></div>
					</div>
					<div class="captcha_input">
						<input type="text" class="inputtext captcha" name="captcha_word" size="30" maxlength="50" value="" required />
					</div>
				</div>
			<?elseif($bHiddenCaptcha == "Y"):?>
				<textarea name="nspm" style="display:none;"></textarea>
			<?endif;?>
			<div class="clearboth"></div>
<input type="hidden"  id="form_nameid" name="NAMEFORM" data-id="over" data-sid="NAMEFORM" value=""/>
<input type="hidden"  name="URLFORM" data-sid="URLFORM" value=""/>
	<input type="hidden" name="DOMENURL"  data-sid="DOMENURL" value="" />
		<input type="hidden" name="DEVICE"  data-sid="DEVICE" value="<?=((isMobilelat()) ? 'mobile' : 'desktop')?>" />
		</div>
		
		
		
		<div class="form_footer">

	<input type="submit" onclick="" class="btn btn-default" value="<?=$arResult["arForm"]["BUTTON"]?>" name="web_form_submit">			



					<?if($arParams["SHOW_LICENCE"] == "Y"):?>
					<?/*if($_REQUEST["form_id"] !== "WHATSAPP"):?>
					<div class="licence_block filter label_block">
						<input type="checkbox" id="licenses_popup_OCB" <?=(COption::GetOptionString("aspro.next", "LICENCE_CHECKED", "N") == "Y" ? "checked" : "");?> name="licenses_popup_OCB" required value="Y">
						<label for="licenses_popup_OCB" class="license">
							<?$APPLICATION->IncludeFile(SITE_DIR."include/licenses_text.php", Array(), Array("MODE" => "html", "NAME" => "LICENSES")); ?>
						</label>
					</div>
				<?endif;*/?>
				
					<div class="licence_block filter label_block">
					<input type='hidden' name='aspro_next_form_validate'/>
					<input type="checkbox" id="licenses_popup" name="licenses_popup" required value="Y">

					<label for="licenses_popup">
						<?$APPLICATION->IncludeFile(SITE_DIR."include/licenses_text.php", Array(), Array("MODE" => "html", "NAME" => "LICENSES")); ?>
					</label>
				</div>
				
				
				<?endif;?>
			
	
		
			
		</div>
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		<?=$arResult["FORM_FOOTER"]?>
	<?}?>
	<!--/noindex-->
	
	

	
<script type="text/javascript">
var urlform = window.location.href;domenurl = window.location.host;b = $('h2').text();
var device = '<?php echo $device_t;?>';
$(document).ready(function(){
$('div.<?=$rand?> form input[data-sid="NAMEFORM"]').val(b);
	if($('div.<?=$rand?> form input[data-sid="DOMENURL"]').length)
				$('div.<?=$rand?> form input[data-sid="DOMENURL"]').val(domenurl);
			if($('div.<?=$rand?> form input[data-sid="URLFORM"]').length)
				$('div.<?=$rand?> form input[data-sid="URLFORM"]').val(urlform);
			if($('div.<?=$rand?> form input[data-sid="DEVICE"]').length)
				$('div.<?=$rand?> form input[data-sid="DEVICE"]').val(device);
			
			 






			 $.validator.addMethod(
    "regexp",
    function (value, element, regexp) {
      var re = new RegExp(regexp);
      return this.optional(element) || re.test(value);
    },
    BX.message("JS_FORMAT_PHONE")
  );
	if(arNextOptions['THEME']['PHONE_MASK'].length){
			var base_mask = arNextOptions['THEME']['PHONE_MASK'].replace( /(\d)/g, '_' );
			$('div.<?=$rand?> form input.phone').inputmask('mask', {'mask': arNextOptions['THEME']['PHONE_MASK'] });
			$('div.<?=$rand?> form input.phone').blur(function(){
				if( $(this).val() == base_mask || $(this).val() == '' ){
					if( $(this).hasClass('required') ){
						$(this).parent().find('label.error').html(BX.message('JS_REQUIRED'));
					}
				}
			});
		}
			
		<?/*if($arResult["arForm"]["VARNAME"] == 'CHEAPER'):?>
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
			
			$.validator.addMethod(
				'captcha', function( value, element, params ){
					return $.validator.methods.remote.call(this, value, element,{
						url: arNextOptions['SITE_DIR'] + 'ajax/check-captcha.php',
						type: 'post',
						data:{
							captcha_word: value,
							captcha_sid: function(){
								return $(element).closest('form').find('div.<?=$rand?> form input[name="captcha_sid"]').val();
							}
						}
					});
				},
				BX.message('JS_ERROR')
			);

			$.validator.addMethod(
				'recaptcha', function(value, element, param){
					var id = $(element).closest('form').find('.g-recaptcha').attr('data-widgetid');
					if(typeof id !== 'undefined'){
						return grecaptcha.getResponse(id) != '';
					}
					else{
						return true;
					}
				}, BX.message('JS_RECAPTCHA_ERROR')
			);
			
			$.validator.addClassRules({
				'phone':{
					regexp: arNextOptions['THEME']['VALIDATE_PHONE_MASK']
				},
				'captcha':{
					captcha: ''
				},
				'recaptcha':{
					recaptcha: ''
				}
			});
		<?endif;*/?>
			

		$('div.<?=$rand?> form').validate({
			highlight: function( element ){
				$(element).parent().addClass('error');
			},
			unhighlight: function( element ){
				$(element).parent().removeClass('error');
			},
			submitHandler: function( form ){
				if( $('div.<?=$rand?> form').valid() ){
					setTimeout(function() {
						$(form).find('button[type="submit"]').attr("disabled", "disabled");
					}, 300);
					var eventdata = {type: 'form_submit', form: form, form_name: '<?=$arResult["arForm"]["VARNAME"]?>'};
					BX.onCustomEvent('onSubmitForm', [eventdata]);
					 ym(62259859,'reachGoal','SUBMITRUFORM');
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


		
		
		
		
		// $('.popup').jqmAddClose('a.jqmClose');
	    
	});
	 $('.jqmClose').on('click', function(e){
            e.preventDefault();
            $(this).closest('.jqmWindow').jqmHide();
        });
		 $('.popup').jqmAddClose('button[name="web_form_reset"]');
		 
		 </script>
</div>


</div></div></div>
