<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$frame = $this->createFrame()->begin('')?>

<?$rand = '_'.md5($_SERVER['REQUEST_TIME_FLOAT']); ?>
<div class="maxwidth-theme">
<div class="form inline <?=$arResult["arForm"]["SID"]?> <?=$rand?>">



<?if ($arResult["isFormErrors"] == "Y"):?><?=$arResult["FORM_ERRORS_TEXT"];?><?endif;?>


<?=$arResult["FORM_HEADER"]?>
	<div style="margin-bottom:20px;"><?=$arResult["FORM_NOTE"]?></div>
	<div class="form_body">
			<?if(is_array($arResult["QUESTIONS"])):?>
				<?if(!$bLeftAndRight):?>
					<?foreach($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion):?>
						<?CNext::drawFormField($FIELD_SID, $arQuestion);?>
					<?endforeach;?>
				<?else:?>
					<div class="row">
						<div class="col-md-7">
							<?foreach($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion):?>
								<?if($arQuestion["STRUCTURE"][0]["FIELD_PARAM"] == 'left'):?>
									<?CNext::drawFormField($FIELD_SID, $arQuestion);?>
								<?endif;?>
							<?endforeach;?>
						</div>
						<div class="col-md-5">
							<?foreach($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion):?>
								<?if($arQuestion["STRUCTURE"][0]["FIELD_PARAM"] != 'left'):?>
									<?CNext::drawFormField($FIELD_SID, $arQuestion);?>
								<?endif;?>
							<?endforeach;?>
						</div>
					</div>
				<?endif;?>
			<?endif;?>

		<input type="hidden"  id="form_nameid" name="NAMEFORM" data-sid="NAMEFORM" value=""/>
<input type="hidden"  name="URLFORM" data-sid="URLFORM" value=""/>
	<input type="hidden" name="DOMENURL"  data-sid="DOMENURL" value="" />
		</div>


		<div class="form_footer">

			<?/*<button type="submit" class="button medium" value="submit" name="web_form_submit" ><span><?=$arResult["arForm"]["BUTTON"]?></span></button>*/?>
			<input type="submit" class="btn btn-default" value="<?=$arResult["arForm"]["BUTTON"]?>" name="web_form_submit" >
<?$bShowLicenses = (isset($arParams["SHOW_LICENCE"]) ? $arParams["SHOW_LICENCE"] : COption::GetOptionString("aspro.next", "SHOW_LICENCE", "Y"));?>
			<?if($bShowLicenses == "Y"):?>
				<div class="licence_block filter label_block">
					<input type="checkbox" id="licenses_inline" <?=(COption::GetOptionString("aspro.next", "LICENCE_CHECKED", "N") == "Y" ? "checked" : "");?> name="licenses_inline" required value="Y">
					<label for="licenses_inline">
						<?$APPLICATION->IncludeFile(SITE_DIR."include/licenses_text.php", Array(), Array("MODE" => "html", "NAME" => "LICENSES")); ?>
					</label>
				</div>
			<?endif;?>
			<script type="text/javascript">
			$(document).ready(function(){
			
			
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
		</div>
		

<?=$arResult["FORM_FOOTER"]?>

</div>

<?$frame->end()?>