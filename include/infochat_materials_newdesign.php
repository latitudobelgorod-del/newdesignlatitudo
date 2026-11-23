<?global $arRegion, $isShowCompany;?>

<?=bitrix_sessid_post();?>
<?
/**
 * Блок «Получите бесплатную консультацию» в правой колонке материалов —
 * новый дизайн.
 *
 * Рядом лежит include/infochat_materials.php — он не тронут, им пользуется
 * старый дизайн. От infochat_newdesign.php отличается только заголовком —
 * так же устроены и исходные файлы старого дизайна.
 * Вся логика телефонов скопирована оттуда один в один: резервные номера,
 * подмена номера и почты для переходов с Яндекса, Telegram, VK и карт.
 * Отличается только разметка (классы .nd-infochat*, стили в css/newdesign.css)
 * и порядок по макету: телефон, почта, а кнопка последней.
 */
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
	if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
	if ($val=='utm_source')
		$utm_source =$v;
}
/* 22.09.2026: подмена — как в шапке: только пока идёт рекламный визит (ndIsUtmVisit, сутки с перехода);
   метка в сессии живёт 30 дней. 'ya_visit' лишь включает проверки str_contains(..., 'ya') ниже. */
$utm_source = (function_exists('ndIsUtmVisit') && ndIsUtmVisit()) ? 'ya_visit' : 'empty';

$ndIconPhone = '<svg class="nd-infochat__icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1 1 .3 1.8.6 2.7a2 2 0 0 1-.5 2.1L7.6 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z"/></svg>';

$ndIconMail = '<svg class="nd-infochat__icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<rect x="2.5" y="4.5" width="19" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/>'
	.'<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="m3 6 9 6 9-6"/></svg>';
?>

<div class="nd-infochat">
	<div class="nd-infochat__title">Получите бесплатную консультацию</div>
	<div class="nd-infochat__subtitle">Свяжитесь с нами сейчас</div>

	<?if($isShowCompany):?>
		<?
		$res = CIBlockElement::GetByID($arRegion["ID"]);
		if($ar_res = $res->GetNext())
			$URL = CFile::GetPath($ar_res['PREVIEW_PICTURE']);
		?>
		<?if($URL):?>
			<img class="nd-infochat__photo" alt="#REGION_TAG_DIRECTOR#" src="<?=$URL;?>"/>
		<?endif;?>
		<p class="nd-infochat__name">#REGION_TAG_DIRECTOR#</p>

		<?if($arRegion):
			$dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
			$dump1 = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);
			$ndPodmena = (str_contains($utm_source, "ya") || str_contains($utm_source, "tg") || str_contains($utm_source, "vk") || str_contains($utm_source, "maps"));
			?>

			<div class="nd-infochat__contacts">
				<? if ($arRegion['PROPERTY_REGION_TAG_USE_NUMBERS_PHONE_VALUE'] == "Y") : ?>
					<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>
					<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']):
						$href8800 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']);?>
						<a class="nd-infochat__contact" rel="nofollow" href="<?=$href8800?>"><?=$ndIconPhone?><span>#REGION_TAG_PHONE_8800#</span></a>
					<?endif;?>

					<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']):
						$hrefphone_mobile = 'tel:'.str_replace(array(' ', '-', '(', ')'), '',  $arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']);?>
						<a class="nd-infochat__contact" rel="nofollow" href="<?=$hrefphone_mobile?>"><?=$ndIconPhone?><span>#REGION_TAG_PHONE_MOBILE#</span></a>
					<?endif;?>
					<?/*ЕСЛИ НОМЕР РЕЗЕРВНЫЙ*/?>

				<?else:?>
					<?/*ЕСЛИ НОМЕР ОБЫЧНЫЙ*/?>
					<?if ($ndPodmena && $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
						<a class="nd-infochat__contact" rel="nofollow" href="tel:+<?=$dump1?>"><?=$ndIconPhone?><span>#REGION_TAG_PHONE_PODMENA#</span></a>
					<?else:?>
						<a class="nd-infochat__contact" rel="nofollow" href="tel:+<?=$dump?>"><?=$ndIconPhone?><span>#REGION_TAG_PHONE#</span></a>
					<?endif;?>
					<?/*ЕСЛИ НОМЕР ОБЫЧНЫЙ*/?>
				<?endif;?>

				<?if ($ndPodmena && $arRegion['PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE']):?>
					<a class="nd-infochat__contact" href="mailto:#REGION_TAG_EMAIL_PODMENA#"><?=$ndIconMail?><span>#REGION_TAG_EMAIL_PODMENA#</span></a>
				<?else:?>
					<a class="nd-infochat__contact" href="mailto:#REGION_TAG_MAIL#"><?=$ndIconMail?><span>#REGION_TAG_MAIL#</span></a>
				<?endif;?>
			</div>

			<?/* data-nd-form-title обязателен: иначе в заголовок формы уедет имя
			     веб-формы, а в скрытое поле NAMEFORM — текст всей страницы
			     (см. js/newdesign-header.js). */?>
			<span class="nd-infochat__btn animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="question" data-nd-form-title="Заказать звонок">Заказать звонок</span>
		<?endif;?>
	<?endif;?>
</div>
