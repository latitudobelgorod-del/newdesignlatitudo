<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>

<?/* Плитки подразделов по новому макету: карточка на светло-сером фоне,
	картинка сверху, название мелким текстом снизу. Количество колонок
	не задаём — сетка сама укладывает карточки по ~184px, поэтому рядом
	с баннером акции их встаёт 5, а без баннера — 7. */?>

<?if($arResult["SECTIONS"]):?>
	<div class="nd-subsec">
		<?foreach($arResult["SECTIONS"] as $arSection):?>
			<?
			$pictureID = ($arSection["PICTURE"]["ID"] ? $arSection["PICTURE"]["ID"] : $arSection["~PICTURE"]);
			$img = ($pictureID ? CFile::ResizeImageGet($pictureID, array("width" => 240, "height" => 160), BX_RESIZE_IMAGE_PROPORTIONAL, true) : false);
			$alt = ($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"]);
			$title = ($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"]);
			?>
			<a class="nd-subsec__item<?=($img ? "" : " nd-subsec__item--noimg")?>" href="<?=$arSection["SECTION_PAGE_URL"]?>" id="<?=$this->GetEditAreaId($arSection["ID"]);?>">
				<?if($img):?>
					<span class="nd-subsec__pic">
						<img src="<?=$img["src"]?>" alt="<?=$alt?>" title="<?=$title?>" loading="lazy" />
					</span>
				<?endif;?>
				<span class="nd-subsec__name"><?=$arSection["NAME"];?></span>
			</a>
		<?endforeach;?>
	</div>
<?endif;?>
