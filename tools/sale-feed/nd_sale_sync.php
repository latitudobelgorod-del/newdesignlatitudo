<?php
/**
 * Сверка раздела «Распродажа» с фидом metatool — по крону раз в час (26.10.2026, просьба Ирины).
 *
 *   php nd_sale_sync.php <DOCROOT>         — сухой прогон: что изменится, в базе ничего не меняется
 *   php nd_sale_sync.php <DOCROOT> apply   — применить
 *   --feed=<файл или адрес>                — фид (по умолчанию https://metatool.ru/api/feeds/latitudo-rasprodazha.json)
 *
 * Что делает (заведение первых 34 товаров — nd_sale_feed.php, там же правила цен и складов):
 *   - у заведённых товаров (XML_ID из фида) обновляет название, цену BASE, остатки по складам, общий
 *     остаток и SORT (порядок в разделе по остатку);
 *   - товар без остатка на всех складах или пропавший из фида — выключает (ACTIVE=N), остаток вернулся — включает;
 *   - новые товары фида заводит так же, как nd_sale_feed.php; фото — с товара каталога, на который указывает
 *     sku фида (ID торгового предложения или товара), если у него есть картинка; добавляет в акцию
 *     «Распродажа остатков» (ИБ «Акции», код rasprodazha_ostatkov, свойство LINK_GOODS);
 *   - пересобирает правила корзины nd_sale_<сумма> под новые цены распродажи;
 *   - пишет итог в report.json (для письма sale_report.py) и журнал изменённых полей для отката.
 * Защита: фид не прочитался или товаров в нём меньше половины заведённых — ничего не меняем, ошибка в отчёт.
 */

$args = array_slice($argv, 1);
$root = array_shift($args);
$opt = ['feed' => 'https://metatool.ru/api/feeds/latitudo-rasprodazha.json'];
$apply = false;
foreach ($args as $a) {
	if ($a === 'apply') {
		$apply = true;
	} elseif (preg_match('/^--([\w-]+)=(.*)$/', $a, $m) && array_key_exists($m[1], $opt)) {
		$opt[$m[1]] = $m[2];
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
const ND_PRICE_GROUP = 1;
const ND_SECTION_CODE = 'rasprodazha';
const ND_PROMO_CODE = 'rasprodazha_ostatkov';
const ND_STORE_BY_CITY = ['Белгород' => 1, 'Воронеж' => 2, 'Москва' => 3, 'Краснодар' => 4];
const ND_USER_GROUPS = [1, 2, 3, 4, 5, 6, 7, 8, 9];

$_SERVER['DOCUMENT_ROOT'] = realpath($root);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
@set_time_limit(0);
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');
\Bitrix\Main\Loader::includeModule('sale');

$outDir = (getenv('HOME') ?: __DIR__) . '/sale-feed/sync/' . date('Y-m-d_His') . ($apply ? '_apply' : '_dry');
mkdir($outDir, 0700, true);
$rep = ['date' => date('d.m.Y H:i'), 'apply' => $apply, 'error' => '', 'created' => [], 'deactivated' => [], 'activated' => [],
	'updated' => [], 'discounts' => [], 'out' => $outDir];
$undo = [];
$saveRep = function () use (&$rep, &$undo, $outDir) {
	file_put_contents($outDir . '/report.json', json_encode($rep, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	file_put_contents($outDir . '/undo.json', json_encode($undo, JSON_UNESCAPED_UNICODE));
};
$fail = function ($msg) use (&$rep, $saveRep) {
	$rep['error'] = $msg;
	$saveRep();
	echo "ОШИБКА: $msg\n";
	exit(1);
};

// --- фид
$raw = @file_get_contents($opt['feed'], false, stream_context_create(['http' => ['timeout' => 60]]));
$feed = $raw ? json_decode($raw, true) : null;
if (!$feed || ($feed['feed'] ?? '') !== 'rasprodazha' || !isset($feed['items']) || !is_array($feed['items'])) {
	$fail('фид не прочитался: ' . $opt['feed']);
}
file_put_contents($outDir . '/feed.json', $raw);

$section = CIBlockSection::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID, '=CODE' => ND_SECTION_CODE], false, ['ID'])->Fetch();
if (!$section) {
	$fail('нет раздела ' . ND_SECTION_CODE);
}
$sectionId = (int)$section['ID'];

// --- что на сайте: товары раздела
$site = [];
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID, 'SECTION_ID' => $sectionId], false, false,
	['ID', 'NAME', 'XML_ID', 'ACTIVE', 'SORT', 'CODE']);
while ($r = $rs->Fetch()) {
	$site[$r['XML_ID']] = $r;
}
if (count($feed['items']) * 2 < count($site)) {
	$fail('в фиде ' . count($feed['items']) . ' товаров, а на сайте ' . count($site) . ' — похоже на сбой фида, ничего не меняю');
}
$usedCode = [];
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_IBLOCK_ID], false, false, ['ID', 'CODE']);
while ($r = $rs->Fetch()) {
	$usedCode[$r['CODE']] = (int)$r['ID'];
}

// --- разбор фида
$plan = [];
foreach ($feed['items'] as $it) {
	$xml = trim((string)($it['xml_id'] ?? ''));
	$name = trim((string)($it['name'] ?? ''));
	if ($xml === '' || $name === '') {
		continue;
	}
	$retail = (float)($it['price_retail'] ?? 0);
	$sale = (float)($it['price_sale'] ?? 0);
	$stores = array_fill_keys(array_values(ND_STORE_BY_CITY), 0.0);
	foreach ((array)($it['stock'] ?? []) as $s) {
		$sid = ND_STORE_BY_CITY[trim((string)$s['city'])] ?? null;
		if ($sid !== null) {
			$stores[$sid] += (float)$s['quantity'];
		} else {
			$rep['updated'][] = "неизвестный город склада «{$s['city']}»: $name";
		}
	}
	$total = array_sum($stores);
	$plan[$xml] = [
		'xml' => $xml, 'name' => $name, 'code' => trim((string)($it['code'] ?? '')), 'sku' => (string)($it['sku'] ?? ''),
		'guid' => trim((string)($it['guid'] ?? '')),
		'base' => $retail > 0 ? $retail : $sale, 'off' => ($retail > 0 && $sale > 0 && $sale < $retail) ? round($retail - $sale, 2) : 0.0,
		'stores' => $stores, 'total' => $total, 'sort' => max(1, 1000000 - (int)round($total * 100)),
	];
}

$el = new CIBlockElement();
$idByXml = [];

// --- заведённые: сверить поля
foreach ($site as $xml => $s) {
	$id = (int)$s['ID'];
	$idByXml[$xml] = $id;
	if (!isset($plan[$xml])) {
		if ($s['ACTIVE'] === 'Y') {
			$rep['deactivated'][] = ['id' => $id, 'name' => $s['NAME'], 'why' => 'нет в фиде'];
			$undo[] = ['id' => $id, 'ACTIVE' => 'Y'];
			if ($apply) {
				$el->Update($id, ['ACTIVE' => 'N']);
			}
		}
		continue;
	}
	$p = $plan[$xml];
	$fields = [];
	$was = ['id' => $id];
	if ($s['NAME'] !== $p['name']) {
		$fields['NAME'] = $p['name'];
		$was['NAME'] = $s['NAME'];
		$rep['updated'][] = "$id название: «{$s['NAME']}» → «{$p['name']}»";
	}
	if ((int)$s['SORT'] !== $p['sort']) {
		$fields['SORT'] = $p['sort'];
		$was['SORT'] = (int)$s['SORT'];
	}
	// продаём только целые штуки: остаток 0,25 купить нельзя, корзина ругалась (Ирина, 27.10.2026)
	$active = $p['total'] >= 1 ? 'Y' : 'N';
	if ($s['ACTIVE'] !== $active) {
		$fields['ACTIVE'] = $active;
		$was['ACTIVE'] = $s['ACTIVE'];
		$rep[$active === 'Y' ? 'activated' : 'deactivated'][] = ['id' => $id, 'name' => $p['name'],
			'why' => $active === 'Y' ? 'остаток ' . $p['total']
				: ($p['total'] > 0 ? 'остаток ' . $p['total'] . ' — меньше одной целой штуки' : 'нет остатка ни на одном складе')];
	}
	// больше остатка не продаём (Ирина, 27.10.2026): учёт количества включён, покупка «в минус» запрещена.
	// Доступно = остаток по фиду минус резерв по заказам сайта: иначе обновление фида «возвращало»
	// в продажу уже заказанную штуку, пока 1С её не списала.
	$prod = \Bitrix\Catalog\ProductTable::getList(['filter' => ['=ID' => $id],
		'select' => ['QUANTITY_TRACE', 'CAN_BUY_ZERO', 'QUANTITY_RESERVED']])->fetch();
	$avail = max(0, $p['total'] - (float)($prod['QUANTITY_RESERVED'] ?? 0));
	if ($prod && ($prod['QUANTITY_TRACE'] !== 'Y' || $prod['CAN_BUY_ZERO'] !== 'N')) {
		$was['TRACE'] = [$prod['QUANTITY_TRACE'], $prod['CAN_BUY_ZERO']];
		$rep['updated'][] = "$id не больше остатка: учёт количества включён";
		if ($apply) {
			\Bitrix\Catalog\Model\Product::update($id, ['QUANTITY_TRACE' => 'Y', 'CAN_BUY_ZERO' => 'N', 'QUANTITY' => $avail]);
		}
	}
	// цена
	$price =\Bitrix\Catalog\PriceTable::getList(['filter' => ['=PRODUCT_ID' => $id, '=CATALOG_GROUP_ID' => ND_PRICE_GROUP]])->fetch();
	if (!$price || abs((float)$price['PRICE'] - $p['base']) > 0.004) {
		$was['PRICE'] = $price ? (float)$price['PRICE'] : null;
		$rep['updated'][] = "$id цена: " . ($price ? (float)$price['PRICE'] : '—') . " → {$p['base']}";
		if ($apply) {
			$price ? \Bitrix\Catalog\Model\Price::update($price['ID'], ['PRICE' => $p['base'], 'CURRENCY' => 'RUB'])
				: \Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $id, 'CATALOG_GROUP_ID' => ND_PRICE_GROUP, 'PRICE' => $p['base'], 'CURRENCY' => 'RUB']);
		}
	}
	// склады
	$cur = [];
	$rs = \Bitrix\Catalog\StoreProductTable::getList(['filter' => ['=PRODUCT_ID' => $id]]);
	while ($x = $rs->fetch()) {
		$cur[(int)$x['STORE_ID']] = $x;
	}
	$stDiff = [];
	foreach ($p['stores'] as $sid => $amount) {
		$old = isset($cur[$sid]) ? (float)$cur[$sid]['AMOUNT'] : null;
		if ($old === null || abs($old - $amount) > 0.0001) {
			$stDiff[$sid] = $old;
			if ($apply) {
				$old === null ? \Bitrix\Catalog\StoreProductTable::add(['PRODUCT_ID' => $id, 'STORE_ID' => $sid, 'AMOUNT' => $amount])
					: \Bitrix\Catalog\StoreProductTable::update($cur[$sid]['ID'], ['AMOUNT' => $amount]);
			}
		}
	}
	if ($stDiff) {
		$was['STORES'] = $stDiff;
		$rep['updated'][] = "$id остатки: " . implode(', ', array_map(function ($sid) use ($stDiff, $p) {
			return array_search($sid, ND_STORE_BY_CITY) . ' ' . (float)$stDiff[$sid] . '→' . $p['stores'][$sid];
		}, array_keys($stDiff)));
		if ($apply) {
			\Bitrix\Catalog\Model\Product::update($id, ['QUANTITY' => $avail]);
		}
	}
	if ($fields && $apply && !$el->Update($id, $fields)) {
		$rep['updated'][] = "$id не сохранилось: {$el->LAST_ERROR}";
	}
	if (count($was) > 1) {
		$undo[] = $was;
	}
}

// --- новые товары
foreach ($plan as $xml => $p) {
	if (isset($site[$xml])) {
		continue;
	}
	$code = $p['code'] !== '' ? $p['code'] : CUtil::translit($p['name'], 'ru', ['max_len' => 100, 'replace_space' => '-', 'replace_other' => '-']);
	for ($i = 2, $base = $code; isset($usedCode[$code]); $i++) {
		$code = $base . '-' . $i;
	}
	$usedCode[$code] = -1;
	$photo = ndPhotoSource($p['guid'], $p['sku']);
	$rep['created'][] = ['id' => 0, 'name' => $p['name'], 'price' => $p['base'], 'sale' => round($p['base'] - $p['off'], 2),
		'total' => $p['total'], 'photo' => $photo ? $photo['ID'] . ' ' . $photo['NAME'] : ''];
	if (!$apply) {
		continue;
	}
	$fields = ['IBLOCK_ID' => ND_IBLOCK_ID, 'ACTIVE' => $p['total'] >= 1 ? 'Y' : 'N', 'NAME' => $p['name'], 'CODE' => $code,
		'XML_ID' => $xml, 'SORT' => $p['sort'], 'IBLOCK_SECTION_ID' => $sectionId];
	if ($photo) {
		$main = (int)($photo['DETAIL_PICTURE'] ?: $photo['PREVIEW_PICTURE']);
		$fields['DETAIL_PICTURE'] = CFile::MakeFileArray($main);
		$fields['PREVIEW_PICTURE'] = CFile::MakeFileArray((int)($photo['PREVIEW_PICTURE'] ?: $main));
	}
	$id = (int)$el->Add($fields, false, true, false);
	if (!$id) {
		$rep['created'][count($rep['created']) - 1]['name'] .= ' — НЕ ЗАВЕДЁН: ' . $el->LAST_ERROR;
		continue;
	}
	$rep['created'][count($rep['created']) - 1]['id'] = $id;
	$undo[] = ['id' => $id, 'created' => true];
	$idByXml[$xml] = $id;
	$props = ['CML2_ARTICLE' => $id];
	if ($photo && $photo['MORE']) {
		$props['MORE_PHOTO'] = array_map(function ($f) { return ['VALUE' => CFile::MakeFileArray($f)]; }, $photo['MORE']);
	}
	CIBlockElement::SetPropertyValuesEx($id, ND_IBLOCK_ID, $props);
	\Bitrix\Catalog\Model\Product::add(['ID' => $id, 'TYPE' => 1, 'MEASURE' => 5, 'QUANTITY' => $p['total'],
		'QUANTITY_TRACE' => 'Y', 'CAN_BUY_ZERO' => 'N', 'VAT_INCLUDED' => 'N']);
	\Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $id, 'CATALOG_GROUP_ID' => ND_PRICE_GROUP, 'PRICE' => $p['base'], 'CURRENCY' => 'RUB']);
	foreach ($p['stores'] as $sid => $amount) {
		\Bitrix\Catalog\StoreProductTable::add(['PRODUCT_ID' => $id, 'STORE_ID' => $sid, 'AMOUNT' => $amount]);
	}
	$saveRep();
}

// --- новые товары — в акцию «Распродажа остатков» (её список «Товары по акции» — LINK_GOODS)
$promoIb = (int)CNextCache::$arIBlocks[SITE_ID]['aspro_next_content']['aspro_next_stock'][0];
$promo = $promoIb ? CIBlockElement::GetList([], ['IBLOCK_ID' => $promoIb, '=CODE' => ND_PROMO_CODE], false, false, ['ID'])->Fetch() : null;
$newIds = array_values(array_filter(array_column($rep['created'], 'id')));
if ($promo && $newIds && $apply) {
	$goods = [];
	$rs = CIBlockElement::GetProperty($promoIb, $promo['ID'], [], ['CODE' => 'LINK_GOODS']);
	while ($x = $rs->Fetch()) {
		if ($x['VALUE']) {
			$goods[] = (int)$x['VALUE'];
		}
	}
	$add = array_diff($newIds, $goods);
	if ($add) {
		$undo[] = ['promo' => (int)$promo['ID'], 'LINK_GOODS' => $goods];
		CIBlockElement::SetPropertyValuesEx($promo['ID'], $promoIb, ['LINK_GOODS' => array_merge($goods, $add)]);
		$rep['updated'][] = 'в акцию «Распродажа остатков» добавлены: ' . implode(', ', $add);
	}
} elseif (!$promo && $newIds) {
	$rep['updated'][] = 'акция ' . ND_PROMO_CODE . ' не найдена — новые товары в неё не добавлены';
}

// --- правила корзины: одна сумма скидки с шт. — одно правило nd_sale_<сумма>
$want = [];
foreach ($plan as $xml => $p) {
	if ($p['off'] > 0 && !empty($idByXml[$xml])) {
		$want[sprintf('%.2f', $p['off'])][] = $idByXml[$xml];
	}
}
$have = [];
$rs = CSaleDiscount::GetList([], [], false, false, ['ID', 'XML_ID', 'ACTIVE', 'ACTIONS']);
while ($d = $rs->Fetch()) {
	// nd_sale_<рубли>_<копейки>; правила комплектов nd_set_… сюда не попадают
	if (!preg_match('/^nd_sale_(\d+)_(\d{2})$/', (string)$d['XML_ID'], $m)) {
		continue;
	}
	$act = is_array($d['ACTIONS']) ? $d['ACTIONS'] : unserialize($d['ACTIONS'], ['allowed_classes' => false]);
	$ids = $act['CHILDREN'][0]['CHILDREN'][0]['DATA']['value'] ?? [];
	$have[$m[1] . '.' . $m[2]] = ['id' => (int)$d['ID'], 'ids' => array_map('intval', (array)$ids), 'act' => $act, 'active' => $d['ACTIVE']];
}
$actions = function ($value, $ids) {
	return ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND'], 'CHILDREN' => [[
		'CLASS_ID' => 'ActSaleBsktGrp',
		'DATA' => ['Type' => 'Discount', 'Value' => (float)$value, 'Unit' => 'CurEach', 'Max' => 0, 'All' => 'OR', 'True' => 'True'],
		'CHILDREN' => [['CLASS_ID' => 'CondIBElement', 'DATA' => ['logic' => 'Equal', 'value' => array_values($ids)]]],
	]]];
};
foreach ($want as $off => $ids) {
	sort($ids);
	if (isset($have[$off])) {
		$old = $have[$off]['ids'];
		sort($old);
		if ($old !== $ids || $have[$off]['active'] !== 'Y') {
			$rep['discounts'][] = "правило {$have[$off]['id']} −$off ₽: товары " . implode(',', $old) . ' → ' . implode(',', $ids) .
				($have[$off]['active'] !== 'Y' ? ', снова включено' : '');
			$undo[] = ['discount' => $have[$off]['id'], 'ACTIONS' => $have[$off]['act'], 'ACTIVE' => $have[$off]['active']];
			if ($apply) {
				CSaleDiscount::Update($have[$off]['id'], ['LID' => 's1', 'ACTIVE' => 'Y', 'ACTIONS' => $actions($off, $ids)]);
			}
		}
		continue;
	}
	$rep['discounts'][] = "новое правило −$off ₽ с шт.: " . implode(',', $ids);
	if ($apply) {
		$did = CSaleDiscount::Add([
			'LID' => 's1', 'NAME' => 'Распродажа: −' . $off . ' ₽ с шт. (' . count($ids) . ' тов.)',
			'XML_ID' => 'nd_sale_' . str_replace('.', '_', $off), 'ACTIVE' => 'Y', 'CURRENCY' => 'RUB', 'PRIORITY' => 1, 'SORT' => 100,
			'LAST_DISCOUNT' => 'N', 'LAST_LEVEL_DISCOUNT' => 'N', 'USE_COUPONS' => 'N', 'USER_GROUPS' => ND_USER_GROUPS,
			'CONDITIONS' => ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND', 'True' => 'True'], 'CHILDREN' => []],
			'ACTIONS' => $actions($off, $ids),
		]);
		if ($did) {
			$undo[] = ['discount' => (int)$did, 'created' => true];
		}
	}
}
foreach ($have as $off => $h) {
	if (!isset($want[$off]) && $h['active'] === 'Y') {
		$rep['discounts'][] = "правило {$h['id']} −$off ₽ больше не нужно — выключено";
		$undo[] = ['discount' => $h['id'], 'ACTIVE' => 'Y'];
		if ($apply) {
			CSaleDiscount::Update($h['id'], ['LID' => 's1', 'ACTIVE' => 'N']);
		}
	}
}

// Кеш каталога сбрасываем только если что-то поменяли: сброс тега инфоблока обнуляет кеш ВСЕГО каталога,
// и первые посетители после него ждут пересборки (по замеру 27.10.2026 раздел 0,8 с вместо 0,4).
if ($apply && ($undo || $rep['created'] || $rep['discounts'])) {
	CIBlock::clearIblockTagCache(ND_IBLOCK_ID);
	if ($promoIb) {
		CIBlock::clearIblockTagCache($promoIb);
	}
}
$saveRep();

echo ($apply ? 'Применено' : 'Сухой прогон') . ': новых ' . count($rep['created']) . ', выключено ' . count($rep['deactivated']) .
	', включено ' . count($rep['activated']) . ', изменений ' . count($rep['updated']) . ', правил скидок ' . count($rep['discounts']) . "\n";
foreach ($rep['created'] as $c) {
	echo "  + {$c['id']} {$c['name']} — {$c['price']} ₽ → {$c['sale']} ₽, остаток {$c['total']}" . ($c['photo'] ? ", фото с {$c['photo']}" : ', БЕЗ ФОТО') . "\n";
}
foreach (['deactivated' => '−', 'activated' => '↑'] as $k => $mark) {
	foreach ($rep[$k] as $c) {
		echo "  $mark {$c['id']} {$c['name']} ({$c['why']})\n";
	}
}
foreach (array_merge($rep['updated'], $rep['discounts']) as $line) {
	echo "  · $line\n";
}
echo "Отчёт: $outDir/report.json\n";


/**
 * Фото для нового товара — с того же товара основного каталога, у которого есть картинка. Ищем по коду 1С
 * (guid фида → ID на сайте по таблице обмена остатками local/stock-sync/stock_mapping.json), затем по sku
 * (ID торгового предложения; с 26.10.2026 в фиде его нет, но если вернётся — пригодится).
 */
function ndPhotoSource(string $guid, string $sku): ?array
{
	static $map = null;
	if ($map === null) {
		$f = $_SERVER['DOCUMENT_ROOT'] . '/local/stock-sync/stock_mapping.json';
		$map = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
	}
	$ids = [];
	if ($guid !== '' && !empty($map[$guid]['offer_id'])) {
		$ids[] = (int)$map[$guid]['offer_id'];
	}
	if (ctype_digit($sku)) {
		$ids[] = (int)$sku;
	}
	$e = null;
	foreach ($ids as $id) {
		$e = CIBlockElement::GetList([], ['ID' => $id, 'IBLOCK_ID' => [19, 20]], false, false,
			['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PICTURE', 'PREVIEW_PICTURE'])->Fetch();
		if ($e && ($e['DETAIL_PICTURE'] || $e['PREVIEW_PICTURE'])) {
			break;
		}
		$e = null;
	}
	if (!$e) {
		return null;
	}
	$e['MORE'] = [];
	$rs = CIBlockElement::GetProperty($e['IBLOCK_ID'], $e['ID'], ['sort' => 'asc'], ['CODE' => 'MORE_PHOTO']);
	while ($p = $rs->Fetch()) {
		if ($p['VALUE']) {
			$e['MORE'][] = (int)$p['VALUE'];
		}
	}
	return $e;
}
