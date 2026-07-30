<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$this->setFrameMode(false);

if (!include_once($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/vendor/php/solution.php')) {
    throw new Exception('Error include solution constants');
}

if (isset($APPLICATION->arAuthResult)) {
    $arResult['ERROR_MESSAGE'] = $APPLICATION->arAuthResult;
}

$bUsePhoneInput = $arResult['PHONE_REGISTRATION'];
$bByPhoneRequest = $bUsePhoneInput && isset($_POST['USER_PHONE_NUMBER']) && isset($_POST['send_account_info']);
$bEmailAsLogin = TSolution::getFrontParametrValue('LOGIN_EQUAL_EMAIL') === 'Y';
?>
<div class="border_block">
    <div class="module-form-block-wr lk-page">
        <?if ($arResult['ERROR_MESSAGE']):?>
            <div class="alert <?=$arResult['ERROR_MESSAGE']['TYPE'] === 'OK' ? 'alert-success' : 'alert-danger';?>"><?=$arResult['ERROR_MESSAGE']['MESSAGE'];?></div>
        <?endif;?>
        <?if (!$arResult['ERROR_MESSAGE'] || $arResult['ERROR_MESSAGE']['TYPE'] != 'OK'):?>
            <?if ($bUsePhoneInput):?>
                <div class="auth_forgot_select_title"><?=GetMessage('AUTH_FORGOT_PASSWORD_SELECT');?></div>
                <br />
                <div class="tabs">
                    <ul class="nav nav-tabs centered">
                        <li class="<?=$bByPhoneRequest ? '' : 'active';?>"><a href="#forgot_by_login" data-toggle="tab"><?=GetMessage('AUTH_FORGOT_BY_LOGIN_OR_EMAIL');?></a></li><!--
                        --><li class="<?=$bByPhoneRequest ? 'active' : '';?>"><a href="#forgot_by_phone" data-toggle="tab"><?=GetMessage('AUTH_FORGOT_BY_PHONE');?></a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane <?=$bByPhoneRequest ? '' : 'active';?>" id="forgot_by_login">
            <?endif;?>
            <div class="form-block form">
                <form id="forgotpasswd-page-form" method="post" action="<?=POST_FORM_ACTION_URI;?>" name="bform">
                    <?if (strlen($arResult['BACKURL']) > 0) {?><input type="hidden" name="backurl" value="<?=$arResult['BACKURL'];?>" /><?}?>
                    <input type="hidden" name="AUTH_FORM" value="Y">
                    <input type="hidden" name="TYPE" value="SEND_PWD">
                    <?php
                    $name = 'AUTH_EMAIL';
                    if (!$bEmailAsLogin) {
                        $name = 'AUTH_LOGIN';
                    }
                    ?>
                    <?=GetMessage('AUTH_FORGOT_PASSWORD_1');?>
                    <br /><br />
                    <div class="form-control">
                        <label><?=GetMessage($name);?> <span class="star">*</span></label>
                        <?if ($bEmailAsLogin):?>
                            <input type="email" name="USER_EMAIL" required="required"  maxlength="255" />
                        <?else:?>
                            <input type="text" name="USER_LOGIN" required="required"  maxlength="255" />
                        <?endif;?>
                    </div>
                    <?if ($arResult['USE_CAPTCHA']):?>
                        <?php
                        /** @var TSolution\Captcha\Service $captcha */
                        $captcha = TSolution\Captcha::getInstance();
                        ?>
                        <div class="clearboth"></div>
                        <div class="form-control captcha-row clearfix">
                            <label for="FORGOTPASSWD_CAPTCHA"><span><?=$captcha->isService() && $captcha->isActive() ? GetMessage("FORM_GENERAL_RECAPTCHA") : GetMessage("CAPTCHA_PROMT");?>&nbsp;<span class="star">*</span></span></label>
                            <div class="captcha_image">
                                <img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult['CAPTCHA_CODE'];?>" border="0" />
                                <input type="hidden" name="captcha_sid" value="<?=$arResult['CAPTCHA_CODE'];?>" />
                                <div class="captcha_reload"><?=GetMessage('RELOAD');?></div>
                            </div>
                            <div class="captcha_input">
                                <input id="FORGOTPASSWD_CAPTCHA" type="text" class="inputtext captcha" name="captcha_word" size="30" maxlength="50" value="" required />
                            </div>
                        </div>
                        <div class="clearboth"></div>
                    <?endif;?>
                    <div class="but-r">
                        <button class="btn btn-default vbig_btn wides" type="submit" name="send_account_info" value="Y"><span><?=GetMessage('RETRIEVE');?></span></button>
                    </div>
                </form>
            </div>
            <?if ($bUsePhoneInput):?>
                        </div>
                        <div class="tab-pane <?=$bByPhoneRequest ? 'active' : '';?>" id="forgot_by_phone">
                            <div class="form-block">
                                <form name="bform2" method="post" target="_top" class="bf" action="<?=SITE_DIR;?>auth/forgot-password/">
                                    <?if (strlen($arResult['BACKURL']) > 0) {?><input type="hidden" name="backurl" value="<?=$arResult['BACKURL'];?>" /><?}?>
                                    <input type="hidden" name="AUTH_FORM" value="Y">
                                    <input type="hidden" name="TYPE" value="SEND_PWD">
                                    <?=GetMessage('forgot_pass_phone_number_note');?>
                                    <br /><br />
                                    <div class="form-control">
                                        <label><?=GetMessage('forgot_pass_phone_number');?> <span class="star">*</span></label>
                                        <input type="tel" name="USER_PHONE_NUMBER" class="phone" required="required"  maxlength="255" value="" />
                                    </div>
                                    <?if ($arResult['USE_CAPTCHA']):?>
                                        <?php
                                        /** @var TSolution\Captcha\Service $captcha */
                                        $captcha = TSolution\Captcha::getInstance();
                                        ?>
                                        <div class="clearboth"></div>
                                        <div class="form-control captcha-row clearfix">
                                            <label for="FORGOTPASSWD_CAPTCHA"><span><?=$captcha->isService() && $captcha->isActive() ? GetMessage("FORM_GENERAL_RECAPTCHA") : GetMessage("CAPTCHA_PROMT");?>&nbsp;<span class="star">*</span></span></label>
                                            <div class="captcha_image">
                                                <img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult['CAPTCHA_CODE'];?>" border="0" />
                                                <input type="hidden" name="captcha_sid" value="<?=$arResult['CAPTCHA_CODE'];?>" />
                                                <div class="captcha_reload"><?=GetMessage('RELOAD');?></div>
                                            </div>
                                            <div class="captcha_input">
                                                <input id="FORGOTPASSWD_CAPTCHA" type="text" class="inputtext captcha" name="captcha_word" size="30" maxlength="50" value="" required />
                                            </div>
                                        </div>
                                        <div class="clearboth"></div>
                                    <?endif;?>
                                    <div class="but-r">
                                        <button class="btn btn-default vbig_btn wides" type="submit" name="send_account_info" value="Y"><span><?=GetMessage('RETRIEVE');?></span></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?endif;?>
        <?endif;?>
    </div>
</div>
<script>
function initValidate($form) {
    if ($form.length) {
        $form.validate({
            highlight: function(element) {
                $(element).parent().addClass('error');
            },
            unhighlight: function(element) {
                $(element).parent().removeClass('error');
            },
            submitHandler: function(form) {
                if ($(form).valid()) {
                    var $button = $(form).find('button[type=submit]');
                    if ($button.length) {

                        if (!$button.hasClass('loadings')) {
                            $button.addClass('loadings');

                            var eventdata = {type: 'form_submit', form: form, form_name: 'FORGOT'};
                            BX.onCustomEvent('onSubmitForm', [eventdata]);
                        }
                    }
                }
            },
            errorPlacement: function(error, element) {
                error.insertBefore(element);
            },
        });
    }
}

BX.Aspro.Utils.readyDOM(() => {
    if ($('.lk-page .nav-tabs>li').length) {
        $('.lk-page .nav-tabs>li').click(function() {
            const id = $(this).find('>a').attr('href');
            if (id.length) {
                const $tabContent = $(id);
                if ($tabContent.length) {
                    const $form = $tabContent.find('form')
                    if ($form.length) {
                        if (!$(this).hasClass('inited')) {
                            $(this).addClass('inited');
                            initValidate($form);
                        }

                        setTimeout(function() {
                            $form.find('input:visible').eq(0).focus();
                        }, 50);
                    }
                }
            }
        });

        $('.lk-page .nav-tabs>li.active').trigger('click');
    } else {
        initValidate($('form[name=bform]'));

        setTimeout(function() {
            $('form[name=bform]').find('input:visible').eq(0).focus();
        }, 50);
    }
});
</script>
<?php
$arExtensions = ['validate', 'phone_mask'];
TSolution\Extensions::init($arExtensions);
