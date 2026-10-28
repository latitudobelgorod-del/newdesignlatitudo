<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

use Bitrix\Main\Web\Json;

if (empty($arResult['ITEMS'])) {
	return;
}

// «В корзину» в окне — общая функция калькуляторов (/local/ajax/nd_calc_basket.php)
$this->addExternalJs(SITE_TEMPLATE_PATH . '/js/newdesign-calc-basket.js');

$ndVrPrice = function ($price) {
	if (empty($price['VALUE'])) {
		return '';
	}
	return number_format((float)$price['VALUE'], 0, ',', "\u{00A0}") . "\u{00A0}₽" . ($price['UNIT'] !== '' ? '/' . $price['UNIT'] : '');
};

$ndVrData = [];
$ndVrLd = [];
foreach ($arResult['ITEMS'] as $i => $v) {
	$products = [];
	foreach ($v['PRODUCTS'] as $p) {
		$products[] = [
			'label' => $p['LABEL'],
			'name' => $p['NAME'],
			'url' => $p['URL'],
			'buyId' => (int)$p['BUY_ID'],
			'price' => $ndVrPrice($p['PRICE']),
		];
	}
	$ndVrData[] = [
		'name' => $v['NAME'],
		'cat' => $v['CATEGORY'],
		'series' => $v['SERIES'],
		'dur' => $v['DURATION'],
		'embed' => $v['EMBED_AUTOPLAY'],
		'products' => $products,
	];

	// thumbnailUrl обязателен для VideoObject; хост — меткой, её подставляет ndCatalogCacheTokens
	if ($v['PICTURE']) {
		$ndVrLd[] = array_filter([
			'@type' => 'VideoObject',
			'name' => $v['NAME'],
			'description' => $v['DESCRIPTION'] !== '' ? $v['DESCRIPTION'] : ($v['PRODUCTS'][0]['NAME'] ?? $v['NAME']),
			'thumbnailUrl' => 'https://#ND_HOST#' . $v['PICTURE']['SRC'],
			'uploadDate' => $v['UPLOAD_DATE'],
			'duration' => $v['DURATION_ISO'],
			'embedUrl' => $v['EMBED'],
		]);
	}
}
$ndVrTotal = count($arResult['ITEMS']);
$ndVrId = 'nd-vr-' . $this->randString();
?>
<section class="nd-vr" id="video" data-nd-vr aria-labelledby="<?= $ndVrId ?>">
	<div class="nd-vr__head">
		<div class="nd-vr__heading">
			<h2 class="nd-vr__title" id="<?= $ndVrId ?>"><?= htmlspecialcharsbx($arParams['TITLE']) ?></h2>
			<? if ($arParams['SUBTITLE'] !== ''): ?>
				<p class="nd-vr__sub"><?= htmlspecialcharsbx($arParams['SUBTITLE']) ?></p>
			<? endif; ?>
		</div>
		<div class="nd-vr__arrows">
			<button type="button" class="nd-vr__arrow" data-nd-vr-dir="-1" aria-label="Листать назад">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<button type="button" class="nd-vr__arrow" data-nd-vr-dir="1" aria-label="Листать вперёд">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		</div>
	</div>

	<? if (count($arResult['CATEGORIES']) > 1): ?>
		<div class="nd-vr__chips" role="group" aria-label="Категории">
			<button type="button" class="nd-vr__chip is-active" data-nd-vr-cat="" aria-pressed="true">Все <span class="nd-vr__chip-n"><?= $ndVrTotal ?></span></button>
			<? foreach ($arResult['CATEGORIES'] as $cat => $n): ?>
				<button type="button" class="nd-vr__chip" data-nd-vr-cat="<?= htmlspecialcharsbx($cat) ?>" aria-pressed="false"><?= htmlspecialcharsbx($cat) ?> <span class="nd-vr__chip-n"><?= (int)$n ?></span></button>
			<? endforeach; ?>
		</div>
	<? endif; ?>

	<div class="nd-vr__rail" data-nd-vr-rail>
		<? foreach ($arResult['ITEMS'] as $i => $v): ?>
			<article class="nd-vr__card" data-nd-vr-i="<?= $i ?>" data-nd-vr-cat="<?= htmlspecialcharsbx($v['CATEGORY']) ?>">
				<button type="button" class="nd-vr__thumb" data-nd-vr-open="<?= $i ?>" aria-label="Смотреть видео: <?= htmlspecialcharsbx($v['NAME']) ?>">
					<? if ($v['PICTURE']): ?>
						<img class="nd-vr__img" src="<?= htmlspecialcharsbx($v['PICTURE']['SRC']) ?>" width="<?= $v['PICTURE']['WIDTH'] ?>" height="<?= $v['PICTURE']['HEIGHT'] ?>" alt="<?= htmlspecialcharsbx($v['NAME']) ?>" loading="lazy" decoding="async">
					<? endif; ?>
					<span class="nd-vr__play" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24"><path d="M8 5.2v13.6c0 .8.9 1.3 1.6.8l10.2-6.8a1 1 0 0 0 0-1.6L9.6 4.4C8.9 3.9 8 4.4 8 5.2z" fill="currentColor"/></svg></span>
					<? if ($v['SERIES'] !== ''): ?>
						<span class="nd-vr__series"><?= htmlspecialcharsbx($v['SERIES']) ?></span>
					<? endif; ?>
					<? if ($v['DURATION'] !== ''): ?>
						<span class="nd-vr__dur"><?= htmlspecialcharsbx($v['DURATION']) ?></span>
					<? endif; ?>
				</button>
				<div class="nd-vr__body">
					<h3 class="nd-vr__name"><?= htmlspecialcharsbx($v['NAME']) ?></h3>
					<?/* цены под карточкой нет (Ирина, 28.10.2026): цена и выбор цвета — в окне с видео */?>
				</div>
			</article>
		<? endforeach; ?>
	</div>

	<div class="nd-vr-modal" data-nd-vr-modal hidden>
		<div class="nd-vr-modal__box" role="dialog" aria-modal="true" aria-label="Видео">
			<div class="nd-vr-modal__player" data-nd-vr-player></div>
			<div class="nd-vr-modal__side">
				<div class="nd-vr-modal__top">
					<span class="nd-vr-modal__kicker" data-nd-vr-kicker></span>
					<button type="button" class="nd-vr-modal__close" data-nd-vr-close aria-label="Закрыть">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					</button>
				</div>
				<div class="nd-vr-modal__title" data-nd-vr-title></div>
				<div class="nd-vr-modal__variants" data-nd-vr-variants></div>
				<div class="nd-vr-modal__product" data-nd-vr-product></div>
				<div class="nd-vr-modal__price" data-nd-vr-price></div>
				<button type="button" class="nd-vr-modal__buy" data-nd-vr-buy>В корзину</button>
				<div class="nd-vr-modal__msg" data-nd-vr-msg aria-live="polite"></div>
				<a class="nd-vr-modal__link" data-nd-vr-link href="#">Перейти к товару →</a>
			</div>
		</div>
	</div>

	<script type="application/json" data-nd-vr-data><?= Json::encode($ndVrData) ?></script>
</section>
<? if ($ndVrLd): ?>
<script type="application/ld+json"><?= Json::encode(['@context' => 'https://schema.org', '@graph' => $ndVrLd]) ?></script>
<? endif; ?>
