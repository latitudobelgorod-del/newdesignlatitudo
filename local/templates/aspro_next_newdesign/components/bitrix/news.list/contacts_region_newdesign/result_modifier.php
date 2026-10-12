<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Контакты региона (новый дизайн): подготовка данных.
 * Название города берём из связанного региона (ИБ 7, свойство LINK_REGION) —
 * им подписаны кнопки «Схема проезда … г. Москва». LINK_REGION множественное,
 * значение приходит массивом.
 * Галерея — свойство MORE_PHOTOS (тип «файл», множественное): режем под
 * размер макета 441×294 с двойной плотностью.
 */

/* Карта из конструктора 2ГИС (makemap.2gis.ru) — iframe, у которого внутри
   свой код делает history.pushState при загрузке и при каждом сдвиге карты.
   Записи попадают в общую историю вкладки, и «Назад» в браузере сначала
   листал карты, а не уводил со страницы (12.10.2026). Снаружи это не
   отключить, поэтому iframe не выводим: из параметра data (JSON, сжатый zlib,
   base64url) берём центр, масштаб и точки, а карту рисует script.js через
   API 2ГИС 2.0 — тот историю не трогает. Не разобрали — отдаём как было. */
$ndTwoGisMap = static function (string $html): string {
	if (!preg_match('~makemap\.2gis\.ru/widget\?data=([A-Za-z0-9_\-]+)~', $html, $m)) {
		return $html;
	}
	$raw = base64_decode(strtr($m[1], '-_', '+/'), true);
	$json = $raw !== false ? @gzuncompress($raw) : false;
	$data = $json !== false ? json_decode($json, true) : null;
	$pos = $data['mapPosition'] ?? null;
	if (!is_array($pos) || !isset($pos['lat'], $pos['lon'])) {
		return $html;
	}

	$points = [];
	foreach ((array) ($data['orderedGeometries'] ?? []) as $geo) {
		if (($geo['geometry']['type'] ?? '') !== 'Point') {
			continue;
		}
		[$lon, $lat] = $geo['geometry']['coordinates'] + [null, null];
		if (!is_numeric($lat) || !is_numeric($lon)) {
			continue;
		}
		$title = base64_decode((string) ($geo['properties']['title'] ?? ''), true);
		$descr = base64_decode((string) ($geo['properties']['description'] ?? ''), true);
		$points[] = [
			'lat' => (float) $lat,
			'lon' => (float) $lon,
			'title' => trim(strip_tags((string) $title)),
			'descr' => trim(strip_tags((string) $descr)),
		];
	}

	$map = [
		'lat' => (float) $pos['lat'],
		'lon' => (float) $pos['lon'],
		'zoom' => (int) ($pos['zoom'] ?? 16),
		'wheel' => !empty($data['scrollWheelZoom']),
		'points' => $points,
	];

	return '<div class="nd-region__2gis" data-nd-2gis="'
		.htmlspecialcharsbx(json_encode($map, JSON_UNESCAPED_UNICODE)).'"></div>';
};

foreach ($arResult['ITEMS'] as &$arItem) {
	$regionId = $arItem['PROPERTIES']['LINK_REGION']['VALUE'] ?? 0;
	if (is_array($regionId)) {
		$regionId = reset($regionId);
	}
	$regionId = (int) $regionId;

	$arItem['ND_CITY'] = '';
	if ($regionId > 0) {
		$res = CIBlockElement::GetList([], ['IBLOCK_ID' => 7, 'ID' => $regionId], false, false, ['ID', 'NAME']);
		if ($row = $res->GetNext()) {
			$arItem['ND_CITY'] = $row['NAME'];
		}
	}

	$arItem['ND_GALLERY'] = [];
	$photos = $arItem['PROPERTIES']['MORE_PHOTOS']['VALUE'] ?? [];
	foreach ((array) $photos as $fileId) {
		$fileId = (int) $fileId;
		if ($fileId <= 0) {
			continue;
		}
		$img = CFile::ResizeImageGet($fileId, ['width' => 882, 'height' => 588], BX_RESIZE_IMAGE_EXACT, true);
		if (!empty($img['src'])) {
			$arItem['ND_GALLERY'][] = $img['src'];
		}
	}

	/* Карты из конструктора Яндекса (готовый <iframe> строкой).
	   Основные свойства — YMAP_CONSTR_OFFICE и YMAP_CONSTR_SKLAD: у каждой
	   карты своя кнопка-переключатель, и офис со складом больше не зависят
	   от порядка значений. Оба множественные — берём первое непустое.

	   Если новых свойств нет (их не завели или это старая копия базы),
	   откатываемся на общее YMAP_CONSTR: там первое значение — офис,
	   второе — склад, как было до появления отдельных свойств. */
	$ndFirstMap = static function ($values) {
		foreach ((array) $values as $value) {
			$value = trim((string) $value);
			if ($value !== '') {
				return $value;
			}
		}

		return '';
	};

	/* Карта 2ГИС (TWO_GIS_CONSTR_OFFICE / TWO_GIS_CONSTR_SKLAD, тоже готовый
	   <iframe>) заменяет яндексовую той же точки, если заполнена. */
	$ndOffice = $ndFirstMap($arItem['PROPERTIES']['TWO_GIS_CONSTR_OFFICE']['~VALUE'] ?? '');
	if ($ndOffice === '') {
		$ndOffice = $ndFirstMap($arItem['PROPERTIES']['YMAP_CONSTR_OFFICE']['~VALUE'] ?? '');
	}
	$ndStore = $ndFirstMap($arItem['PROPERTIES']['TWO_GIS_CONSTR_SKLAD']['~VALUE'] ?? '');
	if ($ndStore === '') {
		$ndStore = $ndFirstMap($arItem['PROPERTIES']['YMAP_CONSTR_SKLAD']['~VALUE'] ?? '');
	}

	$ndOffice = $ndTwoGisMap($ndOffice);
	$ndStore = $ndTwoGisMap($ndStore);

	$arItem['ND_MAPS'] = [];

	if ($ndOffice !== '' || $ndStore !== '') {
		if ($ndOffice !== '') {
			$arItem['ND_MAPS'][] = ['TYPE' => 'office', 'HTML' => $ndOffice];
		}
		if ($ndStore !== '') {
			$arItem['ND_MAPS'][] = ['TYPE' => 'store', 'HTML' => $ndStore];
		}
	} else {
		$maps = array_values(array_filter(array_map('trim', (array) ($arItem['PROPERTIES']['YMAP_CONSTR']['~VALUE'] ?? ''))));
		foreach ($maps as $i => $html) {
			$arItem['ND_MAPS'][] = ['TYPE' => $i === 0 ? 'office' : 'store', 'HTML' => $html];
		}
	}
}
unset($arItem);
?>
