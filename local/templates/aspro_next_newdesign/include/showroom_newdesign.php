<?php
/* Блок «Приезжайте в шоурум» — новый дизайн (макет Ирины, 11 сентября 2026).
   Раньше на его месте был .photo_ter_block старой темы: фото, адрес с
   телефоном и режимом работы, два списка с галочками и плашка «Архитекторам».

   Разметка — здесь, в шаблоне (он в Git). Тексты списков и фото остаются во
   включаемых файлах, откуда их правит контент-менеджер
   (/include/catalog/tizer_block_service.php — услуги, tizer_ter_doska.php —
   каталог): в новом дизайне они собирают $ndShowroom и подключают этот файл,
   старому дизайну печатают прежний блок.

   $ndShowroom = [
       'image' => '/путь/к/фото.jpg',
       'lists' => ['Заголовок карточки' => ['пункт', 'пункт с <a href="…">ссылкой</a>', …], …],
   ];

   Адрес, телефон и режим работы — теги региона (#REGION_TAG_…#, их
   подставляет обработчик темы), как и в старом блоке; подменный телефон для
   переходов с Яндекса, Telegram, VK и карт — тем же правилом. Кнопка
   «Заказать пропуск» (форма PROPUSK) — только в Москве: пропуск нужен в
   бизнес-парк «Румянцево», в других городах его нет (как на /contacts/). */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $arRegion;

$ndShowroom = (array) ($ndShowroom ?? []);
$ndSrImage = (string) ($ndShowroom['image'] ?? '');
$ndSrLists = (array) ($ndShowroom['lists'] ?? []);

$ndSrUtm = !empty($_SESSION['UTM']['utm_source']) ? (string) $_SESSION['UTM']['utm_source'] : 'empty';
$ndSrPodmena = (str_contains($ndSrUtm, 'ya') || str_contains($ndSrUtm, 'tg')
	|| str_contains($ndSrUtm, 'vk') || str_contains($ndSrUtm, 'maps'))
	&& !empty($arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);
$ndSrPhoneRaw = $ndSrPodmena
	? (string) $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']
	: (string) ($arRegion['PROPERTY_REGION_TAG_PHONE_VALUE'] ?? '');
$ndSrPhoneTag = $ndSrPodmena ? '#REGION_TAG_PHONE_PODMENA#' : '#REGION_TAG_PHONE#';
$ndSrHasAddress = !empty($arRegion['PROPERTY_REGION_TAG_ADDRESSMY_VALUE']);
$ndSrIsMoscow = $arRegion && mb_stripos((string) ($arRegion['NAME'] ?? ''), 'москв') !== false;

$ndSrCheck = '<svg class="nd-showroom__check" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">'
	.'<path d="M2.5 8.5l3.5 3.5 7.5-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>
<section class="nd-showroom">
	<div class="nd-showroom__grid">
		<div class="nd-showroom__panel">
			<div class="nd-showroom__title">Приезжайте в шоурум</div>
			<? if ($ndSrHasAddress): ?>
				<div class="nd-showroom__address">#REGION_TAG_ADDRESSMY#</div>
			<? endif; ?>
			<? if ($ndSrPhoneRaw !== ''): ?>
				<a class="nd-showroom__phone" rel="nofollow" href="tel:<?= htmlspecialcharsbx(preg_replace('/[^0-9+]/', '', $ndSrPhoneRaw)) ?>"><?= $ndSrPhoneTag ?></a>
			<? endif; ?>
			<div class="nd-showroom__time">#REGION_TAG_TIME#</div>
			<div class="nd-showroom__actions">
				<? if ($ndSrIsMoscow): ?>
					<span class="nd-showroom__btn nd-showroom__btn--light animate-load" data-event="jqm" data-param-form_id="PROPUSK"
						  data-name="spbuttonPROPUSKshowroom" data-nd-form-title="Заказать пропуск">Заказать пропуск</span>
				<? endif; ?>
				<a class="nd-showroom__btn nd-showroom__btn--outline" href="/contacts/">Схема проезда</a>
			</div>
		</div>

		<div class="nd-showroom__side">
			<? if ($ndSrImage !== ''): ?>
				<div class="nd-showroom__photo">
					<img src="<?= htmlspecialcharsbx($ndSrImage) ?>" alt="Шоурум Латитудо" loading="lazy">
				</div>
			<? endif; ?>
			<? if ($ndSrLists): ?>
				<div class="nd-showroom__cards">
					<? foreach ($ndSrLists as $ndSrHead => $ndSrItems): ?>
						<div class="nd-showroom__card">
							<div class="nd-showroom__card-title"><?= htmlspecialcharsbx($ndSrHead) ?></div>
							<ul class="nd-showroom__list">
								<? foreach ((array) $ndSrItems as $ndSrItem): ?>
									<li><?= $ndSrCheck ?><span><?= $ndSrItem ?></span></li>
								<? endforeach; ?>
							</ul>
						</div>
					<? endforeach; ?>
				</div>
			<? endif; ?>
		</div>
	</div>

	<div class="nd-showroom__arch">
		<span class="nd-showroom__arch-ico" aria-hidden="true"></span>
		<div class="nd-showroom__arch-text"><a href="/info/sotrudnichestvo-s-arhitektorami/">Архитекторам</a> - индивидуальные условия и помощь в проектировании!</div>
		<a class="nd-showroom__arch-btn" href="/info/sotrudnichestvo-s-arhitektorami/">Получить условия</a>
	</div>
</section>
