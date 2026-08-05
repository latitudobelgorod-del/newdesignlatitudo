<?
/**
 * Раздел портфолио в хлебных крошках.
 *
 * Перенесено из старого шаблона news.list/news-project-catalog1: без этого
 * на странице раздела (/projects/terrasy-dlya-kafe-i-restoranov/) крошки
 * обрывались на «Портфолио проектов», хотя в старом дизайне раздел был
 * (Ирина, 2026-08-05).
 *
 * Делать это надо именно в component_epilog: он выполняется и при отдаче
 * из кэша, а сам шаблон — нет, и после первого хита крошка бы пропадала.
 */
global $APPLICATION;

if (isset($arResult['SECTION']) && is_array($arResult['SECTION']) && !empty($arResult['SECTION']['PATH'])) {
	foreach ($arResult['SECTION']['PATH'] as $arPath) {
		$APPLICATION->AddChainItem($arPath['NAME'], $arPath['SECTION_PAGE_URL']);
	}
}
?>
