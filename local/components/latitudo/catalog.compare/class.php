<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

/**
 * Страница сравнения товаров (ТЗ Ирины 15.10.2026).
 *
 * Список — ndCompareList() (сессия + cookie), вкладки — ndCompareGroups()
 * (local/php_interface/include/latitudo_compare.php). Сравниваем только товары
 * одной вкладки; открыта та, куда добавили последний товар, или ?tab=.
 *
 * Строки таблицы и их порядок — ROWS ниже, по типу вкладки. Источник значения:
 *   P:КОД   — свойство товара (ИБ 19);
 *   A:Имя   — пара из «Характеристик» 1С (CML2_ATTRIBUTES, имя = описание);
 *   O:КОД   — свойство торговых предложений, все значения через запятую;
 *   SET     — гарантия из ярлыка «Гарантия N лет».
 * Берётся первый непустой источник. Свойства товаров не меняем (Ирина: «работай
 * с тем, что есть») — разные названия у брендов сводим здесь.
 *
 * Кеша нет: список у каждого посетителя свой, а товаров в нём единицы.
 */
class LatitudoCatalogCompare extends CBitrixComponent
{
	/** Единица цены вкладки: ID единицы измерения каталога (6 — м², 1 — п.м), 0 — за штуку. */
	private const UNITS = ['board' => 6, 'fence' => 1];
	private const UNIT_TITLE = [6 => 'м²', 1 => 'п.м', 0 => 'шт'];

	/** Единица цены вкладки, которая сейчас собирается. */
	private $unitTitle = 'шт';

	private const ROWS = [
		'board' => [
			['price'],
			['stock'],
			['Профиль', 'text', ['P:PROFIL_DOSKA_DPK', 'A:Профиль']],
			['Ширина, мм', 'num', ['P:IT_8']],
			['Толщина, мм', 'num', ['P:IT_6', 'P:TOLWINA_DOSKA_DPK']],
			['Длина, мм', 'text', ['O:DLINA']],
			['Применение', 'usage', []],
			['Гарантия / срок службы', 'text', ['P:GARANTY', 'SET', 'A:Гарантия'], 'max'],
			['Цвет', 'color', []],
			['Поверхность', 'text', ['P:SURFACE_DOSKA_DPK']],
			['Вес 1 пог. м, кг', 'num', ['P:VES_DOSKA_DPK']],
			['Расход на 1 м², пог. м', 'num', ['P:IT_11_DOSKA_DPK']],
			['Кляммеров на 1 м²', 'text', ['P:KL_DOSKA_DPK']],
			['Высота ножки кляммера, мм', 'text', ['P:VWS_NOJKI_DOSKA_DPK']],
			['Производитель', 'text', ['P:BRAND']],
		],
		'steps' => [
			['price'],
			['stock'],
			['Ширина, мм', 'num', ['P:IT_8']],
			['Толщина, мм', 'num', ['P:IT_6', 'P:TOLWINA_DOSKA_DPK']],
			['Длина, мм', 'text', ['O:DLINA']],
			['Профиль', 'text', ['P:FORMAT', 'A:Профиль']],
			['Применение', 'usage', []],
			['Гарантия', 'text', ['P:GARANTY', 'SET'], 'max'],
			['Цвет', 'color', []],
			['Вес 1 пог. м, кг', 'num', ['A:Вес 1 п.м., кг', 'A:Вес 1 пог. метра, кг', 'A:Вес 1 пог. м, кг']],
			['Производитель', 'text', ['P:BRAND']],
		],
		'fence' => [
			['price'],
			['stock'],
			['Ширина, мм', 'num', ['P:IT_8']],
			['Толщина, мм', 'num', ['P:IT_6', 'P:TOLWINA_DOSKA_DPK']],
			['Длина, мм', 'text', ['O:DLINA']],
			['Вид изделия', 'text', ['P:FORMAT']],
			['Профиль', 'text', ['A:Профиль']],
			['Цвет', 'color', []],
			['Гарантия', 'text', ['P:GARANTY', 'SET'], 'max'],
			['Рабочая ширина, мм', 'num', ['A:Рабочая ширина, мм']],
			['Вес 1 пог. м, кг', 'num', ['A:Вес 1 п.м., кг', 'A:Вес 1 пог. м, кг', 'A:Вес 1 пог. м., кг.']],
			['Производитель', 'text', ['P:BRAND']],
		],
		'supports' => [
			['Диапазон высоты, мм', 'text', ['P:HEIGHT_RANGE', 'A:Диапазон высот, мм']],
			['Нагрузка, кг', 'load', [], 'max'],
			['Угол коррекции наклона', 'text', ['A:Угол коррекции наклона', 'A:Угол уклона']],
			['Диаметр основания, мм', 'num', ['A:Диаметр основания, мм', 'A:Диаметр, мм']],
			['price'],
			['stock'],
			['Линейка', 'text', ['P:LINE']],
			['Материал', 'text', ['A:Материал']],
			['Производитель', 'text', ['P:BRAND']],
		],
		'furniture' => [
			['Размеры (Д × Ш × В), мм', 'size', []],
			['Материал каркаса', 'text', ['A:Материал каркаса', 'A:Каркас:', 'A:Материал каркаса (стул/кресло/диван)']],
			['Материал плетения', 'text', ['A:Материал плетения', 'P:ND_MATERIAL']],
			['Комплектация', 'text', ['A:Состав комплекта:', 'P:ND_KOMPLEKT']],
			['price'],
			['stock'],
			['Цвет', 'text', ['A:Цвет', 'P:ROTANG_COLOR', 'P:ASH_COLOR']],
			['Цвет ткани', 'text', ['A:Цвет ткани:', 'A:Цвет ткани']],
			['Столешница', 'text', ['A:Столешница:', 'A:Материал столешницы']],
			['Высота сиденья, мм', 'num', ['A:Высота сидения']],
			['Гарантия', 'text', ['A:Гарантия']],
			['Вес, кг', 'weight', [], ''],
			['Коллекция', 'text', ['A:Коллекция']],
			['Производитель', 'text', ['P:BRAND']],
		],
		'default' => [
			['price'],
			['stock'],
			['Производитель', 'text', ['P:BRAND']],
			// дальше — все «Характеристики» из 1С, что есть у товаров вкладки
		],
	];

	public function executeComponent()
	{
		if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog') || !function_exists('ndCompareList')) {
			return;
		}

		$ids = ndCompareList();
		$groups = ndCompareGroups($ids);
		$ids = array_values(array_filter($ids, static function ($id) use ($groups) {
			return isset($groups[$id]);
		}));

		// вкладки в порядке добавления первого товара; открыта — последняя добавленная
		$tabs = [];
		foreach ($ids as $id) {
			$g = $groups[$id];
			if (!isset($tabs[$g['KEY']])) {
				$tabs[$g['KEY']] = $g + ['IDS' => []];
			}
			$tabs[$g['KEY']]['IDS'][] = $id;
		}
		$active = (string)($this->request->get('tab') ?? '');
		if (!isset($tabs[$active])) {
			$active = $ids ? $groups[end($ids)]['KEY'] : '';
		}

		/* Все вкладки собираем сразу: переключаются они без перезагрузки
		   страницы (script.js шаблона), а товаров в списке — единицы. */
		$this->arResult = [
			'TABS' => $tabs,
			'ACTIVE' => $active,
			'PANELS' => [],
		];
		$allIds = $ids;
		$shownRelated = [];
		foreach ($tabs as $key => $tab) {
			$unit = self::UNITS[$tab['TYPE']] ?? 0;
			$this->unitTitle = self::UNIT_TITLE[$unit] ?? 'шт';
			$items = $this->loadItems($tab['IDS'], $unit);
			// «С этим покупают»: без сравниваемых товаров и без повторов между
			// вкладками — у одной карточки на странице были бы одинаковые id
			$related = array_values(array_diff($this->expandables($items, $allIds), $shownRelated));
			$shownRelated = array_merge($shownRelated, $related);
			$this->arResult['PANELS'][$key] = [
				'ITEMS' => $items,
				'ROWS' => $this->buildRows($tab['TYPE'], $items),
				'UNIT' => $this->unitTitle,
				'EXPANDABLES' => $related,
			];
		}

		$this->includeComponentTemplate();
	}

	/** Свойства, предложения, цена в единице вкладки, наличие в Москве. */
	private function loadItems(array $ids, int $unit): array
	{
		global $USER;
		$items = [];
		$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_COMPARE_IBLOCK, 'ID' => $ids, 'ACTIVE' => 'Y'], false, false,
			['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL']);
		while ($ob = $rs->GetNextElement()) {
			$f = $ob->GetFields();
			$items[(int)$f['ID']] = [
				'ID' => (int)$f['ID'],
				'NAME' => $f['~NAME'],
				'URL' => $f['DETAIL_PAGE_URL'],
				'PROPS' => $ob->GetProperties(),
				'OFFERS' => [],
			];
		}

		// предложения: длины, коэффициенты единиц
		$skuIblock = (int)(CCatalogSKU::GetInfoByProductIBlock(ND_COMPARE_IBLOCK)['IBLOCK_ID'] ?? 0);
		if ($skuIblock && $items) {
			$rs = CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'],
				['IBLOCK_ID' => $skuIblock, 'ACTIVE' => 'Y', 'PROPERTY_CML2_LINK' => array_keys($items)], false, false,
				['ID', 'IBLOCK_ID', 'PROPERTY_CML2_LINK']);
			while ($ob = $rs->GetNextElement()) {
				$f = $ob->GetFields();
				$pid = (int)$f['PROPERTY_CML2_LINK_VALUE'];
				if (isset($items[$pid])) {
					$items[$pid]['OFFERS'][(int)$f['ID']] = $ob->GetProperties();
				}
			}
		}

		$groupsArr = is_object($USER) ? $USER->GetUserGroupArray() : [2];
		$stockIds = [];
		foreach ($items as $pid => &$item) {
			$buyIds = $item['OFFERS'] ? array_keys($item['OFFERS']) : [$pid];
			/* Цена во всех единицах, что есть у товара: штука (цена каталога) и
			   единицы из «Коэффициентов единиц измерения» (цена × коэффициент,
			   как в плитке). По каждой — меньшая среди предложений. */
			$byUnit = [];
			foreach ($buyIds as $buyId) {
				$price = CCatalogProduct::GetOptimalPrice($buyId, 1, $groupsArr, 'N', [], SITE_ID);
				$value = (float)($price['RESULT_PRICE']['DISCOUNT_PRICE'] ?? 0);
				if ($value <= 0) {
					continue;
				}
				$koefProps = $item['OFFERS'][$buyId]['UNIT_KOEF'] ?? ($item['PROPS']['UNIT_KOEF'] ?? []);
				// цена каталога — за базовую единицу товара (обычно штука; у штуки
				// в справочнике нет обозначения — она идёт ключом 0, как единица вкладки)
				$base = (int)(CCatalogProduct::GetByID($buyId)['MEASURE'] ?? 0);
				$units = [($this->measureSymbol($base) === 'шт' ? 0 : $base) => 1.0];
				foreach ((array)($koefProps['VALUE'] ?? []) as $k => $v) {
					$mid = (int)($koefProps['DESCRIPTION'][$k] ?? 0);
					if ($mid > 0 && (float)$v > 0) {
						$units[$mid] = (float)$v;
					}
				}
				foreach ($units as $mid => $koef) {
					$unitPrice = $value * $koef;
					if (!isset($byUnit[$mid]) || $unitPrice < $byUnit[$mid]) {
						$byUnit[$mid] = $unitPrice;
					}
				}
			}
			// цена за единицу раздела — для подсветки и липкой шапки; нет такой
			// единицы у товара — цены за неё нет
			$item['PRICE'] = $byUnit[$unit] ?? null;
			$item['PRICES'] = $byUnit;
			foreach ($buyIds as $buyId) {
				$stockIds[$buyId] = $pid;
			}
		}
		unset($item);

		// наличие в Москве: по каждому предложению (у доски — по длинам)
		if ($stockIds) {
			$rs = CCatalogStoreProduct::GetList([], ['PRODUCT_ID' => array_keys($stockIds), 'STORE_ID' => ND_COMPARE_MSK_STORE], false, false, ['PRODUCT_ID', 'AMOUNT']);
			while ($row = $rs->Fetch()) {
				$pid = $stockIds[(int)$row['PRODUCT_ID']];
				$amount = (float)$row['AMOUNT'];
				if ($amount > 0) {
					$len = $items[$pid]['OFFERS'][(int)$row['PRODUCT_ID']]['DLINA']['VALUE'] ?? '';
					$items[$pid]['STOCK'][] = ['AMOUNT' => $amount, 'LEN' => (string)$len];
				}
			}
		}

		// порядок — как в списке сравнения
		$sorted = [];
		foreach ($ids as $id) {
			if (isset($items[$id])) {
				$sorted[$id] = $items[$id];
			}
		}
		return $sorted;
	}

	/** Обозначение единицы измерения каталога по ID (0 — штука). */
	private function measureSymbol(int $id): string
	{
		static $symbols = null;
		if ($symbols === null) {
			$symbols = [];
			$rs = CCatalogMeasure::getList([], [], false, false, ['ID', 'SYMBOL_RUS', 'MEASURE_TITLE']);
			while ($m = $rs->Fetch()) {
				$symbols[(int)$m['ID']] = (string)($m['SYMBOL_RUS'] ?: $m['MEASURE_TITLE']);
			}
		}
		return ($id && !empty($symbols[$id])) ? $symbols[$id] : 'шт';
	}

	private function buildRows(string $type, array $items): array
	{
		$defs = self::ROWS[$type] ?? self::ROWS['default'];
		if ($type === 'default') {
			$seen = [];
			foreach ($items as $item) {
				foreach ($this->attrs($item) as $name => $v) {
					$seen[$name] = true;
				}
			}
			foreach (array_keys($seen) as $name) {
				$defs[] = [rtrim($name, ': '), 'text', ['A:' . $name]];
			}
		}

		$rows = [];
		foreach ($defs as $def) {
			$kind = $def[1] ?? $def[0];
			$label = $def[0];
			$best = $def[3] ?? null;
			if ($kind === 'price') {
				$label = 'Цена за ' . $this->unitTitle;
				$best = 'min';
			} elseif ($kind === 'stock') {
				$label = 'Наличие в Москве';
			} elseif ($kind === 'num' && $best === null) {
				$best = '';
			}

			$cells = [];
			foreach ($items as $id => $item) {
				$cells[$id] = $this->cell($kind, $def[2] ?? [], $item);
			}

			// пусто у всех — строку не показываем
			$filled = array_filter($cells, static function ($c) {
				return $c['TEXT'] !== '';
			});
			if (!$filled) {
				continue;
			}
			// одинаково у всех — прячется переключателем «только различия»
			$texts = array_unique(array_map(static function ($c) {
				return $c['TEXT'];
			}, $cells));
			$same = count($texts) === 1;

			// лучшее значение — мягкая подсветка, только числа и только если они разные
			if ($best) {
				$nums = [];
				foreach ($cells as $id => $c) {
					if ($c['NUM'] !== null) {
						$nums[$id] = $c['NUM'];
					}
				}
				if (count($nums) >= 2 && count(array_unique($nums)) > 1) {
					$target = $best === 'min' ? min($nums) : max($nums);
					foreach ($nums as $id => $n) {
						if ($n == $target) {
							$cells[$id]['BEST'] = true;
						}
					}
				}
			}

			$rows[] = ['LABEL' => $label, 'KIND' => $kind, 'SAME' => $same, 'CELLS' => $cells];
		}
		return $rows;
	}

	/** Ячейка: HTML для показа, TEXT для сравнения «одинаково/разное», NUM для подсветки. */
	private function cell(string $kind, array $sources, array $item): array
	{
		$cell = ['HTML' => '', 'TEXT' => '', 'NUM' => null, 'BEST' => false];
		switch ($kind) {
			case 'price':
				if ($item['PRICE'] !== null) {
					$cell['NUM'] = round($item['PRICE']);
					$cell['TEXT'] = number_format($item['PRICE'], 0, ',', ' ') . ' ₽/' . $this->unitTitle;
					$cell['HTML'] = '<span class="nd-cmp__price">' . htmlspecialcharsbx($cell['TEXT']) . '</span>';
					// та же цена в других единицах — мелкой строкой (шт · м² · п.м)
					$other = [];
					foreach ($item['PRICES'] as $mid => $value) {
						$sym = $this->measureSymbol((int)$mid);
						if ($sym !== $this->unitTitle) {
							$other[] = number_format($value, 0, ',', ' ') . ' ₽/' . $sym;
						}
					}
					if ($other) {
						$cell['HTML'] .= '<span class="nd-cmp__price-alt">' . htmlspecialcharsbx(implode(' · ', $other)) . '</span>';
					}
				}
				break;

			case 'stock':
				if (!empty($item['STOCK'])) {
					$parts = [];
					foreach ($item['STOCK'] as $s) {
						$parts[] = $this->fmtNum($s['AMOUNT']) . ' шт' . ($s['LEN'] !== '' && count($item['STOCK']) > 1 ? ' (' . $s['LEN'] . ' мм)' : '');
					}
					$cell['TEXT'] = implode(', ', $parts);
					$cell['HTML'] = '<span class="nd-cmp__stock">Москва: ' . htmlspecialcharsbx($cell['TEXT']) . '</span>';
				}
				break;

			case 'usage':
				$enum = $this->usageEnum();
				$has = (array)($item['PROPS']['USAGE_DOSKA_DPK']['VALUE'] ?? []);
				$has = array_filter($has);
				if ($has && $enum) {
					$html = '';
					foreach ($enum as $value) {
						$ok = in_array($value, $has, true);
						$html .= '<div class="nd-cmp__usage"><i class="fa ' . ($ok ? 'fa-check' : 'fa-close') . '"></i> '
							. htmlspecialcharsbx($value) . '</div>';
					}
					$cell['HTML'] = $html;
					$cell['TEXT'] = implode('|', $has);
				}
				break;

			case 'color':
				$color = $this->colorOf($item);
				if ($color) {
					$cell['TEXT'] = $color['NAME'];
					$cell['HTML'] = ($color['FILE'] ? '<img class="nd-cmp__swatch" src="' . htmlspecialcharsbx($color['FILE']) . '" alt="" width="24" height="24" loading="lazy"> ' : '')
						. htmlspecialcharsbx($color['NAME']);
				}
				break;

			case 'size':
				$a = $this->attrs($item);
				$dims = array_filter([$a['Длина, мм'] ?? '', $a['Ширина, мм'] ?? '', $a['Высота, мм'] ?? '']);
				if (count($dims) === 3) {
					$cell['TEXT'] = implode(' × ', array_map([$this, 'fmtNum'], $dims));
				} elseif (!empty($a['Габариты:'])) {
					$cell['TEXT'] = trim(str_replace(['х', 'x', 'мм'], ['×', '×', ''], $a['Габариты:']));
				}
				break;

			case 'weight':
				$a = $this->attrs($item);
				if (!empty($a['Вес:'])) {
					$cell['TEXT'] = trim(str_ireplace('кг', '', $a['Вес:']));
				} elseif (!empty($a['Вес']) && is_numeric(str_replace(',', '.', $a['Вес']))) {
					// у 4SiS вес в граммах без единиц
					$cell['TEXT'] = $this->fmtNum((float)str_replace(',', '.', $a['Вес']) / 1000);
				}
				break;

			case 'load':
				$a = $this->attrs($item);
				if (!empty($a['Несущая мощность, кг'])) {
					$cell['NUM'] = $this->toNum($a['Несущая мощность, кг']);
				} elseif (!empty($a['Максимальная нагрузка, т'])) {
					$t = $this->toNum($a['Максимальная нагрузка, т']);
					$cell['NUM'] = $t !== null ? $t * 1000 : null;
				}
				if ($cell['NUM'] !== null) {
					$cell['TEXT'] = $this->fmtNum($cell['NUM']);
				}
				break;

			default: // text, num
				$value = $this->firstValue($sources, $item);
				if ($value !== '') {
					$cell['TEXT'] = $kind === 'num' ? $this->fmtNum($value) : $this->fmtText($value);
					$cell['NUM'] = $this->toNum($value);
				}
		}
		if ($cell['HTML'] === '' && $cell['TEXT'] !== '') {
			$cell['HTML'] = htmlspecialcharsbx($cell['TEXT']);
		}
		return $cell;
	}

	private function firstValue(array $sources, array $item): string
	{
		foreach ($sources as $src) {
			[$kind, $code] = array_pad(explode(':', $src, 2), 2, '');
			$value = '';
			if ($kind === 'P') {
				$value = $this->propText($item['PROPS'][$code] ?? null);
			} elseif ($kind === 'A') {
				$value = (string)($this->attrs($item)[$code] ?? '');
			} elseif ($kind === 'O') {
				$vals = [];
				foreach ($item['OFFERS'] as $offer) {
					$v = $this->propText($offer[$code] ?? null);
					if ($v !== '') {
						$vals[$v] = $v;
					}
				}
				natsort($vals);
				$value = implode(', ', $vals);
			} elseif ($kind === 'SET') {
				$set = $this->propText($item['PROPS']['SET'] ?? null);
				if (preg_match('~гарантия\s+(.+)~iu', $set, $m)) {
					$value = $m[1];
				}
			}
			if (trim($value) !== '') {
				return trim($value);
			}
		}
		return '';
	}

	private function propText($prop): string
	{
		if (!is_array($prop)) {
			return '';
		}
		$value = $prop['VALUE'];
		if ($prop['PROPERTY_TYPE'] === 'E' && $value) {
			$names = [];
			foreach ((array)$value as $eid) {
				$names[] = $this->elementName((int)$eid);
			}
			return implode(', ', array_filter($names));
		}
		if (is_array($value)) {
			return implode(', ', array_filter(array_map('strval', $value), 'strlen'));
		}
		return (string)$value;
	}

	private function elementName(int $id): string
	{
		static $cache = [];
		if (!isset($cache[$id])) {
			$row = CIBlockElement::GetList([], ['ID' => $id], false, false, ['ID', 'NAME'])->Fetch();
			$cache[$id] = $row ? (string)$row['NAME'] : '';
		}
		return $cache[$id];
	}

	/** «Характеристики» из 1С: [имя => значение]. */
	private function attrs(array $item): array
	{
		$p = $item['PROPS']['CML2_ATTRIBUTES'] ?? null;
		$out = [];
		if ($p && is_array($p['VALUE'])) {
			foreach ($p['VALUE'] as $k => $v) {
				$name = trim((string)($p['DESCRIPTION'][$k] ?? ''));
				if ($name !== '' && trim((string)$v) !== '' && !isset($out[$name])) {
					$out[$name] = trim((string)$v);
				}
			}
		}
		return $out;
	}

	private function usageEnum(): array
	{
		static $enum = null;
		if ($enum === null) {
			$enum = [];
			$rs = CIBlockPropertyEnum::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => ND_COMPARE_IBLOCK, 'CODE' => 'USAGE_DOSKA_DPK']);
			while ($e = $rs->Fetch()) {
				$enum[] = $e['VALUE'];
			}
		}
		return $enum;
	}

	/** Цвет из справочника (COLOR_MAIN_EL → HL 9): название и картинка. */
	private function colorOf(array $item): ?array
	{
		$xml = (string)($item['PROPS']['COLOR_MAIN_EL']['VALUE'] ?? '');
		if ($xml === '' || !Loader::includeModule('highloadblock')) {
			return null;
		}
		static $cache = [];
		if (!array_key_exists($xml, $cache)) {
			$cache[$xml] = null;
			$hl = \Bitrix\Highloadblock\HighloadBlockTable::getById(9)->fetch();
			if ($hl) {
				$class = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hl)->getDataClass();
				$row = $class::getList(['filter' => ['=UF_XML_ID' => $xml], 'select' => ['UF_NAME', 'UF_FILE'], 'limit' => 1])->fetch();
				if ($row && $row['UF_NAME']) {
					$cache[$xml] = ['NAME' => (string)$row['UF_NAME'], 'FILE' => $row['UF_FILE'] ? (string)CFile::GetPath($row['UF_FILE']) : ''];
				}
			}
		}
		return $cache[$xml];
	}

	/** «С этим покупают» для вкладки: что продаётся вместе со сравниваемыми товарами. */
	private function expandables(array $items, array $exclude): array
	{
		$ids = [];
		foreach ($items as $item) {
			foreach ((array)($item['PROPS']['EXPANDABLES']['VALUE'] ?? []) as $eid) {
				$eid = (int)$eid;
				if ($eid && !in_array($eid, $exclude, true)) {
					$ids[$eid] = ($ids[$eid] ?? 0) + 1;
				}
			}
		}
		arsort($ids); // чаще всего встречающиеся — первыми
		return array_slice(array_keys($ids), 0, 12);
	}

	private function toNum($value): ?float
	{
		if (preg_match('~-?\d+(?:[.,]\d+)?~', (string)$value, $m)) {
			return (float)str_replace(',', '.', $m[0]);
		}
		return null;
	}

	/** Число с запятой и без лишних нулей: 6.850 → 6,85; 1500 → 1 500. */
	public function fmtNum($value): string
	{
		$s = trim((string)$value);
		if (!preg_match('~^-?\d+(?:[.,]\d+)?$~', $s)) {
			return $this->fmtText($s);
		}
		$f = (float)str_replace(',', '.', $s);
		$dec = (floor($f) == $f) ? 0 : 2;
		$out = number_format($f, $dec, ',', ' ');
		if ($dec) {
			$out = rtrim(rtrim($out, '0'), ',');
		}
		return $out;
	}

	/** Дробные числа внутри текста — тоже с запятой («6.58» → «6,58»). */
	private function fmtText(string $s): string
	{
		return preg_replace('~(\d)\.(\d)~', '$1,$2', $s);
	}
}
