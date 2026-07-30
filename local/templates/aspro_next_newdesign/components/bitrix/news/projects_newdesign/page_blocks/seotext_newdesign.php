<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * SEO-тексты раздела портфолио (блоки sprint.editor у раздела).
 *
 * Повторяет поведение старого дизайна (km_top.php / km_bottom.php шаблона
 * news-project-catalog1), только без простыни из девяти одинаковых case:
 * у каждого региона своё поле UF_EDITOR1_XXX / UF_EDITOR2_XXX, и если оно
 * пустое — показываем общий текст из UF_EDITOR1 / UF_EDITOR2.
 *
 * Ждёт от вызывающего кода:
 *  $ndIb            — ID инфоблока портфолио;
 *  $ndSectionId     — ID раздела;
 *  $ndSeoSlot       — 'top' или 'bottom'.
 */
$ndIb = (int) ($ndIb ?? 0);
$ndSectionId = (int) ($ndSectionId ?? 0);
$ndSeoSlot = ($ndSeoSlot ?? 'top') === 'bottom' ? 'bottom' : 'top';

if (!$ndIb || !$ndSectionId) {
	return;
}

if (!function_exists('ndSectionEditorHtml')) {
	/**
	 * HTML блоков sprint.editor у раздела. Пустой редактор отдаёт пустой
	 * <div> — такой результат считаем отсутствием текста.
	 *
	 * @return string
	 */
	function ndSectionEditorHtml($iblockId, $sectionId, $propertyCode)
	{
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			'sprint.editor:blocks',
			'.default',
			[
				'IBLOCK_ID' => $iblockId,
				'SECTION_ID' => $sectionId,
				'PROPERTY_CODE' => $propertyCode,
				'USE_JQUERY' => 'N',
				'USE_FANCYBOX' => 'N',
			],
			false,
			['HIDE_ICONS' => 'Y']
		);
		$buffer = trim(ob_get_clean());

		// пустая обёртка вида <div class="..."></div> — текста нет
		if (preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches) && !trim($matches[1])) {
			return '';
		}

		return $buffer;
	}
}

/** Регион → суффикс поля раздела. Список взят из старого шаблона. */
$ndRegionSuffix = [
	9277 => 'BEL',      // Белгород
	9278 => 'VRN',      // Воронеж
	9568 => 'KRD',      // Краснодар
	10039 => 'MSK',     // Москва
	10102 => 'KURSK',   // Курск
	22002 => 'LIPETSK', // Липецк
	22017 => 'TAMBOV',  // Тамбов
	22018 => 'ROSTOV',  // Ростов-на-Дону
	22029 => 'STAVR',   // Ставрополь
];

global $arRegion;
$ndBaseProp = $ndSeoSlot === 'bottom' ? 'UF_EDITOR2' : 'UF_EDITOR1';
$ndRegionProp = '';
if ($arRegion && isset($ndRegionSuffix[(int) $arRegion['ID']])) {
	$ndRegionProp = $ndBaseProp.'_'.$ndRegionSuffix[(int) $arRegion['ID']];
}

$ndSeoHtml = $ndRegionProp ? ndSectionEditorHtml($ndIb, $ndSectionId, $ndRegionProp) : '';
if (!$ndSeoHtml) {
	$ndSeoHtml = ndSectionEditorHtml($ndIb, $ndSectionId, $ndBaseProp);
}

if (!$ndSeoHtml) {
	return;
}
?>
<div class="nd-seotext nd-seotext--<?= $ndSeoSlot ?> editor"><?= $ndSeoHtml ?></div>
