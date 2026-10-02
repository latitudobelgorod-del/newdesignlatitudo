<?php
/**
 * Расчёт доставки из корзины → комментарий заказа и лида (Ирина, 28.09.2026).
 *
 * На /basket/ посетитель считает доставку во фрейме LATITUDO (блок
 * local/include/basket_delivery_calc.php) и ставит или снимает галочку
 * «С доставкой». Блок сохраняет расчёт в сессию через
 * /local/ajax/nd_delivery_quote.php, а здесь, при создании заказа, — если
 * галочка стоит — всё, что посчитал фрейм (склад отгрузки, куда, расстояние,
 * транспорт, вес, состав), дописывается в комментарий покупателя
 * (USER_DESCRIPTION). Его видит менеджер в заказе, с ним заказ уходит в Б24.
 * Быстрый заказ (oneclickbuy.next) шлёт лид сам — текст для него кладём в
 * $GLOBALS['ND_DELIVERY_QUOTE_TEXT'], компонент дописывает его в COMMENTS.
 *
 * Сумма заказа не меняется: цена пришла из браузера и считается
 * предварительной. В лиде быстрого заказа «Сумма» — товары + доставка,
 * а сумма за товары отдельной строкой в комментарии (02.10.2026). Расчёт пропускаем, если он старше суток или состав заказа
 * не совпадает с корзиной на момент расчёта (товар добавили/убрали, поменяли
 * количество). После заказа расчёт из сессии удаляется в любом случае.
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

/** Строка из браузера → одна строка текста без тегов, не длиннее $max. */
function ndDeliveryQuoteClean($value, int $max = 200): string
{
	$value = strip_tags((string)$value);
	if (!mb_check_encoding($value, 'UTF-8')) {
		return '';
	}
	$value = trim(preg_replace('/\s+/u', ' ', $value));
	return mb_substr($value, 0, $max);
}

/** Текст расчёта для комментария заказа/лида. Пустой — если «Без доставки». */
function ndDeliveryQuoteText(array $quote): string
{
	if (empty($quote['WITH'])) {
		return '';
	}
	$lines = array(
		'Доставка (расчёт в корзине, предварительно): '
			. number_format((float)$quote['PRICE'], 0, '.', ' ') . ' ₽'
			. (!empty($quote['VAT']) ? ' с НДС' : ''),
	);
	$fields = array(
		'FROM'     => 'Склад отгрузки',
		'TO'       => 'Куда',
		'DISTANCE' => 'Расстояние',
		'TRUCK'    => 'Транспорт (схема загрузки)',
		'WEIGHT'   => 'Вес',
		'ITEMS'    => 'Состав для расчёта',
		'NOTES'    => 'Примечания',
	);
	foreach ($fields as $code => $title) {
		$value = trim((string)($quote[$code] ?? ''));
		if ($value !== '') {
			$lines[] = $title . ': ' . $value;
		}
	}
	return implode("\n", $lines);
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

	$text = ndDeliveryQuoteText($quote);
	if ($text === '') {
		return;
	}
	$GLOBALS['ND_DELIVERY_QUOTE_TEXT'] = $text;
	// Сумма доставки — для поля «Сумма» лида быстрого заказа (товары + доставка), 02.10.2026.
	$GLOBALS['ND_DELIVERY_QUOTE_PRICE'] = max(0, (float)$quote['PRICE']);

	$comment = trim((string)$order->getField('USER_DESCRIPTION'));
	$order->setField('USER_DESCRIPTION', $comment !== '' ? $comment . "\n\n" . $text : $text);
}
