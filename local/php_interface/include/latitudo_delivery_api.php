<?php
/**
 * API калькулятора доставки LATITUDO (Ирина, 02.10.2026).
 *
 * Документация — «Интеграция калькулятора доставки» на
 * monitor.latitudo-scrum.ru (Калькулятор → Для разработчиков). Ключ —
 * профиль «Менеджер» (цена для клиента с наценкой и НДС, та же, что
 * считал публичный фрейм), лежит вне папки сайта и вне git:
 * ~/.latitudo_delivery_api.php, возвращает ['key' => 'latcalc_…'].
 * Ключ в браузер не попадает: запросы идут только с сервера
 * (/local/ajax/nd_delivery_calc.php). Нет файла (локальная сборка) —
 * блок расчёта в корзине не выводится.
 */

const ND_DELIVERY_API_URL = 'https://monitor.latitudo-scrum.ru/calculator/api';

/**
 * Склады отгрузки. Точку склада калькулятор берёт из пары код ATI + текст:
 * с текстом «Белгород» и кодом 13033 он ставил точку не туда (1000 км от
 * Белгорода до Белгорода). Пары сверены с ответами фрейма и API 02.10.2026:
 * Москва → Белгород 639 км, 11 513 ₽ — как во фрейме.
 */
const ND_DELIVERY_WAREHOUSES = array(
	'msk' => array('name' => 'Москва',    'atiCityId' => 22549, 'text' => 'д. Купчинино, Домодедово'),
	'krd' => array('name' => 'Краснодар', 'atiCityId' => 24942, 'text' => 'пос. Колосистый'),
	'bel' => array('name' => 'Белгород',  'atiCityId' => 13033, 'text' => 'с. Таврово'),
	'vrn' => array('name' => 'Воронеж',   'atiCityId' => 40,    'text' => 'г. Воронеж'),
);

/** Ключ API или '' — если файла нет. */
function ndDeliveryApiKey(): string
{
	static $key = null;
	if ($key === null) {
		$cfg = @include dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/.latitudo_delivery_api.php';
		$key = (is_array($cfg) && !empty($cfg['key'])) ? (string)$cfg['key'] : '';
	}
	return $key;
}

/**
 * Запрос к API. $body === null — GET, иначе POST с JSON.
 * Возвращает [код HTTP, ответ массивом или null]. Код 0 — нет связи.
 */
function ndDeliveryApiRequest(string $path, ?array $body = null, int $timeout = 60): array
{
	$key = ndDeliveryApiKey();
	if ($key === '') {
		return array(0, null);
	}
	$ch = curl_init(ND_DELIVERY_API_URL . $path);
	$opts = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT        => $timeout,
		CURLOPT_HTTPHEADER     => array('X-API-Key: ' . $key, 'Accept: application/json'),
	);
	if ($body !== null) {
		/* Content-Type: application/json — только у POST: с ним GET
		   /ati-autocomplete отвечал 400 Bad Request (проверено 02.10.2026). */
		$opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
		$opts[CURLOPT_POST] = true;
		$opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
	}
	curl_setopt_array($ch, $opts);
	$raw = curl_exec($ch);
	$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$data = is_string($raw) ? json_decode($raw, true) : null;
	return array($code, is_array($data) ? $data : null);
}

/**
 * Состав корзины для калькулятора: название как в корзине, количество,
 * единица (шт / м / упак) — так же передавали во фрейм.
 *
 * @param iterable $items элементы \Bitrix\Sale\BasketItem
 */
function ndDeliveryBasketPositions($items): array
{
	$positions = array();
	foreach ($items as $item) {
		$unitRaw = mb_strtolower(trim((string)$item->getField('MEASURE_NAME')));
		if ($unitRaw === 'м' || mb_strpos($unitRaw, 'метр') !== false || mb_strpos($unitRaw, 'пог') !== false) {
			$unit = 'м';
		} elseif (mb_strpos($unitRaw, 'упак') !== false || mb_strpos($unitRaw, 'уп.') !== false) {
			$unit = 'упак';
		} else {
			$unit = 'шт';
		}
		$positions[] = array(
			'name' => (string)$item->getField('NAME'),
			'qty'  => (float)$item->getQuantity(),
			'unit' => $unit,
		);
	}
	return $positions;
}
