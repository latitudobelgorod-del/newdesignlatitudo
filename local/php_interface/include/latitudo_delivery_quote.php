<?php
/**
 * Расчёт доставки из корзины → комментарий заказа (Ирина, 28.09.2026).
 *
 * На /basket/ посетитель считает доставку во фрейме LATITUDO (блок
 * local/include/basket_delivery_calc.php) и ставит или снимает галочку
 * «С доставкой». Блок сохраняет расчёт в сессию через
 * /local/ajax/nd_delivery_quote.php, а здесь, при создании заказа, расчёт
 * дописывается в комментарий покупателя (USER_DESCRIPTION) — его видит
 * менеджер. Сумма заказа не меняется: цена пришла из браузера и считается
 * предварительной.
 *
 * Расчёт пропускаем, если он старше суток или состав заказа не совпадает с
 * корзиной на момент расчёта (товар добавили/убрали, поменяли количество).
 * После заказа расчёт из сессии удаляется в любом случае.
 */

use Bitrix\Main\Event;

/**
 * Состав корзины строкой «ID товара:количество» по возрастанию ID —
 * для сверки корзины на момент расчёта с составом заказа.
 *
 * @param iterable $items элементы \Bitrix\Sale\BasketItem
 */
function ndDeliveryQuoteBasketKey($items): string
{
	$parts = array();
	foreach ($items as $item) {
		$productId = (int)$item->getProductId();
		if ($productId <= 0) {
			continue;
		}
		$parts[$productId] = ($parts[$productId] ?? 0) + (float)$item->getQuantity();
	}
	ksort($parts);
	$key = array();
	foreach ($parts as $productId => $qty) {
		$key[] = $productId . ':' . rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.');
	}
	return implode(',', $key);
}

/** Обработчик sale:OnSaleOrderBeforeSaved — регистрируется в local/init.php. */
function ndDeliveryQuoteToOrder(Event $event)
{
	/** @var \Bitrix\Sale\Order $order */
	$order = $event->getParameter('ENTITY');
	if (!$order || !$order->isNew()) {
		return;
	}
	$quote = $_SESSION['ND_DELIVERY_QUOTE'] ?? null;
	if (!is_array($quote)) {
		return;
	}
	unset($_SESSION['ND_DELIVERY_QUOTE']);

	if (time() - (int)($quote['TIME'] ?? 0) > 86400) {
		return;
	}
	$basket = $order->getBasket();
	if (!$basket || ndDeliveryQuoteBasketKey($basket->getOrderableItems()) !== (string)($quote['KEY'] ?? '')) {
		return;
	}

	$price = number_format((float)$quote['PRICE'], 0, '.', ' ') . ' ₽';
	$to = trim((string)($quote['TO'] ?? ''));
	$text = 'Расчёт доставки в корзине (предварительно): '
		. ($to !== '' ? $to . ' — ' : '') . $price . '. '
		. (!empty($quote['WITH'])
			? 'Покупатель выбрал «С доставкой».'
			: 'Покупатель выбрал «Без доставки».');

	$comment = trim((string)$order->getField('USER_DESCRIPTION'));
	$order->setField('USER_DESCRIPTION', $comment !== '' ? $comment . "\n\n" . $text : $text);
}
