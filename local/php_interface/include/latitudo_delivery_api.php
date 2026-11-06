<?php
/**
 * API калькулятора доставки LATITUDO (Ирина, 02.10.2026).
 *
 * Документация — «Интеграция калькулятора доставки» (Калькулятор → Для
 * разработчиков). С 06.11.2026 API на core.latitudo-web.ru: старый адрес
 * monitor.latitudo-scrum.ru/calculator/api остался на отключённом сервере
 * (ключ и профиль прежние). Ключ —
 * профиль «Менеджер» (цена для клиента с наценкой и НДС, та же, что
 * считал публичный фрейм), лежит вне папки сайта и вне git:
 * ~/.latitudo_delivery_api.php, возвращает ['key' => 'latcalc_…'].
 * Ключ в браузер не попадает: запросы идут только с сервера
 * (/local/ajax/nd_delivery_calc.php). Нет файла (локальная сборка) —
 * блок расчёта в корзине не выводится.
 */

const ND_DELIVERY_API_URL = 'https://core.latitudo-web.ru/api/public/calculator';

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

/** ID единицы «шт» в справочнике единиц каталога. */
const ND_DELIVERY_MEASURE_PCS = 5;

/**
 * Сколько штук в одной единице товара — по свойству UNIT_KOEF (значение —
 * цена штуки относительно основной единицы, в DESCRIPTION — ID единицы):
 * у кляймера «Фикс» 0.01 при шт → 100 шт в упаковке. Сначала свойство
 * самого предложения, нет — товара-родителя. Не заведено — 0.
 *
 * @param int[] $productIds ID товаров/предложений из корзины
 * @return array<int,float> ID => штук в единице
 */
function ndDeliveryPiecesPerUnit(array $productIds): array
{
	$productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
	if (!$productIds || !\Bitrix\Main\Loader::includeModule('iblock') || !\Bitrix\Main\Loader::includeModule('catalog')) {
		return array();
	}
	$parentOf = array();
	foreach (\CCatalogSku::getProductList($productIds) ?: array() as $id => $info) {
		$parentOf[(int)$id] = (int)$info['ID'];
	}
	$ids = array_values(array_unique(array_merge($productIds, array_values($parentOf))));
	$koef = array();
	$res = \CIBlockElement::GetList(array(), array('ID' => $ids), false, false, array('ID', 'IBLOCK_ID'));
	$byIblock = array();
	while ($e = $res->Fetch()) {
		$byIblock[(int)$e['IBLOCK_ID']][] = (int)$e['ID'];
	}
	foreach ($byIblock as $iblockId => $list) {
		$values = array();
		\CIBlockElement::GetPropertyValuesArray($values, $iblockId, array('ID' => $list), array('CODE' => array('UNIT_KOEF')));
		foreach ($values as $id => $props) {
			$vals  = (array)($props['UNIT_KOEF']['VALUE'] ?? array());
			$descs = (array)($props['UNIT_KOEF']['DESCRIPTION'] ?? array());
			foreach ($vals as $i => $v) {
				$k = (float)str_replace(',', '.', (string)$v);
				if ((int)trim((string)($descs[$i] ?? '')) === ND_DELIVERY_MEASURE_PCS && $k > 0) {
					$koef[(int)$id] = $k;
				}
			}
		}
	}
	$out = array();
	foreach ($productIds as $id) {
		$k = $koef[$id] ?? ($koef[$parentOf[$id] ?? 0] ?? 0);
		if ($k > 0 && $k < 1) {
			$out[$id] = round(1 / $k, 3);
		}
	}

	/* UNIT_KOEF нет — число из описания товара (анонс, детальное, блоки
	   EDITOR1; сначала само предложение, потом родитель): «45 клипс из
	   нержавеющей стали», «10 штук в упаковке», «В упаковке 10 штук».
	   Только однозначные фразы: «8 стартовых клипс и 8 уголков» не берём. */
	$missing = array_values(array_diff($productIds, array_keys($out)));
	if ($missing) {
		$need = array_values(array_unique(array_merge($missing, array_intersect_key($parentOf, array_flip($missing)))));
		$fromText = array();
		$res = \CIBlockElement::GetList(array(), array('ID' => $need), false, false,
			array('ID', 'IBLOCK_ID', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PROPERTY_EDITOR1'));
		while ($e = $res->Fetch()) {
			$id = (int)$e['ID'];
			$text = (string)$e['PREVIEW_TEXT'] . ' ' . (string)$e['DETAIL_TEXT'];
			$editor = $e['PROPERTY_EDITOR1_VALUE'] ?? '';
			if (is_array($editor)) {
				$editor = $editor['TEXT'] ?? '';
			}
			$json = json_decode(html_entity_decode((string)$editor, ENT_QUOTES, 'UTF-8'), true);
			if (is_array($json)) {
				array_walk_recursive($json, function ($v, $k) use (&$text) {
					if (is_string($v) && ($k === 'value' || $k === 'text')) {
						$text .= ' ' . $v;
					}
				});
			}
			$n = ndDeliveryPiecesFromText($text);
			if ($n > 0 && !isset($fromText[$id])) {
				$fromText[$id] = $n;
			}
		}
		foreach ($missing as $id) {
			$n = $fromText[$id] ?? ($fromText[$parentOf[$id] ?? 0] ?? 0);
			if ($n > 0) {
				$out[$id] = $n;
			}
		}
	}
	return $out;
}

/** Штук в упаковке по тексту описания или 0, если фраза неоднозначна. */
function ndDeliveryPiecesFromText(string $text): int
{
	$text = html_entity_decode(strip_tags(str_replace(array('<br', '</p>', '</li>'), array(' <br', ' </p>', ' </li>'), $text)), ENT_QUOTES, 'UTF-8');
	$text = preg_replace('/\s+/u', ' ', $text);
	$patterns = array(
		'/\bв\s+(?:1\s+|одной\s+)?упаковке\s*[-—:]?\s*(\d{1,5})\s*(?:шт|штук)/iu',
		'/\b(\d{1,5})\s*(?:шт\.?|штук)\s+в\s+(?:1\s+|одной\s+)?упаковке/iu',
		'/\b(\d{1,5})\s+(?:клипс|кляммер\S*|скоб)\b/iu',
	);
	$found = array();
	foreach ($patterns as $re) {
		if (preg_match_all($re, $text, $m)) {
			foreach ($m[1] as $n) {
				$found[(int)$n] = true;
			}
		}
	}
	/* Разные числа — непонятно, какое верное: пусть решает менеджер. */
	return count($found) === 1 ? (int)key($found) : 0;
}

/**
 * Состав корзины для калькулятора: название как в корзине, количество,
 * единица (шт / м / упак) — так же передавали во фрейм.
 *
 * Упаковки переводим в штуки (06.11.2026): калькулятор не знает, сколько
 * штук в упаковке, и на «упак» отвечал 422 DATA_REQUIRED — а сайт писал
 * покупателю «не помещается в одну машину». Если штук в упаковке не
 * заведено — отправляем как есть, причину покажет ответ калькулятора.
 *
 * @param iterable $items элементы \Bitrix\Sale\BasketItem
 */
function ndDeliveryBasketPositions($items): array
{
	$items = is_array($items) ? $items : iterator_to_array($items, false);
	$pcs = ndDeliveryPiecesPerUnit(array_map(function ($item) {
		return (int)$item->getProductId();
	}, $items));
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
		$qty = (float)$item->getQuantity();
		$perUnit = $pcs[(int)$item->getProductId()] ?? 0;
		/* Свойства нет — число из названия: «(в 1 уп. - 11 шт., 1 м2)». */
		if ($perUnit <= 0 && preg_match('/в\s*1\s*уп[^\d]{0,6}(\d+)\s*шт/u', (string)$item->getField('NAME'), $m)) {
			$perUnit = (int)$m[1];
		}
		if ($unit === 'упак' && $perUnit > 0) {
			$qty = round($qty * $perUnit);
			$unit = 'шт';
		}
		$positions[] = array(
			'name' => (string)$item->getField('NAME'),
			'qty'  => $qty,
			'unit' => $unit,
		);
	}
	return $positions;
}
