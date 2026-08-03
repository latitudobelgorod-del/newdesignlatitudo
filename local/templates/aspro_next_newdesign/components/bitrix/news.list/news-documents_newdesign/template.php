<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Список файлов-документов (инструкции, схемы, сертификаты) нового дизайна.
 *
 * Копия шаблона news-documents с вёрсткой по макету Figma: сетка в три
 * колонки, слева контурная иконка документа, справа название 18/22 и
 * строка «Скачать PDF (27,9 мб)» со стрелкой вниз. Красная PDF-иконка
 * темы и голый размер файла заменены именно здесь.
 *
 * Вызывается из блока Sprint.Editor iblock_elements__aspro-licenses.php
 * (тоже копия внутри шаблона нового дизайна) — постраничка включается
 * там же, в настройках блока: NEWS_COUNT и DISPLAY_BOTTOM_PAGER.
 *
 * Стили — .nd-docs* в css/newdesign.css.
 */
if(!$arResult["ITEMS"])
	return;

/* Скрипт кнопки «Показать ещё» — тот же, что на отзывах и портфолио.
   Подключаем тегом здесь: блок выводится, когда <head> уже отдан. */
if (!defined('ND_UI_JS')) {
	define('ND_UI_JS', true);
	$ndUi = SITE_TEMPLATE_PATH.'/js/newdesign-ui.js';
	$ndUiAbs = $_SERVER['DOCUMENT_ROOT'].$ndUi;
	?><script src="<?= $ndUi ?><?= is_file($ndUiAbs) ? '?'.filemtime($ndUiAbs) : '' ?>"></script><?
}
?>
<div class="nd-docs">
	<?foreach($arResult["ITEMS"] as $arItem):?>
		<?
		$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
		$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

		if(empty($arItem['PROPERTIES']['INSTRUCTIONS']['VALUE']))
			continue;
		?>
		<div id="<?=$this->GetEditAreaId($arItem['ID']);?>" class="nd-docs__cell">
			<?foreach($arItem['PROPERTIES']['INSTRUCTIONS']['VALUE'] as $fileID):?>
				<?
				$arFile = CNext::GetFileInfo($fileID);
				if(!$arFile['SRC'])
					continue;
				$type = strtoupper($arFile['TYPE']);
				?>
				<a class="nd-docs__item" href="<?=$arFile["SRC"];?>" target="_blank"
				   itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue">
					<meta itemprop="name" content="<?=htmlspecialcharsbx($arItem["NAME"])?>">
					<link itemprop="url" href="<?=$arFile["SRC"];?>">

					<?// Иконка по макету: контурный лист с загнутым уголком.
					   // Инлайном, чтобы красилась currentColor вместе с текстом.?>
					<svg class="nd-docs__ico" width="32" height="40" viewBox="0 0 32 40" fill="none" aria-hidden="true" focusable="false">
						<path d="M19 1H5a4 4 0 0 0-4 4v30a4 4 0 0 0 4 4h22a4 4 0 0 0 4-4V13L19 1Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
						<path d="M19 1v8a4 4 0 0 0 4 4h8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
					</svg>

					<span class="nd-docs__body">
						<span class="nd-docs__name"><?=$arItem["NAME"]?></span>
						<span class="nd-docs__meta">
							<span>Скачать<?=($type ? ' '.$type : '')?><?=($arFile["FILE_SIZE_FORMAT"] ? ' ('.$arFile["FILE_SIZE_FORMAT"].')' : '')?></span>
							<svg class="nd-docs__arrow" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">
								<path d="M8 3v10m0 0 4-4m-4 4-4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</span>
				</a>
			<?endforeach;?>
		</div>
	<?endforeach;?>
</div>

<?// Навигация лежит рядом с сеткой, а не внутри — иначе стала бы её ячейкой.?>
<?if($arResult["NAV_STRING"]):?>
	<div class="nd-docs__nav"><?=$arResult["NAV_STRING"]?></div>
<?endif;?>
