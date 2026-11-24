<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?

$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
	return;
}

global $arRegion;
$ndIco = SITE_TEMPLATE_PATH.'/images/newdesign/contacts/';






/** Ссылка «позвонить» из человекочитаемого номера. */
$ndTel = static function ($phone) {
	return 'tel:'.preg_replace('/[^0-9+]/', '', $phone);
};
?>
<?/* nd-contacts--page: те же классы (.nd-contacts, __row, __ico) у мобильной шторки
     «Связаться с нами» (css/newdesign-mobile.css) — модификатор отделяет страницу. */?>
<div class="nd-contacts nd-contacts--page">
	<? foreach ($arResult['ITEMS'] as $arItem): ?>
		<?
		$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

		$props = $arItem['DISPLAY_PROPERTIES'];

		$phones = [];
		if (!empty($props['PHONE']['VALUE'])) {
			$phones = (array) $props['PHONE']['VALUE'];
		}

		$email = '';
		if (!empty($props['EMAIL'])) {

			$email = $props['EMAIL']['DISPLAY_VALUE'];
		}

		$video = $props['VIDEO_OFFICE']['VALUE'] ?? '';
		?>
		<?/* Микроразметка филиала: имя компании, адрес разложен на PostalAddress,
		     координаты из свойства MAP, режим работы в машинном виде. */?>
		<? $ndGeo = preg_match('/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/', trim((string) ($arItem['PROPERTIES']['MAP']['VALUE'] ?? '')), $m) ? [$m[1], $m[2]] : []; ?>
		<div class="nd-contacts__item" id="<?= $this->GetEditAreaId($arItem['ID']) ?>" itemscope itemtype="https://schema.org/LocalBusiness">
			<meta itemprop="name" content="Латитудо">
			<meta itemprop="description" content="<?= htmlspecialcharsbx($arItem['NAME']) ?>">
			<? if ($ndGeo): ?>
				<span itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">
					<meta itemprop="latitude" content="<?= $ndGeo[0] ?>">
					<meta itemprop="longitude" content="<?= $ndGeo[1] ?>">
				</span>
			<? endif; ?>
			<? if ($arItem['ND_URL']): ?><link itemprop="url" href="<?= $arItem['ND_URL'] ?>"><? endif; ?>

			<div class="nd-contacts__info">
				<div class="nd-contacts__city">
					<? if ($arItem['ND_URL']): ?>
						<a href="<?= $arItem['ND_URL'] ?>"><?= $arItem['ND_CITY'] ?></a>
					<? else: ?>
						<?= $arItem['ND_CITY'] ?>
					<? endif; ?>
				</div>

				<? if (!empty($props['ADDRESS'])): ?>
					<div class="nd-contacts__row nd-contacts__row--address">
						<img class="nd-contacts__ico" src="<?= $ndIco ?>pin.svg" alt="" width="20" height="20">
						<span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
							<meta itemprop="addressCountry" content="RU">
							<? if ($arItem['ND_CITY']): ?><meta itemprop="addressLocality" content="<?= htmlspecialcharsbx($arItem['ND_CITY']) ?>"><? endif; ?>
							<span itemprop="streetAddress"><?= html_entity_decode($arItem['PROPERTIES']['ADDRESS']['VALUE']) ?></span>
						</span>
					</div>
				<? endif; ?>

				<? if ($phones || $email): ?>
					<div class="nd-contacts__links">
						<? foreach ($phones as $phone): ?>
							<div class="nd-contacts__row">
								<img class="nd-contacts__ico" src="<?= $ndIco ?>phone.svg" alt="" width="20" height="20">
								<a itemprop="telephone" rel="nofollow" href="<?= $ndTel($phone) ?>"><?= $phone ?></a>
							</div>
						<? endforeach; ?>
						<? if ($email): ?>
							<div class="nd-contacts__row">
								<img class="nd-contacts__ico" src="<?= $ndIco ?>mail.svg" alt="" width="20" height="20">
								<a itemprop="email" href="mailto:<?= $email ?>"><?= $email ?></a>
							</div>
						<? endif; ?>
					</div>
				<? endif; ?>

				<? if (!empty($props['SCHEDULE'])): ?>
					<div class="nd-contacts__row">
						<img class="nd-contacts__ico" src="<?= $ndIco ?>clock.svg" alt="" width="20" height="20">
						<? $ndHours = ndSchemaOpeningHours($props['SCHEDULE']['VALUE']['TEXT']); ?>
						<? if ($ndHours): ?><meta itemprop="openingHours" content="<?= $ndHours ?>"><? endif; ?>
						<span class="nd-contacts__schedule"><?= htmlspecialcharsBack($props['SCHEDULE']['VALUE']['TEXT']) ?></span>
					</div>
				<? endif; ?>

				<? if (!empty($props['ADDRESS_SKLAD'])): ?>
					<div class="nd-contacts__sep"></div>
					<div class="nd-contacts__row nd-contacts__row--muted">
						<img class="nd-contacts__ico" src="<?= $ndIco ?>store.svg" alt="" width="20" height="20">
						<span><span class="nd-contacts__label">Адрес склада</span><?= $props['ADDRESS_SKLAD']['DISPLAY_VALUE'] ?></span>
					</div>
				<? endif; ?>
			</div>

			<div class="nd-contacts__media">
				<? if ($arItem['ND_PIC']): ?>
					<img class="nd-contacts__pic" itemprop="image" src="<?= $arItem['ND_PIC'] ?>" alt="<?= $arItem['NAME'] ?>" loading="lazy">
				<? endif; ?>
				<? if ($video): ?>
					<?/* Классы popup_video/various/video_link нужны обработчику темы — он вешает на них fancybox */?>
					<a class="nd-contacts__video popup_video various video_link" href="<?= $video ?>">
						Видео офиса
						<img src="<?= $ndIco ?>play.svg" alt="" width="24" height="24">
					</a>
				<? endif; ?>
			</div>
		</div>
	<? endforeach; ?>
</div>

<?/* Тизеры-преимущества под контактами (макет 20560:104625 / 20560:104712).
   Блок общий с контактами региона — лежит в page_blocks шаблона. */?>
<?include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/page_blocks/contacts_teasers_newdesign.php';?>
