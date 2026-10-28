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
		// ролик без товаров (портфолио): в окне вместо корзины — ссылка на проект
		'type' => $v['TYPE'] !== 'product' ? $v['TYPE_NAME'] : '',
		'project' => $v['PROJECT'] ? ['name' => $v['PROJECT']['NAME'], 'url' => $v['PROJECT']['URL']] : null,
	];

	// thumbnailUrl обязателен для VideoObject; хост — меткой, её подставляет ndCatalogCacheTokens
	if ($v['PICTURE']) {
		$ndVrLd[] = array_filter([
			'@type' => 'VideoObject',
			'name' => $v['NAME'],
			'description' => $v['DESCRIPTION'] !== '' ? $v['DESCRIPTION'] : ($v['PRODUCTS'][0]['NAME'] ?? $v['PROJECT']['NAME'] ?? $v['NAME']),
			'thumbnailUrl' => 'https://#ND_HOST#' . $v['PICTURE']['SRC'],
			'uploadDate' => $v['UPLOAD_DATE'],
			'duration' => $v['DURATION_ISO'],
			'embedUrl' => $v['EMBED'],
		]);
	}
}
$ndVrTotal = count($arResult['ITEMS']);
$ndVrId = 'nd-vr-' . $this->randString();
// GRID — страница «Видео»: H1 печатает сама страница, вместо стрелок — сетка
$ndVrGrid = $arParams['VIEW'] === 'GRID';
?>
<section class="nd-vr<?= $ndVrGrid ? ' nd-vr--grid' : '' ?>" id="video" data-nd-vr<?= $ndVrGrid ? '' : ' aria-labelledby="' . $ndVrId . '"' ?>>
	<? if (!$ndVrGrid): ?>
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
	<? elseif ($arParams['SUBTITLE'] !== ''): ?>
		<p class="nd-vr__sub nd-vr__sub--page"><?= htmlspecialcharsbx($arParams['SUBTITLE']) ?></p>
	<? endif; ?>

	<?/* Типы роликов (Ирина, 28.10.2026): обзоры продукции, отзывы, эксперт, объекты из портфолио */?>
	<? if (count($arResult['TYPES'] ?? []) > 1): ?>
		<div class="nd-vr__chips nd-vr__chips--types" role="group" aria-label="Тип видео" data-nd-vr-filter="type">
			<button type="button" class="nd-vr__chip is-active" data-nd-vr-val="" aria-pressed="true">Все видео <span class="nd-vr__chip-n"><?= $ndVrTotal ?></span></button>
			<? foreach ($arResult['TYPES'] as $type => $t): ?>
				<button type="button" class="nd-vr__chip" data-nd-vr-val="<?= htmlspecialcharsbx($type) ?>" aria-pressed="false"><?= htmlspecialcharsbx($t['NAME']) ?> <span class="nd-vr__chip-n"><?= (int)$t['COUNT'] ?></span></button>
			<? endforeach; ?>
		</div>
	<? endif; ?>
	<? if ($arParams['CATEGORY_FILTER'] !== 'N' && count($arResult['CATEGORIES']) > 1): ?>
		<div class="nd-vr__chips" role="group" aria-label="Категории" data-nd-vr-filter="cat">
			<button type="button" class="nd-vr__chip is-active" data-nd-vr-val="" aria-pressed="true">Все <span class="nd-vr__chip-n"><?= $ndVrTotal ?></span></button>
			<? foreach ($arResult['CATEGORIES'] as $cat => $n): ?>
				<button type="button" class="nd-vr__chip" data-nd-vr-val="<?= htmlspecialcharsbx($cat) ?>" aria-pressed="false"><?= htmlspecialcharsbx($cat) ?> <span class="nd-vr__chip-n"><?= (int)$n ?></span></button>
			<? endforeach; ?>
		</div>
	<? endif; ?>
	<? if ($arParams['BRAND_FILTER'] !== 'N' && count($arResult['BRANDS'] ?? []) > 1): ?>
		<div class="nd-vr__chips nd-vr__chips--brands" role="group" aria-label="Марки" data-nd-vr-filter="brand">
			<button type="button" class="nd-vr__chip is-active" data-nd-vr-val="" aria-pressed="true">Все марки</button>
			<? foreach ($arResult['BRANDS'] as $brandId => $b): ?>
				<button type="button" class="nd-vr__chip" data-nd-vr-val="<?= (int)$brandId ?>" aria-pressed="false"><?= htmlspecialcharsbx($b['NAME']) ?> <span class="nd-vr__chip-n"><?= (int)$b['COUNT'] ?></span></button>
			<? endforeach; ?>
		</div>
	<? endif; ?>

	<div class="nd-vr__rail" data-nd-vr-rail>
		<? foreach ($arResult['ITEMS'] as $i => $v): ?>
			<article class="nd-vr__card" data-nd-vr-i="<?= $i ?>" data-nd-vr-cat="<?= htmlspecialcharsbx($v['CATEGORY']) ?>" data-nd-vr-brand="<?= (int)$v['BRAND'] ?>" data-nd-vr-type="<?= htmlspecialcharsbx($v['TYPE']) ?>">
				<button type="button" class="nd-vr__thumb" data-nd-vr-open="<?= $i ?>" aria-label="Смотреть видео: <?= htmlspecialcharsbx($v['NAME']) ?>">
					<? if ($v['PICTURE']): ?>
						<img class="nd-vr__img" src="<?= htmlspecialcharsbx($v['PICTURE']['SRC']) ?>" width="<?= $v['PICTURE']['WIDTH'] ?>" height="<?= $v['PICTURE']['HEIGHT'] ?>" alt="<?= htmlspecialcharsbx($v['NAME']) ?>" loading="lazy" decoding="async">
					<? endif; ?>
					<span class="nd-vr__play" aria-hidden="true"><svg width="12" height="13" viewBox="0 0 12 13"><path d="M11.2 5.63a1 1 0 0 1 0 1.74l-9 5.2A1 1 0 0 1 .7 11.7V1.3A1 1 0 0 1 2.2.43l9 5.2Z" fill="currentColor"/></svg></span>
					<? if ($v['SERIES'] !== ''): ?>
						<span class="nd-vr__series"><?= htmlspecialcharsbx($v['SERIES']) ?></span>
					<? endif; ?>
					<? if ($v['DURATION'] !== ''): ?>
						<span class="nd-vr__dur"><?= htmlspecialcharsbx($v['DURATION']) ?></span>
					<? endif; ?>
				</button>
				<div class="nd-vr__body">
						<?/* подпись марки/типа над названием убрана (Ирина, 28.10.2026) */?>
						<?/* Название — настоящая ссылка на товар: её видят поисковики (до этого
						   адрес товара подставлял только скрипт окна). Обычный клик открывает
						   окно с видео (script.js), Ctrl/колёсико — товар в новой вкладке. */?>
						<h3 class="nd-vr__name">
							<? if (!empty($v['PRODUCTS'][0]['URL']) || !empty($v['PROJECT']['URL'])): ?>
								<a class="nd-vr__link" href="<?= htmlspecialcharsbx($v['PRODUCTS'][0]['URL'] ?? $v['PROJECT']['URL']) ?>"><?= htmlspecialcharsbx($v['NAME']) ?></a>
							<? else: ?>
								<?= htmlspecialcharsbx($v['NAME']) ?>
							<? endif; ?>
						</h3>
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
				<a class="nd-vr-modal__link" data-nd-vr-link href="<?= htmlspecialcharsbx($arResult['ITEMS'][0]['PRODUCTS'][0]['URL'] ?? '/catalog/') ?>">Перейти к товару →</a>
			</div>
		</div>
	</div>

	<script type="application/json" data-nd-vr-data><?= Json::encode($ndVrData) ?></script>
</section>
<? if ($ndVrLd): ?>
<script type="application/ld+json"><?= Json::encode(['@context' => 'https://schema.org', '@graph' => $ndVrLd]) ?></script>
<? endif; ?>
