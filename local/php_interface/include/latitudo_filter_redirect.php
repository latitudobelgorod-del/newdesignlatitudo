<?php
/**
 * Страница фильтра по одному бренду → раздел этого бренда (301).
 *
 * Задача (Ирина, 18 сентября 2026): если у бренда есть свой раздел, страница
 * фильтра по этому бренду — дубль раздела и отнимает у него запросы
 * (каннибализация). Пример: /catalog/terrasnaya-doska-iz-dpk/filter/brand-is-nextwood/
 * и /catalog/terrasnaya-doska-iz-dpk/terrasnaya-doska-nextwood/filter/brand-is-nextwood/
 * — это тот же список, что /catalog/terrasnaya-doska-iz-dpk/terrasnaya-doska-nextwood/.
 *
 * Правило:
 * - адрес /catalog/<раздел>/filter/brand-is-<код>/ (можно с apply/ на конце),
 *   в фильтре ТОЛЬКО бренд и ровно одно значение — бренд с цветом, размером и
 *   т.п. это уже своя выборка, её не трогаем;
 * - «раздел бренда» — активный раздел, все активные товары которого этого
 *   бренда. Ищем его среди: сам <раздел> (фильтр по бренду ничего не
 *   меняет) → его прямые подразделы (должен найтись ровно один, иначе не
 *   знаем, куда вести — например, три серии ограждений Polivan);
 * - параметры запроса (utm и т.п.) переносим.
 *
 * Список не ведётся руками: заведут новый раздел бренда — правило заработает
 * само. Соответствие кешируется на сутки с тегом инфоблока, поэтому обычные
 * хиты фильтра в базу не ходят.
 */

if (!class_exists('LatitudoFilterRedirect')) {

class LatitudoFilterRedirect
{
	const IBLOCK_CATALOG = 19;
	const IBLOCK_BRANDS = 12;
	const PROP_BRAND = 'BRAND';

	/**
	 * Адрес раздела бренда для адреса фильтра $url (путь, можно с ?query и в
	 * html-экранировании, как его отдаёт catalog.smart.filter) или ''.
	 * Им пользуются и редирект в local/init.php, и шаблон фильтра main_newdesign —
	 * чтобы кнопка «Показать» сразу вела на раздел, без 301.
	 */
	public static function sectionForFilterUrl($url)
	{
		$path = (string)parse_url(htmlspecialcharsback((string)$url), PHP_URL_PATH);
		if (strpos($path, '/catalog/') !== 0) {
			return '';
		}
		$path = self::landingRealUrl($path) ?: $path;

		/* Ровно одно значение бренда и больше ничего в фильтре: «-or-» — это уже
		   несколько брендов, другие свойства — своя выборка. */
		if (!preg_match('~^/catalog/((?:[a-z0-9_-]+/)+)filter/brand-is-([a-z0-9_]+(?:-[a-z0-9_]+)*)/(?:apply/)?$~', $path, $m)
			|| strpos($m[2], '-or-') !== false) {
			return '';
		}

		return self::target($m[1], $m[2]);
	}

	/** Адрес раздела бренда или '' — куда вести фильтр по бренду $brandCode в разделе с путём $sectionPath. */
	public static function target($sectionPath, $brandCode)
	{
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$key = 'nd_filter_brand_' . md5($sectionPath . '|' . $brandCode);
		if ($cache->initCache(86400, $key, '/nd_filter_brand')) {
			return (string)$cache->getVars();
		}
		$cache->startDataCache();
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache('/nd_filter_brand');
		$CACHE_MANAGER->RegisterTag('iblock_id_' . self::IBLOCK_CATALOG);
		$CACHE_MANAGER->RegisterTag('iblock_id_' . self::IBLOCK_BRANDS);

		$url = self::find($sectionPath, $brandCode);

		$CACHE_MANAGER->EndTagCache();
		$cache->endDataCache($url);

		return $url;
	}

	/**
	 * Настоящий адрес фильтра за посадочной SEO-подборок (sotbit.seometa) или ''.
	 * Берём только активные посадочные на фильтре по бренду — их единицы, список
	 * кешируется на сутки и на каждом хите каталога читается из кеша.
	 */
	public static function landingRealUrl($path)
	{
		static $map = null;
		if ($map === null) {
			$cache = \Bitrix\Main\Data\Cache::createInstance();
			if ($cache->initCache(86400, 'nd_seometa_brand_landings', '/nd_filter_brand')) {
				$map = $cache->getVars();
			} else {
				$map = array();
				global $DB;
				$rs = $DB->Query("SHOW TABLES LIKE 'b_sotbit_seometa_chpu'");
				if ($rs && $rs->Fetch()) {
					$rs = $DB->Query("SELECT NEW_URL, REAL_URL FROM b_sotbit_seometa_chpu WHERE ACTIVE = 'Y' AND REAL_URL LIKE '%/filter/brand-is-%'");
					while ($row = $rs->Fetch()) {
						$map[(string)$row['NEW_URL']] = (string)$row['REAL_URL'];
					}
				}
				$cache->startDataCache();
				$cache->endDataCache($map);
			}
		}

		return isset($map[$path]) ? $map[$path] : '';
	}

	private static function find($sectionPath, $brandCode)
	{
		if (!\Bitrix\Main\Loader::includeModule('iblock')) {
			return '';
		}

		$brand = CIBlockElement::GetList(array(), array('IBLOCK_ID' => self::IBLOCK_BRANDS, '=CODE' => $brandCode, 'CHECK_PERMISSIONS' => 'N'), false, array('nTopCount' => 1), array('ID'))->Fetch();
		if (!$brand) {
			return '';
		}
		$brandId = (int)$brand['ID'];

		$codes = explode('/', trim($sectionPath, '/'));
		$section = CIBlockSection::GetList(array(), array('IBLOCK_ID' => self::IBLOCK_CATALOG, '=CODE' => end($codes), 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'), false, array('ID', 'SECTION_PAGE_URL'))->GetNext();
		if (!$section) {
			return '';
		}

		if (self::isBrandSection((int)$section['ID'], $brandId)) {
			return $section['SECTION_PAGE_URL'];
		}

		$found = array();
		$rs = CIBlockSection::GetList(array(), array('IBLOCK_ID' => self::IBLOCK_CATALOG, 'SECTION_ID' => $section['ID'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'), false, array('ID', 'SECTION_PAGE_URL'));
		while ($child = $rs->GetNext()) {
			if (self::isBrandSection((int)$child['ID'], $brandId)) {
				$found[] = $child['SECTION_PAGE_URL'];
			}
		}

		return count($found) === 1 ? $found[0] : '';
	}

	/** В разделе (с подразделами) есть активные товары, и все они бренда $brandId. */
	private static function isBrandSection($sectionId, $brandId)
	{
		$filter = array('IBLOCK_ID' => self::IBLOCK_CATALOG, 'SECTION_ID' => $sectionId, 'INCLUDE_SUBSECTIONS' => 'Y', 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N');
		$all = (int)CIBlockElement::GetList(array(), $filter, array(), false, array('ID'));
		if ($all === 0) {
			return false;
		}
		$own = (int)CIBlockElement::GetList(array(), $filter + array('PROPERTY_' . self::PROP_BRAND => $brandId), array(), false, array('ID'));

		return $own === $all;
	}
}

}
