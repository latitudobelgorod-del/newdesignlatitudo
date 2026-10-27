<?php
/**
 * Дубли чехлов 4SIS (Ирина, 27.10.2026: «если это действительно дубли — удали»). Сверено с карточками 4sis.ru:
 *   35019 Ривьера хаки (УТ-00014706, 4200)  — у 4SIS одна карточка, это наш 31567 (УТ-00009046, 3800)  → удалить;
 *   35198 Капучино диван 3-м серый (с сайта) — та же карточка 4sis.ru, что наш 31659 (УТ-00006803)     → удалить;
 *   35150 Капучино кресло серый (с сайта)    — та же карточка 4sis.ru, что наш 31884 (УТ-00006255)     → удалить.
 * 35018 (столик Капучино «серый») — НЕ дубль: у 4SIS отдельная карточка «цвет серый», оставлен.
 * Оставшимся 31659 и 31884 — названия, как сейчас на 4sis.ru: иначе воскресный обход сайта (new_goods_4sis.php
 * --site) не узнает товар по названию и заведёт удалённый дубль снова. Цена и адрес страницы не меняются.
 *
 *   php dups_covers_2026-10-27.php          — сухой прогон
 *   php dups_covers_2026-10-27.php apply    — сохранить копии в ~/4sis-sync/2026-10-27_dups/ и удалить, переименовать
 */
$apply = ($argv[1] ?? '') === 'apply';
$_SERVER['DOCUMENT_ROOT'] = getenv('HOME') . '/www/latitudo.ru';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');

const ND_DELETE = [35019 => 31567, 35198 => 31659, 35150 => 31884]; // дубль => остаётся
const ND_RENAME = [
	31659 => 'Защитный чехол на диван Капучино, трехместный 222×86,5×81,5 см, цвет серый',
	31884 => 'Защитный чехол на кресло Капучино, 104х86,5х81,5 см, цвет серый',
];

$dir = getenv('HOME') . '/4sis-sync/2026-10-27_dups';
if ($apply && !is_dir($dir)) {
	mkdir($dir, 0700, true);
}
foreach (ND_DELETE as $id => $keep) {
	$e = CIBlockElement::GetList([], ['ID' => $id, 'IBLOCK_ID' => 19], false, false, ['*'])->GetNextElement();
	$k = CIBlockElement::GetList([], ['ID' => $keep, 'IBLOCK_ID' => 19], false, false, ['ID', 'NAME', 'ACTIVE'])->Fetch();
	if (!$e || !$k) {
		echo "  ! $id или $keep не найден — пропуск\n";
		continue;
	}
	$f = $e->GetFields();
	echo "удалить $id «{$f['NAME']}» ({$f['XML_ID']}) — остаётся {$k['ID']} «{$k['NAME']}» ({$k['ACTIVE']})\n";
	if ($apply) {
		$copy = ['fields' => $f, 'props' => $e->GetProperties(), 'price' => CPrice::GetBasePrice($id),
			'product' => \Bitrix\Catalog\ProductTable::getById($id)->fetch()];
		file_put_contents("$dir/$id.json", json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		echo CIBlockElement::Delete($id) ? "  удалён, копия $dir/$id.json\n" : "  ! не удалился\n";
	}
}
$el = new CIBlockElement();
foreach (ND_RENAME as $id => $name) {
	$old = CIBlockElement::GetList([], ['ID' => $id, 'IBLOCK_ID' => 19], false, false, ['ID', 'NAME'])->Fetch();
	echo "переименовать $id: «{$old['NAME']}» → «{$name}»\n";
	if ($apply) {
		file_put_contents("$dir/rename_$id.txt", $old['NAME']);
		echo $el->Update($id, ['NAME' => $name]) ? "  ok\n" : "  ! {$el->LAST_ERROR}\n";
	}
}
if ($apply) {
	CIBlock::clearIblockTagCache(19);
} else {
	echo "Сухой прогон — ничего не изменено. Выполнить: добавьте apply\n";
}
