<?php
/* Блок «Уточните наличие и условия доставки» на детальной проекта — новый дизайн.

   Макет: Figma «Чистовик», фрейм «Проект» 20524:98253, компонент 20795:39511.
   Одна строка: круглое фото 188, колонка с заголовком и контактами, кнопка
   «Заказать звонок» у правого края. Имя менеджера — в колонке над телефоном,
   а не подписью под фото, и строки «Скидывайте информацию для расчета на почту»
   в макете нет.

   Файл лежит в шаблоне, а не в /include: за пределами /local ничего не
   версионируется, а этот код должен уезжать на прод и на вторую машину вместе
   с шаблоном (WORKFLOW.md). Прежний /include/infochat_projects_newdesign.php
   не трогаем — им пользуются старые шаблоны проектов.

   Логика подмены телефона и почты для переходов с Яндекса, Telegram, VK и карт
   и чтение фото руководителя из карточки региона — как были. */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $arRegion, $isShowCompany;

$ndUtmSource = 'empty';
if (!empty($_SESSION['UTM']['utm_source'])) {
	$ndUtmSource = $_SESSION['UTM']['utm_source'];
}

$ndIconPhone = '<svg class="nd-infochat__icon" width="17" height="17" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1 1 .3 1.8.6 2.7a2 2 0 0 1-.5 2.1L7.6 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z"/></svg>';

$ndIconMail = '<svg class="nd-infochat__icon" width="17" height="17" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<rect x="2.5" y="4.5" width="19" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/>'
	.'<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="m3 6 9 6 9-6"/></svg>';

if (!$isShowCompany || !$arRegion) {
	return;
}

$ndPhoneDigits = preg_replace('/[^0-9]/', '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
$ndPhonePodmenaDigits = preg_replace('/[^0-9]/', '', $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);
$ndPodmena = (str_contains($ndUtmSource, 'ya') || str_contains($ndUtmSource, 'tg')
	|| str_contains($ndUtmSource, 'vk') || str_contains($ndUtmSource, 'maps'));

$ndPhoto = '';
$res = CIBlockElement::GetList(['ID' => 'ASC'], ['ID' => $arRegion['ID'], 'IBLOCK_ID' => 7], false, false, ['ID', 'NAME', 'PREVIEW_PICTURE']);
while ($ob = $res->GetNextElement()) {
	$arr = $ob->GetFields();
	$ndPhoto = CFile::GetPath($arr['PREVIEW_PICTURE']);
}
?>
<?= bitrix_sessid_post() ?>
<div class="nd-infochat nd-infochat--wide">
	<?php if ($ndPhoto): ?>
		<img class="nd-infochat__photo" alt="#REGION_TAG_DIRECTOR#" src="<?= $ndPhoto ?>" loading="lazy" />
	<?php endif; ?>

	<div class="nd-infochat__body">
		<div class="nd-infochat__head">
			<div class="nd-infochat__title">Уточните наличие и&nbsp;условия доставки</div>
			<div class="nd-infochat__subtitle">Свяжитесь с нами сейчас</div>
		</div>

		<div class="nd-infochat__person">
			<div class="nd-infochat__name">#REGION_TAG_DIRECTOR#</div>

			<div class="nd-infochat__contacts">
				<?php if ($ndPodmena && $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']): ?>
					<a class="nd-infochat__contact" rel="nofollow" href="tel:+<?= $ndPhonePodmenaDigits ?>"><?= $ndIconPhone ?><span>#REGION_TAG_PHONE_PODMENA#</span></a>
				<?php else: ?>
					<a class="nd-infochat__contact" rel="nofollow" href="tel:+<?= $ndPhoneDigits ?>"><?= $ndIconPhone ?><span>#REGION_TAG_PHONE#</span></a>
				<?php endif; ?>

				<?php /* Почта — при любой метке, телефон выше — по списку источников. */ ?>
				<?php if (ndIsUtmVisit() && $arRegion['PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE']): ?>
					<a class="nd-infochat__contact" href="mailto:#REGION_TAG_EMAIL_PODMENA#"><?= $ndIconMail ?><span>#REGION_TAG_EMAIL_PODMENA#</span></a>
				<?php else: ?>
					<a class="nd-infochat__contact" href="mailto:#REGION_TAG_MAIL#"><?= $ndIconMail ?><span>#REGION_TAG_MAIL#</span></a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php /* data-nd-form-title обязателен: иначе в заголовок формы уедет имя
	         веб-формы, а в скрытое поле NAMEFORM — текст всей страницы
	         (см. js/newdesign-header.js). */ ?>
	<span class="nd-infochat__btn animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="question" data-nd-form-title="Заказать звонок">Заказать звонок</span>
</div>
