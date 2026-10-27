<?php
/**
 * Дубли «товар с сайта 4sis.ru ↔ товар из фида» (Ирина, 27.10.2026). Признак: адрес карточки на 4sis.ru
 * (XML_ID SITE-<адрес>) — транслит СТАРОГО названия нашего товара из фида: 4SIS переименовал товар на сайте,
 * в фиде оставил прежнее имя, и обход сайта завёл его второй раз. Сверено с карточками 4sis.ru.
 *   35052 чехол барный Марсель/Бордо (с сайта) = 34888 (УТ-00013590)  → удалить, 34888 назвать как на 4sis.ru;
 *   35222 «Парма» шезлонг (с сайта)           = 31833 (УТ-00008678)  → удалить, 31833 назвать как на 4sis.ru;
 *   31797 «Севилья» светло-серый (выключен, нет в фиде) — старая версия 35176 → не удаляем, в keep_off.txt
 *   (иначе вернувшись в фид заведётся или включится снова).
 *   php dups_site_2026-10-27.php [apply]
 */
$apply = ($argv[1] ?? '') === 'apply';
$_SERVER['DOCUMENT_ROOT'] = getenv('HOME') . '/www/latitudo.ru';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');

const ND_DELETE = [35052 => 34888, 35222 => 31833];
const ND_RENAME = [
	34888 => 'Защитный чехол на стул барный Марсель/Бордо, 59х69х110 см, цвет темно-серый',
	31833 => '"Парма" шезлонг из алюминия темно-серый (RAL7024) муар, ткань текстилен темно-серая',
];
const ND_KEEP_OFF = [31797 => '«Севилья» кресло-кокон светло-серый — старая версия, актуальная 35176 (бежевый). 27.10.2026'];

$dir = getenv('HOME') . '/4sis-sync/2026-10-27_dups';
if ($apply && !is_dir($dir)) {
	mkdir($dir, 0700, true);
}
foreach (ND_DELETE as $id => $keep) {
	$e = CIBlockElement::GetList([], ['ID' => $id, 'IBLOCK_ID' => 19], false, false, ['*'])->GetNextElement();
	if (!$e) {
		echo "  ! $id не найден\n";
		continue;
	}
	$f = $e->GetFields();
	echo "удалить $id «{$f['NAME']}» — остаётся $keep\n";
	if ($apply) {
		file_put_contents("$dir/$id.json", json_encode(['fields' => $f, 'props' => $e->GetProperties(),
			'price' => CPrice::GetBasePrice($id), 'product' => \Bitrix\Catalog\ProductTable::getById($id)->fetch()],
			JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
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
$ko = getenv('HOME') . '/4sis-sync/keep_off.txt';
$have = is_file($ko) ? file_get_contents($ko) : '';
foreach (ND_KEEP_OFF as $id => $why) {
	if (preg_match('/^' . $id . '\b/m', $have)) {
		echo "$id уже в keep_off.txt\n";
		continue;
	}
	echo "в keep_off.txt: $id $why\n";
	if ($apply) {
		file_put_contents($ko, "$id $why\n", FILE_APPEND);
	}
}
if ($apply) {
	CIBlock::clearIblockTagCache(19);
} else {
	echo "Сухой прогон — ничего не изменено. Выполнить: добавьте apply\n";
}
