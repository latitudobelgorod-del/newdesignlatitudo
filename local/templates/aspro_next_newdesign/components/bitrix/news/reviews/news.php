<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Страница «Отзывы» нового дизайна.
 *
 * От оригинала aspro_next отличается тем, что вся разметка ушла в шаблон
 * news.list `list_reviews_newdesign`: там и левая колонка с общим рейтингом
 * и кнопкой «Оставить отзыв», и карточки, и постраничная навигация — компонент
 * отдаёт её в $arResult['NAV_STRING'], поэтому её можно поставить внутрь своей вёрстки.
 *
 * Остальные файлы шаблона (detail.php, section.php, rss*.php, page_blocks/element_1.php)
 * скопированы из aspro_next как есть: Битрикс берёт шаблон компонента целиком,
 * частичного наследования у него нет.
 */
$this->setFrameMode(true);

// RSS-иконка в шапку — как в оригинале, если не отключена параметрами
if ($arParams['USE_RSS'] !== 'N') {
	$this->SetViewTarget('product_share');
	?><div class="colored_theme_hover_bg-block"><?= CNext::ShowRSSIcon($arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss']) ?></div><?
	$this->EndViewTarget();
}

include __DIR__.'/page_blocks/list_elements_1.php';
