<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Блок «Проекты с товаром» на детальной товара — привязанное портфолио
 * (свойство LINK_PORTFOLIO). Заменяет вывод шаблоном news-project-catalog-pr.
 *
 * По макету: заголовок слева, справа счётчик «01/08» и стрелки; под ними
 * три карточки в ряд — картинка со скруглением 4, плашка типа материала
 * в левом нижнем углу и название под картинкой. Карточка повторяет блоки
 * главной («Полезно знать», «Проекты»), поэтому и размеры те же.
 *
 * Слайдер свой и очень простой: лента со скролл-снапом, стрелки листают
 * по видимой странице. Без скрипта лента остаётся горизонтально
 * прокручиваемой руками, поэтому блок работает и с выключенным JS.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
	return;
}

$ndTitle = trim((string) ($arParams['TITLE_BLOCK'] ?? '')) ?: 'Проекты с товаром';
$ndBadge = trim((string) ($arParams['BADGE_TEXT'] ?? '')) ?: 'Проект';
?>
<section class="nd-relprojects" data-nd-relprojects>
	<div class="nd-relprojects__head">
		<h2 class="nd-relprojects__title"><?= htmlspecialcharsbx($ndTitle) ?></h2>
		<div class="nd-relprojects__nav">
			<span class="nd-relprojects__counter" data-nd-relprojects-counter></span>
			<button type="button" class="nd-relprojects__arrow" data-nd-relprojects-prev aria-label="Предыдущие проекты">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<button type="button" class="nd-relprojects__arrow" data-nd-relprojects-next aria-label="Следующие проекты">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		</div>
	</div>

	<div class="nd-relprojects__track" data-nd-relprojects-track>
		<? foreach ($arResult['ITEMS'] as $arItem): ?>
			<?
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

			$src = '';
			$picId = (int) ($arItem['PREVIEW_PICTURE']['ID'] ?? $arItem['DETAIL_PICTURE']['ID'] ?? 0);
			if ($picId > 0) {
				$img = CFile::ResizeImageGet($picId, ['width' => 640, 'height' => 400], BX_RESIZE_IMAGE_EXACT, true);
				$src = $img['src'] ?? '';
			}

			$link = $arItem['DETAIL_PAGE_URL'];
			$tag = $link ? 'a' : 'div';
			?>
			<<?= $tag ?> class="nd-relprojects__item"<?= $link ? ' href="'.$link.'"' : '' ?> id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
				<span class="nd-relprojects__pic">
					<? if ($src): ?>
						<img src="<?= $src ?>" alt="<?= $arItem['NAME'] ?>" loading="lazy">
					<? endif; ?>
					<span class="nd-relprojects__badge"><?= htmlspecialcharsbx($ndBadge) ?></span>
				</span>
				<span class="nd-relprojects__name"><?= $arItem['NAME'] ?></span>
			</<?= $tag ?>>
		<? endforeach; ?>
	</div>
</section>
