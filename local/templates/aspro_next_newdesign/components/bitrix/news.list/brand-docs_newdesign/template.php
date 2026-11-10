<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * «Документация» на странице производителя (бренда).
 *
 * Документы берутся из инфоблока «Лицензии и сертификаты» по свойству BRAND
 * («Привязка к производителю») — вызов в news/partners_newdesign/page_blocks/element_1.php.
 * До 10.11.2026 этот блок собирали руками в редакторе EDITOR2 марки (заголовок +
 * список ID); теперь документ достаточно привязать к производителю в его карточке.
 *
 * Вёрстка — копия news-documents_newdesign (сетка .nd-docs, стили в css/newdesign.css)
 * плюс заголовок h2 с якорем #dokumentatsiya, как был у блока редактора: на него
 * ведут ссылки из описаний марок. Нет документов — блока нет совсем.
 */
if(!$arResult["ITEMS"])
	return;
?>
<?// Стоит внутри div.editor страницы марки (element_1.php) — h2 и отступы оттуда.?>
<div class="nd-branddocs">
	<a class="sp-anchorlink" id="dokumentatsiya"></a>
	<h2><?=htmlspecialcharsbx(strlen($arParams["ND_TITLE"]) ? $arParams["ND_TITLE"] : "Документация")?></h2>
	<div class="nd-docs">
		<?foreach($arResult["ITEMS"] as $arItem):?>
			<?
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
			$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

			if(empty($arItem['PROPERTIES']['INSTRUCTIONS']['VALUE']))
				continue;
			?>
			<div id="<?=$this->GetEditAreaId($arItem['ID']);?>" class="nd-docs__cell">
				<?foreach((array)$arItem['PROPERTIES']['INSTRUCTIONS']['VALUE'] as $fileID):?>
					<?
					$arFile = CNext::GetFileInfo($fileID);
					if(!$arFile['SRC'])
						continue;
					$type = strtoupper($arFile['TYPE']);
					?>
					<a class="nd-docs__item" href="<?=$arFile["SRC"];?>" target="_blank">
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
</div>
