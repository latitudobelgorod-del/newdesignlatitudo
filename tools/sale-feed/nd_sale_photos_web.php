<?php
/**
 * Фото товарам «Распродажи» с сайтов производителей — там, где в нашем каталоге своего фото нет (26.10.2026,
 * вторая партия 06.11.2026 — по ID товара, ND_WEB_PHOTO_BY_ID).
 *
 *   php nd_sale_photos_web.php <DOCROOT>                     — сухой прогон: проверить, что картинки скачиваются
 *   php nd_sale_photos_web.php <DOCROOT> apply               — поставить (прежние фото товара заменяются)
 *   php nd_sale_photos_web.php <DOCROOT> rollback=<done.json> — убрать поставленные фото
 *   … new                                                     — только товары ND_WEB_PHOTO_BY_ID (партия 06.11)
 *   … id=<ID>[,<ID>…]                                         — только эти товары из ND_WEB_PHOTO_BY_ID
 *
 * Woodvex Select (товаров на сайте нет, удалены вместе с картинками) — фото профиля нужного цвета с
 * woodvex.com (страницы серии Select: ограждения, ступени, аксессуары). NEXTWOOD — с nextwood.ru,
 * вместо «похожей» заборной доски Wood, поставленной nd_sale_photos.php.
 * Без фото остаётся доска Expert Colorite: у Woodvex сейчас только Select, Select 3D и Antique.
 *
 * 06.11.2026: Expert Colorite Палисандр — фото Select Colorite Палисандр (тот же цвет, похожий профиль);
 * PG SINGARAJA крышка и юбка — общая картинка «крышка и юбка» с polivan.com (webp → jpg).
 */

$args = array_slice($argv, 1);
$root = array_shift($args);
$mode = 'dry';
$rollbackFile = '';
$onlyNew = false;
$onlyId = [];
foreach ($args as $a) {
	if ($a === 'new') {
		$onlyNew = true;
	} elseif (strpos($a, 'id=') === 0) {
		$onlyNew = true;
		$onlyId = array_filter(array_map('intval', explode(',', substr($a, 3))));
	} elseif ($a === 'apply') {
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
const ND_WV = 'https://woodvex.com/wp-content/uploads/2017/12/img-';
// XML_ID товара распродажи => адрес картинки
const ND_WEB_PHOTO = [
	'rasprodaja-1e2635bb-7840-11e8-a7b5-74d4359935c6' => ND_WV . 'Ugolok_Select_Wenge.jpg',           // L-планка Венге
	'rasprodaja-7331d3b9-f7fe-11ea-8e2e-74d4359935c6' => ND_WV . 'Ugolok_Select_Wood.jpg',            // L-планка Вуд
	'rasprodaja-6ebf1a9b-b4dc-11e8-b936-74d4359935c6' => ND_WV . 'Ugolok_Select_Smoke_gray_nakat.jpg', // L-планка Серый дым
	'rasprodaja-769b7e01-daab-11e9-bf97-74d4359935c6' => ND_WV . 'Baljasina_Wood.jpg',                // Балясина Вуд
	'rasprodaja-d76820c1-5230-11ea-9b16-74d4359935c6' => ND_WV . 'Baljasina_Coffee.jpg',              // Балясина Кофе
	'rasprodaja-bbd40fd0-5cc1-11e8-972b-74d4359935c6' => ND_WV . 'Baljasina_Dark_brown.jpg',          // Балясина Темно-коричневый
	'rasprodaja-7f5b7a3c-7de8-11e8-ac93-74d4359935c6' => ND_WV . 'Perila_verkhnjaja_Wood.jpg',        // Перила верхние Вуд
	'rasprodaja-c8b27199-04e8-11e9-9286-74d4359935c6' => ND_WV . 'Perila_verkhnjaja_Graphite.jpg',    // Перила верхние Графит
	'rasprodaja-bbd40fca-5cc1-11e8-972b-74d4359935c6' => ND_WV . 'Stolb_Dark_brown.jpg',              // Столб Темно-коричневый
	'rasprodaja-8f1a7771-8f08-11e8-b803-74d4359935c6' => ND_WV . 'Stupen_Select_Wenge.jpg',           // Ступень Венге
	'rasprodaja-910e4d54-dc46-11e9-bf97-74d4359935c6' => ND_WV . 'Stupen_Select_Coffee.jpg',          // Ступень Кофе
	'rasprodaja-b3d36fcc-5276-11ec-80db-9da37be214e6' =>
		'https://nextwood.ru/wp-content/uploads/2024/03/Doska-zabornaya-NEXTWOOD-11h130-mm-tyomno-korichnevyj-2-scaled.jpg',
];
// ID товара распродажи => адрес картинки (06.11.2026)
const ND_WEB_PHOTO_BY_ID = [
	35248 => 'https://woodvex.com/wp-content/uploads/2020/06/Palisander-STD.jpg',   // Expert Colorite Палисандр Old Style
	35389 => 'https://woodvex.com/wp-content/uploads/2019/06/antiq-wenge.jpg',      // Expert Antique wenge
	35388 => ND_WV . 'Baljasina_Wenge_nakat.jpg',                                   // Балясина Select Венге
	35424 => ND_WV . 'Ugolok_Select_Wenge.jpg',                                     // L-планка Select Colorite Венге
	35397 => 'https://polivan.com/wp-content/uploads/2022/01/kr-ub-dpk-02-01.webp', // Крышка для столба PG SINGARAJA
	35421 => 'https://polivan.com/wp-content/uploads/2022/01/kr-ub-dpk-02-01.webp', // Юбка для столба PG SINGARAJA
	35433 => 'https://nextwood.ru/wp-content/uploads/2024/01/Krepe-zh-dlya-balyasin-uglovoi-NEXTWOOD-45-15-sht.-v-upakovke-2.jpg',
	// Шарнир для клумбы Holzhof 30*225 коричневый — gardeck24.ru (Гардек), тот же товар
	35420 => 'https://gardeck24.ru/upload/iblock/70e/h2xxmvfc3sht6k413xqnhz72qfc7bqw3/sharnir-dlya-klumby-holzhof-30-225mm-plastik-korichnevyy.jpg',
	// Торцевая пластина периметра HILST — hilst.ru (металл, без «пластик 12мм» в названии)
	35437 => 'https://hilst.ru/upload/iblock/b26/fuqfl9lw6c9mf4am2jq2tvjyyl0u6cne/plastina_torez_main_hilst.jpg',
	// Регулируемая опора Level (35-50) — sayangroup.ru (производитель Level), Level PRO 35-50
	35403 => 'https://sayangroup.ru/images/products/reguliruemaia-opora-level-pro-35-50-1.png',
];

$_SERVER['DOCUMENT_ROOT'] = realpath($root);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
@set_time_limit(0);
\Bitrix\Main\Loader::includeModule('iblock');

$outDir = (getenv('HOME') ?: __DIR__) . '/sale-feed/' . date('Y-m-d_His') . '_webphotos_' . $mode;
mkdir($outDir, 0700, true);

// убрать главное фото, анонс и MORE_PHOTO товара
function ndClearPhotos(int $id): void
{
	$el = new CIBlockElement();
	$el->Update($id, ['DETAIL_PICTURE' => ['del' => 'Y'], 'PREVIEW_PICTURE' => ['del' => 'Y']]);
	$vals = [];
	$rs = CIBlockElement::GetProperty(ND_IBLOCK_ID, $id, [], ['CODE' => 'MORE_PHOTO']);
	while ($p = $rs->Fetch()) {
		if ($p['PROPERTY_VALUE_ID']) {
			$vals[$p['PROPERTY_VALUE_ID']] = ['VALUE' => ['del' => 'Y']];
		}
	}
	if ($vals) {
		CIBlockElement::SetPropertyValueCode($id, 'MORE_PHOTO', $vals);
	}
}

if ($mode === 'rollback') {
	$done = json_decode((string)file_get_contents($rollbackFile), true);
	foreach ((array)$done as $id) {
		ndClearPhotos((int)$id);
	}
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	echo 'Фото убраны у товаров: ' . count((array)$done) . "\n";
	exit(0);
}

$done = [];
$el = new CIBlockElement();
$rs = CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => ND_IBLOCK_ID,
	[
		'LOGIC' => 'OR',
		['XML_ID' => $onlyNew ? [-1] : array_keys(ND_WEB_PHOTO)],
		['ID' => $onlyId ?: array_keys(ND_WEB_PHOTO_BY_ID)],
	],
], false, false, ['ID', 'NAME', 'XML_ID', 'DETAIL_PICTURE']);
while ($r = $rs->Fetch()) {
	$url = ND_WEB_PHOTO_BY_ID[(int)$r['ID']] ?? ND_WEB_PHOTO[$r['XML_ID']];
	$bin = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'Mozilla/5.0']]));
	$ok = $bin !== false && strlen($bin) > 10000 && @getimagesizefromstring($bin);
	/* webp и png — в jpg на белом фоне (прозрачный фон иначе становится чёрным), не больше 1600 px:
	   так картинку примут и ресайз, и старые браузеры; png Level весил 7,5 МБ при 4500 px */
	$info = $ok ? getimagesizefromstring($bin) : null;
	if ($info && in_array($info['mime'], ['image/webp', 'image/png'], true) && ($img = @imagecreatefromstring($bin))) {
		$k = min(1, 1600 / max(imagesx($img), imagesy($img)));
		$w = (int)round(imagesx($img) * $k);
		$h = (int)round(imagesy($img) * $k);
		$bg = imagecreatetruecolor($w, $h);
		imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
		imagecopyresampled($bg, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
		ob_start();
		imagejpeg($bg, null, 90);
		$bin = ob_get_clean();
		$url = preg_replace('/\.(webp|png)$/', '.jpg', $url);
	}
	echo "{$r['ID']} {$r['NAME']}\n    ← " . basename($url) . ($ok ? ', ' . round(strlen($bin) / 1024) . ' КБ' : ' — НЕ СКАЧАЛОСЬ') .
		($r['DETAIL_PICTURE'] ? ' (прежнее фото заменится)' : '') . "\n";
	if (!$ok || $mode !== 'apply') {
		continue;
	}
	$path = $outDir . '/' . basename(parse_url($url, PHP_URL_PATH));
	file_put_contents($path, $bin);
	ndClearPhotos((int)$r['ID']);
	$file = CFile::MakeFileArray($path);
	if (!$el->Update((int)$r['ID'], ['DETAIL_PICTURE' => $file, 'PREVIEW_PICTURE' => CFile::MakeFileArray($path)])) {
		echo "    ! {$el->LAST_ERROR}\n";
		continue;
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
