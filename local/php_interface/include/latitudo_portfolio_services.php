<?php
/**
 * Услуги у работ портфолио по разделу.
 *
 * Задача (Ирина, 18 сентября 2026): у работы портфолио (ИБ 18) в свойстве
 * LINK_SERVICES «Услуги» должны стоять услуги её разделов, а «Расчет по вашим
 * размерам» — у всех. 18.09 это разово расставлено по всем 620 работам
 * (скрипт ~/nd_pf_add_service.php на проде), а этот обработчик держит правило
 * для новых работ.
 *
 * Когда срабатывает:
 * - новая работа — к тому, что менеджер указал сам, добавляются недостающие
 *   услуги её разделов и «Расчет»;
 * - сохранение существующей — только если услуг нет совсем. Иначе услуга,
 *   которую сняли руками, возвращалась бы при каждом сохранении.
 * Работа в нескольких разделах получает услуги каждого.
 *
 * SetPropertyValuesEx событий OnAfter…Update не вызывает — зацикливания нет.
 */

if (!class_exists('LatitudoPortfolioServices')) {

class LatitudoPortfolioServices
{
	const IBLOCK_PORTFOLIO = 18;
	const PROP = 'LINK_SERVICES';

	/** «Расчет по вашим размерам» — у всех работ. */
	const SERVICE_ALL = 25097;

	/** Раздел портфолио → услуги (ИБ 15). */
	private static $bySection = array(
		27  => array(27564, 27567, 10742), // Террасы и веранды частных домов: под ключ, монтаж доски, опоры
		273 => array(10023),               // Уличные ограждения: монтаж ограждений
		323 => array(22166),               // Фасады ДПК: монтаж фасадов
		107 => array(10972),               // Заборы: монтаж заборов
		333 => array(27590),               // Пирсы: пирсы на сваях
		191 => array(21746),               // Крыльцо и ступени: крыльцо на металлокаркасе
	);

	public static function onAfterSave(array $fields, $isNew)
	{
		$id = (int)$fields['ID'];
		if ($id <= 0 || (isset($fields['RESULT']) && !$fields['RESULT'])) {
			return;
		}

		$current = array();
		$rs = CIBlockElement::GetProperty(self::IBLOCK_PORTFOLIO, $id, array('sort' => 'asc', 'id' => 'asc'), array('CODE' => self::PROP));
		while ($row = $rs->Fetch()) {
			if ($row['VALUE']) {
				$current[] = (int)$row['VALUE'];
			}
		}
		if (!$isNew && $current) {
			return;
		}

		$wanted = array();
		$rs = CIBlockElement::GetElementGroups($id, true, array('ID'));
		while ($section = $rs->Fetch()) {
			$sid = (int)$section['ID'];
			if (isset(self::$bySection[$sid])) {
				$wanted = array_merge($wanted, self::$bySection[$sid]);
			}
		}
		$wanted[] = self::SERVICE_ALL;

		$result = array_values(array_unique(array_merge($current, $wanted)));
		if ($result == $current) {
			return;
		}

		CIBlockElement::SetPropertyValuesEx($id, self::IBLOCK_PORTFOLIO, array(self::PROP => $result));
	}

	/*
	 * Обратная связь товар → портфолио (Ирина, 18 сентября 2026).
	 *
	 * Проекты в карточке товара выводятся из свойства товара LINK_PORTFOLIO, а
	 * заполняют связь со стороны работы — LINK_GOODS. 18.09 LINK_PORTFOLIO разово
	 * пересобран зеркалом LINK_GOODS (~/nd_goods_link_portfolio.php на проде),
	 * дальше зеркало держат эти методы: после сохранения работы её ID есть ровно
	 * у тех товаров, что указаны в её LINK_GOODS; после удаления — ни у кого.
	 */
	const IBLOCK_GOODS = 19;
	const PROP_GOODS = 'LINK_GOODS';
	const PROP_BACK = 'LINK_PORTFOLIO';

	public static function syncGoods($portfolioId, $deleted = false)
	{
		$portfolioId = (int)$portfolioId;
		if ($portfolioId <= 0) {
			return;
		}

		$wanted = array();
		if (!$deleted) {
			$rs = CIBlockElement::GetProperty(self::IBLOCK_PORTFOLIO, $portfolioId, array(), array('CODE' => self::PROP_GOODS));
			while ($row = $rs->Fetch()) {
				if ((int)$row['VALUE'] > 0) {
					$wanted[(int)$row['VALUE']] = true;
				}
			}
		}

		// в LINK_GOODS может остаться ID удалённого товара — такие пропускаем
		if ($wanted) {
			$exists = array();
			$rs = CIBlockElement::GetList(array(), array('IBLOCK_ID' => self::IBLOCK_GOODS, 'ID' => array_keys($wanted), 'CHECK_PERMISSIONS' => 'N'), false, false, array('ID'));
			while ($row = $rs->Fetch()) {
				$exists[(int)$row['ID']] = true;
			}
			$wanted = array_intersect_key($wanted, $exists);
		}

		// товары, у которых работа сейчас стоит
		$has = array();
		$rs = CIBlockElement::GetList(array(), array(
			'IBLOCK_ID' => self::IBLOCK_GOODS,
			'=PROPERTY_' . self::PROP_BACK => $portfolioId,
			'CHECK_PERMISSIONS' => 'N',
		), false, false, array('ID'));
		while ($row = $rs->Fetch()) {
			$has[(int)$row['ID']] = true;
		}

		$touched = array_keys(array_diff_key($has, $wanted) + array_diff_key($wanted, $has));
		foreach ($touched as $goodId) {
			$links = array();
			$rs = CIBlockElement::GetProperty(self::IBLOCK_GOODS, $goodId, array('sort' => 'asc', 'id' => 'asc'), array('CODE' => self::PROP_BACK));
			while ($row = $rs->Fetch()) {
				if ((int)$row['VALUE'] > 0 && (int)$row['VALUE'] !== $portfolioId) {
					$links[] = (int)$row['VALUE'];
				}
			}
			if (isset($wanted[$goodId])) {
				$links[] = $portfolioId;
			}
			CIBlockElement::SetPropertyValuesEx($goodId, self::IBLOCK_GOODS, array(self::PROP_BACK => $links ? $links : false));
		}

		if ($touched) {
			CIBlock::clearIblockTagCache(self::IBLOCK_GOODS);
		}
	}
}

}
