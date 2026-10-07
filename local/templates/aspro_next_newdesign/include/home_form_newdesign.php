<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/* Окно «Оставить заявку» на главной, разметка которого стоит прямо в HTML страницы
   (footer.php, после блоков главной), а не грузится ajax'ом по кнопке.

   Проверка сайта на 152-ФЗ (vlip.site) смотрит только ту страницу, адрес которой ей
   дали, и формы-окна не открывает: на easydecking.ru главная без формы в HTML получала
   «Отдельное согласие на обработку ПД в формах не выявлено», с формой — чисто
   (07.10.2026). Встроенная в страницу форма Ирине не понравилась — поэтому окно:
   кнопки MAINFORM на главной («Оставить заявку») открывают его вместо штатного
   jqm-окна. Перехват в capture-фазе — иначе на странице оказались бы две формы
   MAINFORM, а их скрипты проверки полей завязаны на form[name="MAINFORM"].
   Окно прячется visibility, а не display:none — капча должна отрисоваться заранее.

   Форма — «Общая форма» (MAINFORM, id 19), шаблон inline_newdesign, как у формы
   под политикой (include/policy_form_newdesign.php). */
use CNext as Solution;
?>
<div class="nd-home-modal" id="nd-home-modal" aria-hidden="true">
	<div class="nd-home-modal__box" role="dialog" aria-modal="true">
		<button type="button" class="nd-home-modal__close" aria-label="Закрыть">&times;</button>
		<?$APPLICATION->IncludeComponent(
			'bitrix:form.result.new',
			'inline_newdesign',
			array(
				'WEB_FORM_ID' => '19',
				'ND_TITLE' => 'Оставить заявку',
				'ND_SUBTITLE' => 'Оставьте контакты и опишите задачу — менеджер свяжется с вами.',
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
</div>
<style>
/* z-index выше cookie-баннера и шапки; окно переносится в body скриптом ниже. */
.nd-home-modal {position:fixed;inset:0;z-index:10070;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(16,16,20,.55);visibility:hidden;opacity:0;pointer-events:none;transition:opacity .2s ease,visibility 0s linear .2s;}
.nd-home-modal.is-open {visibility:visible;opacity:1;pointer-events:auto;transition:opacity .2s ease;}
.nd-home-modal__box {position:relative;width:100%;max-width:680px;max-height:calc(100vh - 32px);overflow-y:auto;background:#fff;}
.nd-home-modal__close {position:absolute;top:8px;right:8px;z-index:2;width:40px;height:40px;padding:0;border:0;background:none;font-size:30px;line-height:40px;color:#525264;cursor:pointer;}
.nd-home-modal__close:hover {color:#101014;}
.nd-home-modal .form-container.nd-inlineform > div {border:0;}
@media (min-width:1200px) {
	.nd-home-modal .form-container.nd-inlineform .form .form_body {grid-template-columns:repeat(2,minmax(0,1fr));}
}
.nd-home-modal-lock, .nd-home-modal-lock body {overflow:hidden;}
</style>
<script>
(function(){
	var modal = document.getElementById('nd-home-modal');
	if(!modal) return;
	/* Блок формы лежит внутри обёрток со своим z-index — в DOM окно уходит в body. */
	document.body.appendChild(modal);
	var TRIGGER = '[data-event="jqm"][data-param-form_id="MAINFORM"]';
	function open(){ modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); document.documentElement.classList.add('nd-home-modal-lock'); }
	function close(){ modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); document.documentElement.classList.remove('nd-home-modal-lock'); }
	document.addEventListener('click', function(e){
		var t = e.target.closest ? e.target.closest(TRIGGER) : null;
		if(!t) return;
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();
		open();
	}, true);
	modal.addEventListener('click', function(e){
		if(e.target === modal || (e.target.closest && e.target.closest('.nd-home-modal__close'))){ e.preventDefault(); close(); }
	});
	document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && modal.classList.contains('is-open')) close(); });
})();
</script>
