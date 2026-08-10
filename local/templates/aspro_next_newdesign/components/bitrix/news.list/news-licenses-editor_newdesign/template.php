<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Сертификаты и лицензии нового дизайна.
 *
 * Копия шаблона news-licenses-editor с вёрсткой по макету Figma («Сертификаты»,
 * node 20543:93773): сетка в три колонки с шагом 24, карточка без рамки и
 * подложки — картинка 450:638 под обрез и подпись 18/25 слева под ней.
 * Прежний вариант рисовал четыре колонки в серых плашках с подписью по центру.
 *
 * Вызывается из блока Sprint.Editor iblock_elements__aspro-licenses.php
 * (папка aspro-news-element — раскладка редактора для детальных страниц
 * инфоблоков, ею пользуется только /info/sertifikaty-i-litsenzii/).
 *
 * По клику картинка открывается в галерее fancybox темы: класс .fancy и
 * общий rel — так группируются все карточки страницы.
 */
if(!$arResult['ITEMS'])
	return;
?>
<div class="nd-certs">
	<?foreach($arResult['ITEMS'] as $arItem):?>
		<?
		$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
		$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

		$sFullSrc = $arItem['PREVIEW_PICTURE']['SRC'];
		if(!$sFullSrc)
			$sFullSrc = $arItem['DETAIL_PICTURE']['SRC'];
		if(!$sFullSrc)
			continue;

		// 450x638 — пропорция карточки из макета (316x448) с запасом под retina.
		// EXACT кадрирует под эту пропорцию, как scaleMode FILL в Figma.
		$arThumb = CFile::ResizeImageGet($arItem['PREVIEW_PICTURE'], array('width' => 450, 'height' => 638), BX_RESIZE_IMAGE_EXACT, false);
		$sThumbSrc = ($arThumb['src'] ? $arThumb['src'] : $sFullSrc);

		$sName = $arItem['NAME'];
		$sAlt = ($arItem['PREVIEW_PICTURE']['ALT'] ? $arItem['PREVIEW_PICTURE']['ALT'] : $sName);
		?>
		<a id="<?=$this->GetEditAreaId($arItem['ID']);?>" class="nd-certs__item fancy"
		   href="<?=$sFullSrc?>" rel="nd-certs" title="<?=htmlspecialcharsbx($sName)?>"
		   data-fancybox="nd-certs" data-caption="<?=htmlspecialcharsbx($sName)?>">
			<span class="nd-certs__pic">
				<img class="nd-certs__img" src="<?=$sThumbSrc?>" alt="<?=htmlspecialcharsbx($sAlt)?>" loading="lazy">
			</span>
			<?if(strlen($sName)):?>
				<span class="nd-certs__name"><?=$sName?></span>
			<?endif;?>
		</a>
	<?endforeach;?>
</div>
