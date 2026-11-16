<?php
/**
 * «Цена по запросу» у товаров без цены (Ирина, 16.11.2026).
 *
 * Зачем. У 4SIS на 4sis.ru у товаров «Под заказ от 10 дней» цены нет, а в фиде
 * для них приходят случайные числа (у «Эспрессо–150 S» коричневого – 57 800 вместо
 * 218 700). Решение Ирины: если у поставщика на сайте цены нет – убираем цену и у
 * нас (ночная сверка ~/4sis-sync, tools/4sis-price-sync), а покупателю показываем
 * «Цена по запросу» и кнопку «Узнать цену».
 *
 * Как. Товар без цены Аспро и так не даёт положить в корзину: вместо кнопки
 * корзины – «Заказать» (форма TOORDER, название товара подставляется). Кнопку
 * собирает CNext::GetAddToBasketArray для всех шаблонов (плитка, список, карточка,
 * акции, поиск), в конце он зовёт событие OnAsproGetBuyBlockElement – здесь
 * меняем подпись кнопки и ставим над ней «Цена по запросу». Только новый дизайн и
 * только простой товар (у товара с предложениями цена – у предложений).
 *
 * Шаблоны кешируются – после выкладки снести кеш catalog.section / catalog.element.
 */

function ndPriceRequestBuyBlock($arItem, $totalCount, $arParams, &$arOptions)
{
	if (!defined('SITE_TEMPLATE_ID') || SITE_TEMPLATE_ID !== 'aspro_next_newdesign') {
		return;
	}
	if (($arOptions['ACTION'] ?? '') !== 'ORDER' || !empty($arItem['OFFERS'])) {
		return;
	}
	if (isset($arItem['MIN_PRICE']['VALUE']) && $arItem['MIN_PRICE']['VALUE'] > 0) {
		return;
	}
	$old = (string)($arOptions['TEXT'][0] ?? '');
	if ($old === '' || strpos($arOptions['HTML'], 'to-order') === false) {
		return;
	}
	$arOptions['TEXT'][0] = 'Узнать цену';
	$arOptions['HTML'] = '<div class="nd-price-request">Цена по запросу</div>'
		. str_replace('<span>' . $old . '</span>', '<span>Узнать цену</span>', $arOptions['HTML']);
}
