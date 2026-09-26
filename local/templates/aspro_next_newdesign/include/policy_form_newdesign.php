<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/* Форма «Вопрос по персональным данным» внизу политики обработки ПД
   (/info/licenses_detail/, news/about/detail.php). Встроена в страницу, а не
   окном: проверка сайта на 152-ФЗ (vlip.site) засчитывает отдельное согласие
   только у формы, которая есть в HTML (Ирина, 26.09.2026).

   Форма — «Общая форма» (MAINFORM, id 19), та же, что у кнопок «Заявка» и
   «Звонок»: заявка уходит тем же путём в Битрикс24 и на почту. Параметры —
   как у окна в /ajax/form.php. */
use CNext as Solution;
?>
<div class="nd-policy-form">
	<?$APPLICATION->IncludeComponent(
		'bitrix:form.result.new',
		'inline_newdesign',
		array(
			'WEB_FORM_ID' => '19',
			'ND_TITLE' => 'Вопрос об обработке персональных данных',
			'ND_SUBTITLE' => 'Уточнить, изменить или удалить ваши данные, отозвать согласие — напишите, ответим по телефону или почте.',
			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_STYLE' => 'Y',
			'AJAX_OPTION_HISTORY' => 'N',
			'SEF_MODE' => 'N',
			'SUCCESS_URL' => '',
			'LIST_URL' => '',
			'EDIT_URL' => '',
			'CHAIN_ITEM_TEXT' => '',
			'CHAIN_ITEM_LINK' => '',
			'IGNORE_CUSTOM_TEMPLATE' => 'N',
			'USE_EXTENDED_ERRORS' => 'Y',
			'CACHE_TYPE' => 'A',
			'CACHE_TIME' => '3600000',
			'SHOW_LICENCE' => Solution::GetFrontParametrValue('SHOW_LICENCE'),
			'HIDDEN_CAPTCHA' => Solution::GetFrontParametrValue('HIDDEN_CAPTCHA'),
			'VARIABLE_ALIASES' => array('WEB_FORM_ID' => '', 'RESULT_ID' => ''),
		),
		false
	);?>
</div>
