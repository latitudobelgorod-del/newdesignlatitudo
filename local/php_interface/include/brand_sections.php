<?php
/* Разделы бренда: один порядок и одни подписи для якорей и для списка товаров.

   Якоря над списком рисует компонент catalog.section/ankor_section, сам список —
   catalog.section/catalog_blockcolors_newdesign. Это два независимых вызова
   компонента, и пока каждый сортировал разделы по-своему, якорь уводил не в тот
   блок: на easydecking у якорей осталось ID ASC, а список шёл по SORT. Чтобы
   разъехаться было негде, порядок и подписи считаются здесь, в одном месте. */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class LdBrandSections
{
	/**
	 * Разделы, в которых лежат переданные товары.
	 * Годится там, где на руках весь список (шаблон товаров бренда).
	 *
	 * @param array $items элементы каталога — $arResult['ITEMS']
	 * @return array [ID раздела => [ID, NAME, CODE, SORT]] в порядке SORT ASC, NAME ASC
	 */
	public static function fromItems(array $items)
	{
		$ids = [];
		foreach ($items as $item) {
			if (!empty($item['IBLOCK_SECTION_ID'])) {
				$ids[(int)$item['IBLOCK_SECTION_ID']] = true;
			}
		}

		return self::load(array_keys($ids));
	}

	/**
	 * Разделы всех товаров, попадающих под фильтр.
	 * Для якорей: их компонент читает всего сотню элементов, и по ней разделы
	 * получались неполные — у Millargo товаров 295, полтора десятка разделов
	 * в сотню не помещаются. Отдельный запрос считает разделы по всей выборке.
	 *
	 * @param int   $iblockId инфоблок каталога
	 * @param array $filter   фильтр товаров — тот же, что у компонента (FILTER_NAME)
	 * @return array тот же формат, что и у fromItems()
	 */
	public static function fromFilter($iblockId, array $filter)
	{
		$ids = [];
		$rs = CIBlockElement::GetList(
			[],
			array_merge($filter, ['IBLOCK_ID' => (int)$iblockId, 'ACTIVE' => 'Y']),
			['IBLOCK_SECTION_ID']
		);
		while ($row = $rs->Fetch()) {
			if (!empty($row['IBLOCK_SECTION_ID'])) {
				$ids[] = (int)$row['IBLOCK_SECTION_ID'];
			}
		}

		return self::load($ids);
	}

	/** Общий хвост: подтянуть разделы по ID и разложить в нужном порядке. */
	private static function load(array $ids)
	{
		if (!$ids) {
			return [];
		}

		$sections = [];
		$rs = CIBlockSection::GetList(
			['SORT' => 'ASC', 'NAME' => 'ASC'],
			['ID' => $ids, 'ACTIVE' => 'Y'],
			false,
			['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'SORT']
		);
		while ($row = $rs->Fetch()) {
			$sections[(int)$row['ID']] = [
				'ID' => (int)$row['ID'],
				'NAME' => self::title($row),
				'CODE' => $row['CODE'],
				'SORT' => (int)$row['SORT'],
			];
		}

		/* Порядок: сначала поле «Сортировка», при равных значениях — по алфавиту.
		   Пересортировываем уже здесь, а не полагаемся на ORDER BY: база
		   сортирует по NAME раздела, а на странице подписью служит его H1, и
		   это разные строки. По алфавиту должно читаться то, что видит человек.

		   Регистр приводим к нижнему: в UTF-8 заглавные кириллические буквы
		   идут раньше всех строчных, и «Ямы» оказались бы перед «аллеей». */
		uasort($sections, function ($a, $b) {
			if ($a['SORT'] !== $b['SORT']) {
				return $a['SORT'] <=> $b['SORT'];
			}

			return strcmp(
				mb_strtolower($a['NAME'], 'UTF-8'),
				mb_strtolower($b['NAME'], 'UTF-8')
			);
		});

		return $sections;
	}

	/**
	 * Подпись раздела — H1 из SEO-блока (SECTION_PAGE_TITLE), а не NAME:
	 * у разных серий есть одноимённые разделы («Террасная доска» и в Ко-Экс,
	 * и в Вуд-Икс), по имени их не различить, а в H1 серия указана.
	 */
	private static function title(array $section)
	{
		try {
			$seo = (new \Bitrix\Iblock\InheritedProperty\SectionValues(
				(int)$section['IBLOCK_ID'],
				(int)$section['ID']
			))->getValues();
			if (!empty($seo['SECTION_PAGE_TITLE'])) {
				return trim($seo['SECTION_PAGE_TITLE']);
			}
		} catch (\Throwable $e) {
			// H1 не заполнен или у инфоблока нет SEO-настроек — остаётся NAME
		}

		return $section['NAME'];
	}
}
