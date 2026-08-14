<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?php
/**
 * Блок «Доставка» в правой колонке корзины — под «Уточните наличие и условия
 * доставки» (макет Figma «Чистовик», фрейм корзины 20496:82623).
 *
 * Строк всего две и они постоянные, поэтому состав задан здесь списком, а не
 * инфоблоком — так же сделаны тизеры-преимущества на контактах. «Подробнее»
 * ведёт на ту же страницу, что пункт «Доставка» в подвале (/info/dostavka/).
 *
 * Иконки нарисованы прямо в разметке: они красятся currentColor, а отдельными
 * файлами пришлось бы возиться с масками, как в мобильной панели.
 */
$ndDeliveryUrl = SITE_DIR.'info/dostavka/';
?>
<div class="nd-basket-delivery">
	<div class="nd-basket-delivery__title">Доставка</div>

	<div class="nd-basket-delivery__row">
		<span class="nd-basket-delivery__ico" aria-hidden="true">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
				stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round">
				<path d="M12 2.6 20.5 7v10L12 21.4 3.5 17V7L12 2.6Z"/>
				<path d="M3.5 7 12 11.4 20.5 7"/>
				<path d="M12 11.4v10"/>
				<path d="M7.75 4.8l8.5 4.4"/>
			</svg>
		</span>
		<span class="nd-basket-delivery__name">Самовывоз</span>
		<span class="nd-basket-delivery__note">Из магазина</span>
		<span class="nd-basket-delivery__value">Бесплатно</span>
	</div>

	<a class="nd-basket-delivery__row nd-basket-delivery__row--link" href="<?=$ndDeliveryUrl?>">
		<span class="nd-basket-delivery__ico" aria-hidden="true">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
				<path d="M3 5.5h9.5a1 1 0 0 1 1 1v8.4H2V6.5a1 1 0 0 1 1-1Z"/>
				<path d="M15 8.4h2.6a2 2 0 0 1 1.6.8l2.2 3a2 2 0 0 1 .4 1.2v1.5h-6.8V8.4Z"/>
				<circle cx="7.2" cy="17.4" r="2.4"/>
				<circle cx="17.4" cy="17.4" r="2.4"/>
			</svg>
		</span>
		<span class="nd-basket-delivery__name">Транспортной компанией</span>
		<span class="nd-basket-delivery__more">
			Подробнее
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor"
				stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="m6 3.5 5 4.5-5 4.5"/>
			</svg>
		</span>
	</a>
</div>
