<?php
/**
 * Акции CM Decking (Cottage Mode) до 30.11.2026 — по письму поставщика (Ирина, 12.11.2026:
 * «заводим и ориентируемся на письмо»). Всё в одном скрипте:
 *   — правила корзины −20 / −30 / −50% (как у ноябрьской EasyDecking, nd_promo_nov2026.php);
 *   — элемент акции в ИБ 17 (/sale/) с баннером и текстом;
 *   — метка «Акция» (HIT = AKCIYA) у товаров, список для отката — ~/nd_promo_cm_2026_hit.json.
 *
 * Состав (ID торговых предложений = артикул на сайте):
 *   −30%: террасная доска ГРАНД все цвета и длины, УРБАН Смок Грэй;
 *   −50%: ступени Стэйр 345х25 все цвета (карточка выключена — под заказ), Кладдинг ТИМБЕР
 *         Спайси Браун, СМ Волл Белый 3 и 4 м;
 *   −20%: Кладдинг УРБАН Сонома.
 *
 *   php nd_promo_cm_2026.php <DOCROOT>                     — показать, ничего не писать
 *   php nd_promo_cm_2026.php <DOCROOT> --apply <баннер.jpg> — завести всё (сразу для всех)
 *   php nd_promo_cm_2026.php <DOCROOT> rollback            — удалить правила и элемент, снять метку
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix')) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

\Bitrix\Main\Loader::includeModule('sale');
\Bitrix\Main\Loader::includeModule('catalog');
\Bitrix\Main\Loader::includeModule('iblock');

const ND_PROMO_IBLOCK = 17;
const ND_CATALOG_IBLOCK = 19;
const ND_CODE = 'aktsii_cm_decking_oktyabr_noyabr_2026';
const ND_TEMPLATE_PROMO = 34618; // откуда берём регионы
const ND_FROM = '12.11.2026 00:00:00';
const ND_TO = '30.11.2026 23:59:00';

$rules = [
	30 => [27774, 27775, 27776, 27777, 26493],
	50 => [27779, 27780, 27781, 27782, 27101, 33827, 27080],
	20 => [27062],
];

$mode = $argv[2] ?? '';
$log = (getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT'], 2)) . '/nd_promo_cm_2026_hit.json';
$xmlId = function (int $percent): string {
	return 'nd_promo_cm_2026_' . $percent;
};
$ruleOf = function (int $percent) use ($xmlId) {
	return \Bitrix\Sale\Internals\DiscountTable::getList([
		'select' => ['ID'], 'filter' => ['=XML_ID' => $xmlId($percent)],
	])->fetch() ?: null;
};
$current = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_PROMO_IBLOCK, '=CODE' => ND_CODE], false, false, ['ID'])->Fetch() ?: null;

$enum = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => ND_CATALOG_IBLOCK, 'CODE' => 'HIT', 'XML_ID' => 'AKCIYA'])->Fetch();
$akciya = (int)$enum['ID'];
$hitOf = function (int $id): array {
	$vals = [];
	$rs = CIBlockElement::GetProperty(ND_CATALOG_IBLOCK, $id, [], ['CODE' => 'HIT']);
	while ($v = $rs->Fetch()) {
		if ((int)$v['VALUE'] > 0) {
			$vals[] = (int)$v['VALUE'];
		}
	}
	return $vals;
};
$saveHit = function (int $id, array $vals) {
	CIBlockElement::SetPropertyValuesEx($id, ND_CATALOG_IBLOCK, ['HIT' => $vals ?: false]);
	\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(ND_CATALOG_IBLOCK, $id);
};
$clearCache = function () {
	foreach ([ND_PROMO_IBLOCK, ND_CATALOG_IBLOCK, 20] as $ib) {
		CIBlock::clearIblockTagCache($ib);
	}
};

if ($mode === 'rollback') {
	foreach (array_keys($rules) as $percent) {
		if ($row = $ruleOf($percent)) {
			CSaleDiscount::Delete((int)$row['ID']);
			echo "правило {$row['ID']} ({$percent}%) удалено\n";
		}
	}
	if ($current) {
		CIBlockElement::Delete((int)$current['ID']);
		echo "элемент {$current['ID']} удалён\n";
	}
	$added = is_file($log) ? (array)json_decode(file_get_contents($log), true) : [];
	foreach ($added as $id) {
		$saveHit((int)$id, array_values(array_diff($hitOf((int)$id), [$akciya])));
	}
	if (is_file($log)) {
		unlink($log);
	}
	echo 'метка «Акция» снята у ' . count($added) . " товаров\n";
	$clearCache();
	exit;
}

// проверка состава, товары и разделы
$products = [];   // все родители (для метки)
$visible = [];    // включённые родители (для страницы акции)
$sections = [];
$errors = 0;
foreach ($rules as $percent => $ids) {
	echo "== −{$percent}%\n";
	foreach ($ids as $id) {
		$o = CIBlockElement::GetList([], ['ID' => $id], false, false, ['ID', 'NAME', 'ACTIVE', 'PROPERTY_CML2_LINK'])->Fetch();
		$price = CPrice::GetBasePrice($id);
		if (!$o || !$price) {
			echo "  !! $id — нет предложения или цены\n";
			$errors++;
			continue;
		}
		$pid = (int)$o['PROPERTY_CML2_LINK_VALUE'];
		$p = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_CATALOG_IBLOCK, 'ID' => $pid], false, false, ['ID', 'ACTIVE', 'IBLOCK_SECTION_ID'])->Fetch();
		$base = (float)$price['PRICE'];
		echo sprintf("  %d %s%s — %s → %s ₽\n", $id, trim($o['NAME']), $p['ACTIVE'] === 'Y' ? '' : ' [карточка выключена]',
			$base, round($base * (100 - $percent) / 100));
		$products[$pid] = true;
		if ($p['ACTIVE'] === 'Y' && $o['ACTIVE'] === 'Y') {
			$visible[$pid] = true;
			$sections[(int)$p['IBLOCK_SECTION_ID']] = true;
		}
	}
}
if ($errors) {
	exit("есть ошибки ($errors) — ничего не пишу\n");
}
echo 'товаров: ' . count($products) . ', на странице акции: ' . count($visible) . ', разделов: ' . count($sections) . "\n";
echo 'элемент акции: ' . ($current ? "есть, ID {$current['ID']} — будет обновлён" : 'будет создан') . "\n";
if ($mode !== '--apply') {
	exit("Прогон без записи. Для записи: --apply <баннер.jpg>\n");
}
$banner = $argv[3] ?? '';
if (!$current && !is_file($banner)) {
	exit("нет файла баннера\n");
}

// 1. правила корзины — для всех групп
global $APPLICATION;
$groups = [];
$res = CGroup::GetList('id', 'asc', ['ACTIVE' => 'Y']);
while ($g = $res->Fetch()) {
	$groups[] = (int)$g['ID'];
}
foreach ($rules as $percent => $ids) {
	$fields = [
		'LID' => 's1',
		'XML_ID' => $xmlId($percent),
		'NAME' => "Акция CM Decking до 30.11.2026 / {$percent}%",
		'CURRENCY' => 'RUB',
		'ACTIVE' => 'Y',
		'ACTIVE_FROM' => ND_FROM,
		'ACTIVE_TO' => ND_TO,
		'SORT' => 100,
		'PRIORITY' => 1,
		'LAST_DISCOUNT' => 'N',
		'LAST_LEVEL_DISCOUNT' => 'N',
		'USER_GROUPS' => $groups,
		'CONDITIONS' => ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND', 'True' => 'True'], 'CHILDREN' => []],
		'ACTIONS' => [
			'CLASS_ID' => 'CondGroup',
			'DATA' => ['All' => 'AND'],
			'CHILDREN' => [[
				'CLASS_ID' => 'ActSaleBsktGrp',
				'DATA' => ['Type' => 'Discount', 'Value' => $percent, 'Unit' => 'Perc', 'Max' => 0, 'All' => 'OR', 'True' => 'True'],
				'CHILDREN' => [['CLASS_ID' => 'CondIBElement', 'DATA' => ['logic' => 'Equal', 'value' => $ids]]],
			]],
		],
	];
	$row = $ruleOf($percent);
	$id = $row ? (CSaleDiscount::Update((int)$row['ID'], $fields) ? (int)$row['ID'] : 0) : (int)CSaleDiscount::Add($fields);
	if (!$id) {
		$ex = $APPLICATION->GetException();
		exit("ошибка правила {$percent}%: " . ($ex ? $ex->GetString() : '?') . "\n");
	}
	echo "правило {$percent}%: ID $id\n";
}

// 2. элемент акции
$html = '<p><strong>Скидки до 50% на материалы CM Decking</strong></p>'
	. '<p>До 30 ноября — специальные цены на террасную доску, фасадные и стеновые панели CM Decking:</p>'
	. '<ul>'
	. '<li>террасная доска ГРАНД, вся коллекция — <strong>−30%</strong>;</li>'
	. '<li>террасная доска УРБАН Смок Грэй — <strong>−30%</strong>;</li>'
	. '<li>фасадная панель СМ Кладдинг ТИМБЕР Спайси Браун — <strong>−50%</strong>;</li>'
	. '<li>фасадная панель СМ Кладдинг УРБАН Сонома — <strong>−20%</strong>;</li>'
	. '<li>реечная панель СМ Волл Белый — <strong>−50%</strong>;</li>'
	. '<li>ступени 345х25 во всех цветах (под заказ) — <strong>−50%</strong>.</li>'
	. '</ul>'
	. '<p><strong>Что важно знать</strong></p>'
	. '<ul><li>Акция действует до 30 ноября 2026 года.</li>'
	. '<li>Количество товара по акции ограничено.</li>'
	. '<li>Не суммируется с другими акциями.</li></ul>'
	. '<p><strong>Не уверены, сколько брать?</strong><br>'
	. 'Пришлите размеры террасы или фасада — бесплатно рассчитаем количество материала и комплектующих '
	. 'и подскажем, какие цвета и длины есть в наличии.</p>';
$editor = [
	'version' => 2,
	'blocks' => [
		['value' => $html, 'name' => 'text', 'settings' => [], 'layout' => '0,0', 'meta' => []],
		['title' => 'Заказать звонок', 'url' => '', 'target' => '', 'name' => 'button_link', 'settings' => ['form_id' => 'MAINFORM'], 'layout' => '0,0', 'meta' => []],
	],
	'layouts' => [['settings' => [], 'columns' => [['css' => '']]]],
];
$tpl = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_PROMO_IBLOCK, 'ID' => ND_TEMPLATE_PROMO], false, false, ['ID', 'IBLOCK_ID'])->GetNextElement();
$tplProps = $tpl ? $tpl->GetProperties() : [];
$penum = function (string $code, string $xml) {
	$e = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => ND_PROMO_IBLOCK, 'CODE' => $code, 'XML_ID' => $xml])->Fetch();
	return $e ? (int)$e['ID'] : false;
};
$fields = [
	'IBLOCK_ID' => ND_PROMO_IBLOCK,
	'NAME' => 'CM Decking: скидки до −50% на террасную доску, фасадные и стеновые панели',
	'CODE' => ND_CODE,
	'ACTIVE' => 'Y',
	'ACTIVE_FROM' => ND_FROM,
	'ACTIVE_TO' => ND_TO,
	'SORT' => 4,
	'PREVIEW_TEXT' => 'До 30 ноября — скидки до 50% на террасную доску ГРАНД и УРБАН, фасадные панели СМ Кладдинг '
		. 'и реечные панели СМ Волл Белый.<br><b>* Количество товара по акции ограничено. Не суммируется с другими акциями.</b>',
	'PREVIEW_TEXT_TYPE' => 'html',
];
if (is_file($banner)) {
	$fields['PREVIEW_PICTURE'] = CFile::MakeFileArray($banner);
	$fields['DETAIL_PICTURE'] = CFile::MakeFileArray($banner);
}
$props = [
	'LINK_REGION' => $tplProps['LINK_REGION']['VALUE'] ?? [],
	'LINK_GOODS' => array_keys($visible),
	'LINK_SECT_CATALOG' => array_keys($sections),
	'PHOTOPOS' => $penum('PHOTOPOS', 'TOP'),
	'SHOW_ON_CATALOG_PAGE' => $penum('SHOW_ON_CATALOG_PAGE', 'yes'),
	'EDITOR1' => json_encode($editor, JSON_UNESCAPED_UNICODE),
];
$el = new CIBlockElement();
if ($current) {
	$eid = (int)$current['ID'];
	if (!$el->Update($eid, $fields)) {
		exit('ошибка элемента: ' . $el->LAST_ERROR . "\n");
	}
} else {
	$eid = (int)$el->Add($fields);
	if (!$eid) {
		exit('ошибка элемента: ' . $el->LAST_ERROR . "\n");
	}
}
CIBlockElement::SetPropertyValuesEx($eid, ND_PROMO_IBLOCK, $props);
echo "элемент акции: ID $eid\n";

// 3. метка «Акция» у включённых товаров
$added = is_file($log) ? (array)json_decode(file_get_contents($log), true) : [];
foreach (array_keys($visible) as $pid) {
	if (!in_array($akciya, $hitOf($pid), true)) {
		$saveHit($pid, array_merge($hitOf($pid), [$akciya]));
		$added[] = $pid;
	}
}
file_put_contents($log, json_encode(array_values(array_unique($added))));
echo 'метка «Акция»: ' . count($added) . " товаров, откат — $log\n";
$clearCache();
echo "готово\n";
