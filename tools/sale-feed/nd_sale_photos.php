<?php
/**
 * Фото товарам раздела «Распродажа» — копии фото того же товара из основного каталога (26.10.2026).
 *
 *   php nd_sale_photos.php <DOCROOT>                 — сухой прогон: что откуда
 *   php nd_sale_photos.php <DOCROOT> apply           — скопировать фото
 *   php nd_sale_photos.php <DOCROOT> rollback=<done.json> — убрать поставленные фото
 *
 * Источник подобран по названию, размеру и цвету (sku из фида — ID торгового предложения, но не всегда
 * точный: у ступеней Step-345 в фиде sku предложений Стэп-342 — та же ступень, на сайте названа 342).
 * Берём главное фото, анонс и MORE_PHOTO источника; файлы копируются, источник не меняется.
 * Без пары оставлены Woodvex Select (на сайте только со-экструзия других цветов) и Expert Colorite.
 */

$args = array_slice($argv, 1);
$root = array_shift($args);
$mode = 'dry';
$rollbackFile = '';
foreach ($args as $a) {
	if ($a === 'apply') {
		$mode = 'apply';
	} elseif (strpos($a, 'rollback=') === 0) {
		$mode = 'rollback';
		$rollbackFile = substr($a, 9);
	} else {
		fwrite(STDERR, "Неизвестный параметр: $a\n");
		exit(1);
	}
}
if (!$root || !is_dir($root)) {
	fwrite(STDERR, "Укажите корень сайта первым параметром\n");
	exit(1);
}

const ND_IBLOCK_ID = 19;
// XML_ID товара распродажи => [ID источника фото, как совпал]
const ND_PHOTO_SOURCE = [
	'rasprodaja-ecd863d1-8d71-11ec-80df-99c0047e77b0' => [23150, 'тот же товар'],
	'rasprodaja-41b00694-8d71-11ec-80df-99c0047e77b0' => [23138, 'тот же товар'],
	'rasprodaja-98dcc48c-8d71-11ec-80df-99c0047e77b0' => [23139, 'тот же товар'],
	'rasprodaja-a8014faf-2255-11eb-80df-9cd70a25022f' => [23475, 'тот же товар'],
	'rasprodaja-462f1f5a-8d99-11ec-80df-99c0047e77b0' => [24943, 'тот же товар'],
	'rasprodaja-c3c4de67-d542-11ee-8119-a81998b93c55' => [23157, 'тот же товар'],
	'rasprodaja-8fc143bb-8d72-11ec-80df-99c0047e77b0' => [23155, 'тот же товар (на сайте Стэп-342)'],
	'rasprodaja-b32ccd3d-8d72-11ec-80df-99c0047e77b0' => [23154, 'тот же товар (на сайте Стэп-342)'],
	'rasprodaja-9b7aabd0-8d72-11ec-80df-99c0047e77b0' => [23156, 'тот же товар (на сайте Стэп-342)'],
	'rasprodaja-c3b1ed02-8d72-11ec-80df-99c0047e77b0' => [23157, 'тот же товар (на сайте Стэп-342)'],
	'rasprodaja-9b7aabcf-8d72-11ec-80df-99c0047e77b0' => [23159, 'тот же товар (на сайте Стэп-342)'],
	'rasprodaja-a8399b95-8d72-11ec-80df-99c0047e77b0' => [23160, 'тот же товар (на сайте Стэп-342)'],
	'rasprodaja-c890c77f-020e-11ef-8124-d1c3d31db83f' => [26432, 'тот же товар'],
	'rasprodaja-3d946e43-fb01-11ee-8124-d1c3d31db83f' => [26404, 'тот же товар (предложение выключено)'],
	'rasprodaja-1b86e3d1-b9ba-11ed-80fb-cd873c161f6a' => [25625, 'тот же товар'],
	'rasprodaja-ea32d52a-3203-11ee-8102-fd7d7d6ab93b' => [25866, 'тот же товар'],
	'rasprodaja-4b0a9a21-3203-11ee-8102-fd7d7d6ab93b' => [25865, 'тот же товар'],
	'rasprodaja-26d23b90-bc42-11ee-810d-e87e8355d38a' => [25744, 'тот же товар'],
	'rasprodaja-b3d36fcc-5276-11ec-80db-9da37be214e6' => [23772, 'похожий: Заборная доска Wood 130х11х2000 Темно-коричневый'],
	'rasprodaja-bc546707-e31e-11e8-acfe-74d4359935c6' => [11010, 'похожий: Заборная доска с фаской Latitudo 118x11х4000 Шоколад'],
	'rasprodaja-c646ca01-1b08-11ee-80ff-836afc7e107a' => [25769, 'похожий: Ступень ДПК 340х22х4000 Венге Полнотелая'],
	'rasprodaja-8a22d06e-26ce-11ee-8101-a52c7e43c9a7' => [23483, 'похожий: U-профиль СМ Декинг 40х30х2000 Мербау (другой размер)'],
];

$_SERVER['DOCUMENT_ROOT'] = realpath($root);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
@set_time_limit(0);
\Bitrix\Main\Loader::includeModule('iblock');

$outDir = (getenv('HOME') ?: __DIR__) . '/sale-feed/' . date('Y-m-d_His') . '_photos_' . $mode;
mkdir($outDir, 0700, true);

if ($mode === 'rollback') {
	$done = json_decode((string)file_get_contents($rollbackFile), true);
	$el = new CIBlockElement();
	foreach ((array)$done as $id) {
		$el->Update((int)$id, ['DETAIL_PICTURE' => ['del' => 'Y'], 'PREVIEW_PICTURE' => ['del' => 'Y']]);
		$vals = [];
		$rs = CIBlockElement::GetProperty(ND_IBLOCK_ID, (int)$id, [], ['CODE' => 'MORE_PHOTO']);
		while ($p = $rs->Fetch()) {
			if ($p['PROPERTY_VALUE_ID']) {
				$vals[$p['PROPERTY_VALUE_ID']] = ['VALUE' => ['del' => 'Y']];
			}
		}
		if ($vals) {
			CIBlockElement::SetPropertyValueCode((int)$id, 'MORE_PHOTO', $vals);
		}
	}
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	echo 'Фото убраны у товаров: ' . count((array)$done) . "\n";
	exit(0);
}

$done = [];
$el = new CIBlockElement();
$rs = CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => ND_IBLOCK_ID, 'XML_ID' => array_keys(ND_PHOTO_SOURCE)],
	false, false, ['ID', 'NAME', 'XML_ID', 'DETAIL_PICTURE']);
while ($r = $rs->Fetch()) {
	[$srcId, $how] = ND_PHOTO_SOURCE[$r['XML_ID']];
	$src = CIBlockElement::GetList([], ['ID' => $srcId], false, false, ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PICTURE', 'PREVIEW_PICTURE'])->Fetch();
	if (!$src) {
		echo "  ! {$r['ID']} {$r['NAME']}: источника $srcId нет\n";
		continue;
	}
	$more = [];
	$pr = CIBlockElement::GetProperty($src['IBLOCK_ID'], $srcId, ['sort' => 'asc'], ['CODE' => 'MORE_PHOTO']);
	while ($p = $pr->Fetch()) {
		if ($p['VALUE']) {
			$more[] = (int)$p['VALUE'];
		}
	}
	$main = (int)($src['DETAIL_PICTURE'] ?: $src['PREVIEW_PICTURE']);
	$prev = (int)($src['PREVIEW_PICTURE'] ?: $main);
	echo "{$r['ID']} {$r['NAME']}\n    ← $srcId {$src['NAME']} [$how], фото: " . ($main ? 1 : 0) . ' + ' . count($more) . "\n";
	if ($mode !== 'apply') {
		continue;
	}
	if ($r['DETAIL_PICTURE']) {
		echo "    фото уже есть — пропуск\n";
		continue;
	}
	$fields = [];
	if ($main) {
		$fields['DETAIL_PICTURE'] = CFile::MakeFileArray($main);
	}
	if ($prev) {
		$fields['PREVIEW_PICTURE'] = CFile::MakeFileArray($prev);
	}
	if ($fields && !$el->Update((int)$r['ID'], $fields)) {
		echo "    ! {$el->LAST_ERROR}\n";
		continue;
	}
	if ($more) {
		CIBlockElement::SetPropertyValuesEx((int)$r['ID'], ND_IBLOCK_ID, ['MORE_PHOTO' => array_map(function ($f) {
			return ['VALUE' => CFile::MakeFileArray($f)];
		}, $more)]);
	}
	$done[] = (int)$r['ID'];
	file_put_contents($outDir . '/done.json', json_encode($done));
}
CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
if ($mode === 'apply') {
	echo 'Фото поставлены: ' . count($done) . "\nОткат: php " . __FILE__ . " $root rollback=$outDir/done.json\n";
} else {
	echo "Сухой прогон — ничего не изменено. Поставить: добавьте apply\n";
}
