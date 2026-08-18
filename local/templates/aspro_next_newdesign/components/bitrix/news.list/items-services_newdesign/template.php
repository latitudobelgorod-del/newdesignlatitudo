<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Блок «Оказанные услуги» на детальной проекта — новый дизайн.
 *
 * Макет: Figma «Чистовик», фрейм «Проект» 20524:98253, секция 20545:102842.
 * Заголовок Nunito Sans 800 52/57.2, под ним сетка 2×N: карточка 616 —
 * картинка 296×204 со скруглением 6 и плашкой «Услуга» в левом нижнем углу,
 * рядом колонка 296 с названием (700 18/21.6) и описанием (400 14/19.6).
 * Зазор между колонками 104, между рядами 36.
 *
 * Прежний шаблон (items-services) остаётся для старого дизайна.
 */
$this->setFrameMode(true);
?>
<? if ($arResult['ITEMS']): ?>
	<section class="nd-workserv">
		<? if ($arParams['TITLE']): ?>
			<h2 class="nd-workserv__title"><?= $arParams['TITLE'] ?></h2>
		<? endif; ?>

		<div class="nd-workserv__list">
			<? foreach ($arResult['ITEMS'] as $arItem): ?>
				<?
				$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

				$pic = $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] ?: $arItem['FIELDS']['DETAIL_PICTURE']['SRC'];
				/* Ссылка на услугу нужна не всегда: у элемента может не быть
				   детальной, и тогда тема прячет ссылку тем же условием. */
				$url = ($arParams['SHOW_DETAIL_LINK'] !== 'N'
					&& (strlen($arItem['DETAIL_TEXT']) || ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1)))
					? $arItem['DETAIL_PAGE_URL'] : '';
				$tag = $url ? 'a' : 'div';
				?>
				<<?= $tag ?> class="nd-workserv__item"<?= $url ? ' href="'.$url.'"' : '' ?> id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
					<span class="nd-workserv__pic">
						<? if ($pic): ?>
							<img src="<?= $pic ?>" loading="lazy"
								 alt="<?= htmlspecialcharsbx($arItem['PREVIEW_PICTURE']['ALT'] ?: $arItem['NAME']) ?>"
								 title="<?= htmlspecialcharsbx($arItem['PREVIEW_PICTURE']['TITLE'] ?: $arItem['NAME']) ?>">
						<? endif; ?>
						<span class="nd-workserv__tags"><span class="nd-workserv__tag">Услуга</span></span>
					</span>
					<span class="nd-workserv__body">
						<span class="nd-workserv__name"><?= $arItem['NAME'] ?></span>
						<? if (strlen($arItem['FIELDS']['PREVIEW_TEXT'])): ?>
							<span class="nd-workserv__text"><?= $arItem['FIELDS']['PREVIEW_TEXT'] ?></span>
						<? endif; ?>
					</span>
				</<?= $tag ?>>
			<? endforeach; ?>
		</div>
	</section>
<? endif; ?>
