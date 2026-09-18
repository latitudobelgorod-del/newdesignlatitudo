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
}

}
