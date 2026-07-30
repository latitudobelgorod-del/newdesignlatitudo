<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Страница «Портфолио» нового дизайна — сетка проектов.
 *
 * Размеры из макета (Figma «Чистовик», фрейм 20517:160693): контент 1336,
 * три карточки в ряд по 429 с зазором 24, картинка со скруглением 6,
 * подпись под ней. Навигация — общий с отзывами `pagination_newdesign`,
 * компонент отдаёт её в $arResult['NAV_STRING'].
 *
 * Плашки на картинке собираются из свойств инфоблока 18:
 *  SET_BRAND  — ярлык производителя (белая плашка сверху слева);
 *  VIDEO      — «Видео»;
 *  GALLEY_BIG — по длине галереи «N фото»;
 *  REVIEW     — «Отзыв», если текст отзыва заполнен.
 */
$this->setFrameMode(true);

// разметка карточки — общая с блоком «Вдохновитесь нашими проектами» на главной
require_once $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/parts/project_card.php';

/* Панель фильтров рисует page_blocks/list_elements_newdesign.php — этот
   шаблон кешируется по составу фильтра, и отмеченные пункты приезжали бы
   из чужого кеша. Сюда приходит только признак «фильтр включён». */
$ndF = is_array($arParams['ND_FILTER']) ? $arParams['ND_FILTER'] : [];
?>
<section class="nd-projects">
	<? if (!$arResult['ITEMS']): ?>
		<p class="nd-projects__empty"><?= !empty($ndF['active']) ? 'По выбранным фильтрам проектов нет.' : 'Проектов не найдено.' ?></p>
	<? else: ?>
		<div class="nd-projects__list">
			<? foreach ($arResult['ITEMS'] as $arItem): ?>
				<?
				$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

					ndProjectCard($arItem, $this->GetEditAreaId($arItem['ID']));
					?>
			<? endforeach; ?>
		</div>
	<? endif; ?>

	<? if ($arResult['NAV_STRING']): ?>
		<div class="nd-projects__nav"><?= $arResult['NAV_STRING'] ?></div>
	<? endif; ?>
</section>
