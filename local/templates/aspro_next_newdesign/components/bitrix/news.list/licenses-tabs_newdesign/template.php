<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * «Сертификаты и лицензии» с табами по производителям (10.11.2026).
 *
 * Копия news-licenses-editor_newdesign (сетка .nd-certs по макету Figma «Сертификаты»)
 * плюс ряд табов как на странице /video/ (.nd-vr__chip). Что выводить, решает только
 * галочка «Показывать на странице Лицензии и сертификаты» (SERT_LICENSE) — фильтр в
 * блоке редактора iblock_elements__aspro-licenses.php, вариант «style5». Табы —
 * по свойству «Привязка к производителю» (BRAND), см. result_modifier.php.
 *
 * Без скрипта видны все карточки; script.js прячет чужие и переключает группу
 * галереи fancybox, чтобы листалась только выбранная марка.
 */
if(!$arResult['ITEMS'])
	return;
?>
<div class="nd-certtabs" data-nd-certtabs>
	<?if(count($arResult['ND_TABS']) > 1):?>
		<div class="nd-certtabs__chips" role="group" aria-label="Производители">
			<button type="button" class="nd-certtabs__chip is-active" data-nd-cert-brand="" aria-pressed="true">Все <span class="nd-certtabs__chip-n"><?=count($arResult['ITEMS'])?></span></button>
			<?foreach($arResult['ND_TABS'] as $bid => $tab):?>
				<button type="button" class="nd-certtabs__chip" data-nd-cert-brand="<?=(int)$bid?>" aria-pressed="false"><?=htmlspecialcharsbx($tab['NAME'])?> <span class="nd-certtabs__chip-n"><?=(int)$tab['COUNT']?></span></button>
			<?endforeach;?>
		</div>
	<?endif;?>

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
			$arThumb = CFile::ResizeImageGet($arItem['PREVIEW_PICTURE'], array('width' => 450, 'height' => 638), BX_RESIZE_IMAGE_EXACT, false);
			$sThumbSrc = ($arThumb['src'] ? $arThumb['src'] : $sFullSrc);

			$sName = $arItem['NAME'];
			$sAlt = ($arItem['PREVIEW_PICTURE']['ALT'] ? $arItem['PREVIEW_PICTURE']['ALT'] : $sName);
			?>
			<a id="<?=$this->GetEditAreaId($arItem['ID']);?>" class="nd-certs__item fancy"
			   href="<?=$sFullSrc?>" rel="nd-certs" title="<?=htmlspecialcharsbx($sName)?>"
			   data-fancybox="nd-certs" data-caption="<?=htmlspecialcharsbx($sName)?>"
			   data-nd-cert-brands="<?=implode(' ', $arItem['ND_BRANDS'])?>">
				<span class="nd-certs__pic">
					<img class="nd-certs__img" src="<?=$sThumbSrc?>" alt="<?=htmlspecialcharsbx($sAlt)?>" loading="lazy">
				</span>
				<?if(strlen($sName)):?>
					<span class="nd-certs__name"><?=$sName?></span>
				<?endif;?>
			</a>
		<?endforeach;?>
	</div>
</div>
