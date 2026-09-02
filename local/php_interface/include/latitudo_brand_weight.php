<?php
/**
 * Приоритет своих марок в поиске.
 *
 * Задача (Ирина, 2 сентября 2026): и в подсказках шапки, и на странице выдачи
 * первыми показывать EasyDecking, следом LATITUDO, потом всё остальное.
 *
 * Подсказки сортируются в PHP (LatitudoQuickSearch), а выдачу собирает
 * bitrix:catalog.section запросом к базе — там сортировать можно только по
 * полю или свойству элемента. Марка у товара хранится привязкой к справочнику,
 * и сортировка по ней даёт порядок по ID бренда (LATITUDO 24936 раньше
 * EasyDecking 26365) — нужного порядка из него не выходит.
 *
 * Поэтому у товара заводится числовое свойство-вес: 1 — EasyDecking, 2 —
 * LATITUDO, 3 — остальные. По нему список и сортируется первым ключом.
 *
 * Свойство служебное: скрыто из фильтров и карточки, заполняется только кодом.
 * Значение пересчитывается обработчиком при каждом сохранении товара, поэтому
 * смена марки в админке или обмен с 1С его не рассинхронизируют.
 */

if (!class_exists('LatitudoBrandWeight')) {

class LatitudoBrandWeight
{
	const IBLOCK_PRODUCTS = 19;
	/** BRAND — привязка к справочнику производителей (ИБ 12). */
	const PROP_BRAND = 134;

	const CODE = 'ND_BRAND_WEIGHT';

	/** ID марок в справочнике → вес. Всё, чего здесь нет, получает WEIGHT_OTHER. */
	private static $weights = array(
		26365 => 1, // EasyDecking
		24936 => 2, // LATITUDO
	);
	const WEIGHT_OTHER = 3;

	private static $propId = null;

	/** Вес по ID марки. */
	public static function weightFor($brandId)
	{
		$brandId = (int)$brandId;

		return isset(self::$weights[$brandId]) ? self::$weights[$brandId] : self::WEIGHT_OTHER;
	}

	/**
	 * ID свойства-веса; создаёт его, если в инфоблоке ещё нет.
	 * Возвращает 0, только если создать не удалось.
	 */
	public static function propertyId()
	{
		if (self::$propId !== null) {
			return self::$propId;
		}

		$rs = CIBlockProperty::GetList(
			array(),
			array('IBLOCK_ID' => self::IBLOCK_PRODUCTS, 'CODE' => self::CODE)
		);
		if ($row = $rs->Fetch()) {
			return self::$propId = (int)$row['ID'];
		}

		$prop = new CIBlockProperty();
		$id = $prop->Add(array(
			'IBLOCK_ID'      => self::IBLOCK_PRODUCTS,
			'NAME'           => 'Приоритет марки в поиске',
			'CODE'           => self::CODE,
			'PROPERTY_TYPE'  => 'N',
			'SORT'           => 9000,
			'MULTIPLE'       => 'N',
			'IS_REQUIRED'    => 'N',
			/* Служебное: в умном фильтре и на карточке ему делать нечего. */
			'FILTRABLE'      => 'N',
			'SEARCHABLE'     => 'N',
			'ACTIVE'         => 'Y',
		));

		return self::$propId = ($id ? (int)$id : 0);
	}

	/** Марка товара по данным инфоблока. */
	public static function brandOf($elementId)
	{
		$rs = CIBlockElement::GetProperty(
			self::IBLOCK_PRODUCTS,
			(int)$elementId,
			array(),
			array('ID' => self::PROP_BRAND)
		);
		if ($row = $rs->Fetch()) {
			return (int)$row['VALUE'];
		}

		return 0;
	}

	/** Проставляет вес одному товару. */
	public static function apply($elementId)
	{
		$elementId = (int)$elementId;
		if ($elementId <= 0 || !self::propertyId()) {
			return;
		}

		CIBlockElement::SetPropertyValuesEx(
			$elementId,
			self::IBLOCK_PRODUCTS,
			array(self::CODE => self::weightFor(self::brandOf($elementId)))
		);
	}

	/**
	 * Обработчик сохранения элемента каталога. Вешается в local/init.php на
	 * OnAfterIBlockElementAdd и OnAfterIBlockElementUpdate.
	 *
	 * SetPropertyValuesEx сам вызывает события обновления свойств, но не
	 * OnAfterIBlockElementUpdate, так что рекурсии здесь нет.
	 */
	public static function onAfterSave(&$arFields)
	{
		if ((int)$arFields['IBLOCK_ID'] !== self::IBLOCK_PRODUCTS) {
			return;
		}
		if (empty($arFields['ID']) || empty($arFields['RESULT'])) {
			return;
		}

		self::apply($arFields['ID']);
	}

	/**
	 * Разовая заливка по всему каталогу. Возвращает число обработанных товаров.
	 * Запускается из tools/nd_brand_weight_fill.php.
	 */
	public static function fillAll()
	{
		if (!self::propertyId()) {
			return 0;
		}

		/* Марки читаем одним запросом на всех: GetProperty на каждый товар
		   означал бы пару тысяч запросов. */
		global $DB;
		$brands = array();
		$rs = $DB->Query(
			'SELECT IBLOCK_ELEMENT_ID, VALUE_NUM FROM b_iblock_element_property '
			.'WHERE IBLOCK_PROPERTY_ID = '.self::PROP_BRAND
		);
		while ($row = $rs->Fetch()) {
			$brands[(int)$row['IBLOCK_ELEMENT_ID']] = (int)$row['VALUE_NUM'];
		}

		$count = 0;
		$rsEl = CIBlockElement::GetList(
			array('ID' => 'ASC'),
			array('IBLOCK_ID' => self::IBLOCK_PRODUCTS),
			false,
			false,
			array('ID')
		);
		while ($el = $rsEl->Fetch()) {
			$id = (int)$el['ID'];
			CIBlockElement::SetPropertyValuesEx(
				$id,
				self::IBLOCK_PRODUCTS,
				array(self::CODE => self::weightFor(isset($brands[$id]) ? $brands[$id] : 0))
			);
			$count++;
		}

		return $count;
	}
}

}
