<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Разделы портфолио плитками — для блока редактора «Разделы инфоблока»
 * (sprint.editor, iblock_sections__aspro-projects).
 *
 * Вид тот же, что у плиток разделов на общей странице /projects/ (Ирина,
 * 2026-08-11). Разметку обоим даёт ndSectionTile() из
 * include/parts/section_tile.php, стили `.nd-sectiles*` — глобальные, в
 * css/newdesign.css. Своего style.css у шаблона нет намеренно: он бы
 * продублировал сетку и она разъехалась бы с /projects/ при правке макета.
 *
 * Отдельный шаблон, а не тот же page_block: страница /projects/ сама выбирает
 * разделы первого уровня, а тут состав задаёт контент-менеджер, и приходит он
 * готовым от компонента.
 */
$this->setFrameMode(true);

require_once $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/parts/section_tile.php';

if (!$arResult['SECTIONS']) {
	return;
}
?>
<section class="nd-sectiles">
	<? foreach ($arResult['SECTIONS'] as $arSection): ?>
		<?
		$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection['IBLOCK_ID'], 'SECTION_EDIT'));

		/* Картинка приезжает то массивом (PICTURE.ID), то сырым ID (~PICTURE) —
		   зависит от SECTION_FIELDS вызова; берём что есть, иначе DETAIL_PICTURE. */
		$ndFileId = (int) ($arSection['PICTURE']['ID'] ?? $arSection['~PICTURE'] ?? 0);
		if (!$ndFileId) {
			$ndFileId = (int) ($arSection['DETAIL_PICTURE']['ID'] ?? $arSection['~DETAIL_PICTURE'] ?? 0);
		}

		ndSectionTile(
			$arSection['NAME'],
			$arSection['SECTION_PAGE_URL'],
			ndSectionTileImage($ndFileId),
			$this->GetEditAreaId($arSection['ID'])
		);
		?>
	<? endforeach; ?>
</section>
