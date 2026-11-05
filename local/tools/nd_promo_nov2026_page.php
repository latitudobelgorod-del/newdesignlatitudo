<?php
/**
 * Ноябрьская акция EasyDecking 2026 — элемент на странице акций (/sale/, ИБ 17).
 * Сделан по образцу августовских акций EasyDecking (34617–34624): та же оговорка
 * в анонсе, перечень товаров со скидками и кнопка «Заказать звонок» в редакторе
 * блоков, регионы как у них, товары — в «Элементах, участвующих в акции».
 * Период — с 5 по 30 ноября 2026 (Ирина).
 *
 * Элемент заводится ВЫКЛЮЧЕННЫМ: правила корзины пока видят только администраторы,
 * включаем вместе с ними (nd_promo_nov2026.php --public, затем здесь --activate).
 *
 *   php nd_promo_nov2026_page.php <DOCROOT>              — показать, что будет
 *   php nd_promo_nov2026_page.php <DOCROOT> --apply      — создать/обновить (выключенным)
 *   php nd_promo_nov2026_page.php <DOCROOT> --activate   — включить
 *   php nd_promo_nov2026_page.php <DOCROOT> rollback     — удалить элемент
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix')) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

\Bitrix\Main\Loader::includeModule('iblock');

const ND_PROMO_IBLOCK = 17;
const ND_CATALOG_IBLOCK = 19;
const ND_CODE = 'noyabrskaya_aktsiya_easydecking_2026';
const ND_TEMPLATE_PROMO = 34618; // откуда берём регионы

$mode = $argv[2] ?? '';
$current = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_PROMO_IBLOCK, '=CODE' => ND_CODE], false, false, ['ID', 'ACTIVE'])->Fetch() ?: null;

if ($mode === 'rollback') {
	if (!$current) {
		exit("элемента нет\n");
	}
	CIBlockElement::Delete((int)$current['ID']);
	exit("элемент {$current['ID']} удалён\n");
}
if ($mode === '--activate') {
	if (!$current) {
		exit("элемента нет — сначала --apply\n");
	}
	(new CIBlockElement())->Update((int)$current['ID'], ['ACTIVE' => 'Y']);
	exit("элемент {$current['ID']} включён\n");
}

// перечень по группам — в порядке, удобном покупателю
$groups = [
	'Террасная доска' => '~^(Террасная доска|Доска для террас)~u',
	'Финишная доска' => '~^Финишная доска~u',
	'Ступени' => '~^Ступень~u',
	'Фасадные панели' => '~^Фасадная панель~u',
	'Реечные фасадные панели' => '~^Фасадная реечная панель~u',
	'Заборная (универсальная) доска' => '~^Заборная \(универсальная\)~u',
	'Заборные панели шип-паз' => '~^Заборная панель шип-паз~u',
	'Ограждения' => '~^(Балясина|Перила)~u',
];
$list = array_fill_keys(array_keys($groups), []);
$products = [];
$sections = [];
foreach (require __DIR__ . '/nd_promo_nov2026_items.php' as $percent => $ids) {
	$rs = CIBlockElement::GetList([], ['ID' => $ids], false, false, ['ID', 'NAME', 'PROPERTY_CML2_LINK']);
	while ($o = $rs->Fetch()) {
		$name = preg_replace('~\s+~u', ' ', trim($o['NAME']));
		$group = null;
		foreach ($groups as $title => $re) {
			if (preg_match($re, $name)) {
				$group = $title;
				break;
			}
		}
		if (!$group) {
			exit("не нашла группу для «{$name}»\n");
		}
		$list[$group][] = [$name, $percent];
		$products[(int)$o['PROPERTY_CML2_LINK_VALUE']] = true;
	}
}
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_CATALOG_IBLOCK, 'ID' => array_keys($products)], false, false, ['ID', 'IBLOCK_SECTION_ID']);
while ($p = $rs->Fetch()) {
	$sections[(int)$p['IBLOCK_SECTION_ID']] = true;
}

$html = '<p><strong>Ноябрьская акция: скидки до 20% на продукцию EasyDecking!</strong><br>'
	. 'Акция действует с 5 по 30 ноября 2026 года.<br><br>'
	. 'Полный перечень товаров, участвующих в акции:</p>';
foreach ($list as $title => $items) {
	if (!$items) {
		continue;
	}
	sort($items);
	$html .= '<p><strong>' . htmlspecialchars($title) . '</strong></p><ul>';
	foreach ($items as [$name, $percent]) {
		$html .= '<li>' . htmlspecialchars($name) . ' -' . $percent . '%</li>';
	}
	$html .= '</ul>';
}
$html .= '<p><br>Акция действует только на товар в наличии на складе. Не суммируется с другими акциями.</p>'
	. '<p>Итоговый размер скидки может отличаться. Все подробности у наших менеджеров. Количество товара ограничено!<br>'
	. 'Поможем с расчетами материалов. Свяжитесь с нами любым удобным способом!</p>';

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
$enum = function (string $code, string $xml) {
	$e = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => ND_PROMO_IBLOCK, 'CODE' => $code, 'XML_ID' => $xml])->Fetch();
	return $e ? (int)$e['ID'] : false;
};

$name = 'До -20% на продукцию EasyDecking: ноябрьская акция';
$fields = [
	'IBLOCK_ID' => ND_PROMO_IBLOCK,
	'NAME' => $name,
	'CODE' => ND_CODE,
	'ACTIVE' => $current ? $current['ACTIVE'] : 'N',
	'ACTIVE_FROM' => '05.11.2026 09:00:00',
	'ACTIVE_TO' => '30.11.2026 18:00:00',
	'SORT' => 2,
	'PREVIEW_TEXT' => '<b>* Акция действует только на товар в наличии на складе. Не суммируется с другими.</b>',
	'PREVIEW_TEXT_TYPE' => 'html',
];
$props = [
	'LINK_REGION' => $tplProps['LINK_REGION']['VALUE'] ?? [],
	'LINK_GOODS' => array_keys($products),
	'LINK_SECT_CATALOG' => array_keys($sections),
	'PHOTOPOS' => $enum('PHOTOPOS', 'TOP'),
	'SHOW_ON_CATALOG_PAGE' => $enum('SHOW_ON_CATALOG_PAGE', 'yes'),
	'EDITOR1' => json_encode($editor, JSON_UNESCAPED_UNICODE),
];

echo "«{$name}», {$fields['ACTIVE_FROM']} – {$fields['ACTIVE_TO']}\n";
echo 'товаров: ' . count($products) . ', разделов: ' . count($sections) . ', регионов: ' . count($props['LINK_REGION']) . "\n";
foreach ($list as $title => $items) {
	echo "  $title: " . count($items) . "\n";
}
echo 'элемент: ' . ($current ? "есть, ID {$current['ID']} — будет обновлён" : 'нет — будет создан выключенным') . "\n";
if ($mode !== '--apply') {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

$el = new CIBlockElement();
if ($current) {
	$id = (int)$current['ID'];
	if (!$el->Update($id, $fields)) {
		exit('ошибка: ' . $el->LAST_ERROR . "\n");
	}
} else {
	$id = (int)$el->Add($fields);
	if (!$id) {
		exit('ошибка: ' . $el->LAST_ERROR . "\n");
	}
}
CIBlockElement::SetPropertyValuesEx($id, ND_PROMO_IBLOCK, $props);
CIBlock::clearIblockTagCache(ND_PROMO_IBLOCK);
echo "готово, элемент ID $id\n";
