<?global $arRegion, $isShowCompany;?>

<?=bitrix_sessid_post();?>
<?
/**
 * Широкий вариант блока «Уточните наличие и условия доставки» — на детальных
 * страницах проекта и статьи в новом дизайне.
 *
 * Рядом лежит include/infochat_projects.php — он не тронут, им пользуется
 * старый дизайн. Логика та же: подмена телефона и почты для переходов с
 * Яндекса, Telegram, VK и карт, фото и имя руководителя из карточки региона.
 * Отличается разметка: серая карточка, фото слева, текст справа
 * (классы .nd-infochat*, модификатор --wide, стили в css/newdesign.css).
 */
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
	if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
	if ($val=='utm_source')
		$utm_source =$v;
}
/* 22.09.2026: подмена — как в шапке: только пока идёт рекламный визит (ndShouldReplaceContacts, сутки с перехода);
   метка в сессии живёт 30 дней. 'ya_visit' лишь включает проверки str_contains(..., 'ya') ниже. */
$utm_source = (function_exists('ndShouldReplaceContacts') && ndShouldReplaceContacts()) ? 'ya_visit' : 'empty';

$ndIconPhone = '<svg class="nd-infochat__icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1 1 .3 1.8.6 2.7a2 2 0 0 1-.5 2.1L7.6 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z"/></svg>';

$ndIconMail = '<svg class="nd-infochat__icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<rect x="2.5" y="4.5" width="19" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/>'
	.'<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="m3 6 9 6 9-6"/></svg>';
?>

<?if($isShowCompany):?>
	<?if($arRegion):
		$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
		$dump1 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);
		$ndPodmena = (ndShouldReplaceContacts());

		$res = CIBlockElement::GetList(Array("ID"=>"ASC"), Array("ID"=>$arRegion["ID"], "IBLOCK_ID" => 7), false, false, Array("ID", "NAME", "PREVIEW_PICTURE"));
		$ndPhoto = '';
		while($ob = $res->GetNextElement()) {
			$arr = $ob->GetFields();
			$ndPhoto = CFile::GetPath($arr["PREVIEW_PICTURE"]);
		}
		?>
		<div class="nd-infochat nd-infochat--wide">
			<div class="nd-infochat__person">
				<?if($ndPhoto):?>
					<img class="nd-infochat__photo" alt="#REGION_TAG_DIRECTOR#" src="<?=$ndPhoto?>"/>
				<?endif;?>
				<p class="nd-infochat__name">#REGION_TAG_DIRECTOR#</p>
			</div>

			<div class="nd-infochat__body">
				<div class="nd-infochat__title">Уточните наличие и условия доставки</div>
				<div class="nd-infochat__subtitle">Свяжитесь с нами сейчас</div>

				<div class="nd-infochat__contacts">
					<?if ($ndPodmena && $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
						<a class="nd-infochat__contact" rel="nofollow" href="tel:+<?=$dump1?>"><?=$ndIconPhone?><span>#REGION_TAG_PHONE_PODMENA#</span></a>
					<?else:?>
						<a class="nd-infochat__contact" rel="nofollow" href="tel:+<?=$dump?>"><?=$ndIconPhone?><span>#REGION_TAG_PHONE#</span></a>
					<?endif;?>

					<?if ($ndPodmena && $arRegion['PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE']):?>
						<a class="nd-infochat__contact" href="mailto:#REGION_TAG_EMAIL_PODMENA#"><?=$ndIconMail?><span>#REGION_TAG_EMAIL_PODMENA#</span></a>
					<?else:?>
						<a class="nd-infochat__contact" href="mailto:#REGION_TAG_MAIL#"><?=$ndIconMail?><span>#REGION_TAG_MAIL#</span></a>
					<?endif;?>
				</div>

				<div class="nd-infochat__hint">Скидывайте информацию для расчета на почту</div>

				<?/* data-nd-form-title обязателен: иначе в заголовок формы уедет имя
				     веб-формы, а в скрытое поле NAMEFORM — текст всей страницы
				     (см. js/newdesign-header.js). */?>
				<span class="nd-infochat__btn animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="question" data-nd-form-title="Заказать звонок">Заказать звонок</span>
			</div>
		</div>
	<?endif;?>
<?endif;?>
