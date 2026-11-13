<?php
/**
 * Группы товаров «Распродажи» – кнопки над списком /catalog/rasprodazha/?group=<код> (Ирина, 13.11.2026).
 * Свойство ИБ 19 ND_SALE_GROUP (список, XML_ID значения = код группы). Группу ставит по названию
 * nd_sale_sync.php новым товарам и nd_sale_groups.php заведённым; поставленную руками не трогаем.
 * Порядок правил важен: берётся первое совпавшее («Доска заборная» – забор, а не террасная доска).
 */
const ND_SALE_GROUP_PROP = 'ND_SALE_GROUP';
// код => [название кнопки, правило по названию]; порядок = порядок кнопок на сайте
const ND_SALE_GROUPS = [
	'terrasnaya-doska'  => ['Террасная доска', '/террас|^\W*доска|террапол/ui'],
	'stupeni'           => ['Ступени', '/ступен/ui'],
	'ograzhdeniya'      => ['Ограждения', '/балясин|перил|поруч|столб|^\W*крышк|юбк/ui'], // крышка – только в начале: «Подвес … с крышкой» не ограждение
	'zabory'            => ['Заборы', '/забор|fencing|торцевая планка/ui'],
	'fasad'             => ['Фасад', '/фасад|cm wall/ui'],
	'gryadki'           => ['Грядки и клумбы', '/гряд|клумб/ui'],
	'komplektuyushchie' => ['Комплектующие', ''],
];
// в каком порядке проверять правила (кнопки – в порядке ND_SALE_GROUPS)
const ND_SALE_GROUP_CHECK = ['stupeni', 'gryadki', 'fasad', 'zabory', 'ograzhdeniya', 'terrasnaya-doska'];

function ndSaleGroupByName(string $name): string
{
	$name = preg_replace('/^\[Распродажа\]\s*/u', '', $name);
	foreach (ND_SALE_GROUP_CHECK as $code) {
		if (preg_match(ND_SALE_GROUPS[$code][1], $name)) {
			return $code;
		}
	}
	return 'komplektuyushchie';
}

// ID свойства и значений списка (код группы => ID значения); пусто, если свойства ещё нет
function ndSaleGroupEnums(int $iblockId): array
{
	$out = [];
	$rs = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => ND_SALE_GROUP_PROP]);
	while ($e = $rs->Fetch()) {
		$out[$e['XML_ID']] = (int)$e['ID'];
	}
	return $out;
}
