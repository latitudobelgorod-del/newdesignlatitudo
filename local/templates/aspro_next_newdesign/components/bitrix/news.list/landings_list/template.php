<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);?>

<?if($arResult['ITEMS']):?>
	<?$i = 0;?>
	<div class="items landings_list_inline">
		<?/*if($arParams["TITLE_BLOCK"]):?>
			<div class="title_block"><?=$arParams["TITLE_BLOCK"];?></div>
		<?endif;*/?>
		<div class="wrap">
			<div class="clearfix">
				<?$compare_field = (isset($arParams["COMPARE_FIELD"]) && $arParams["COMPARE_FIELD"] ? $arParams["COMPARE_FIELD"] : "DETAIL_PAGE_URL");
				$bProp = (isset($arParams["COMPARE_PROP"]) && $arParams["COMPARE_PROP"] == "Y");?>
				<?foreach($arResult['ITEMS'] as $arItem):?>
					<?
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

					++$i;
					$bHidden = ($i > $arParams["SHOW_COUNT"] ? true : false);
					$url_tag = $arItem["PROPERTIES"]["CPY_FILTER_TAG"]["VALUE"];
					$url = $arItem[$compare_field];
					if($bProp)
						$url = $arItem["PROPERTIES"][$compare_field]["VALUE"];
					if(empty($arItem["PROPERTIES"]["CPY_FILTER_TAG"]["VALUE"]))
						$url_link = $arItem["PROPERTIES"][$compare_field]["VALUE"];
					else
						$url_link = $arItem["PROPERTIES"]["CPY_FILTER_TAG"]["VALUE"];
					
//echo $url;
					?>
					<?/* Чип посадочной, на которой сейчас стоим, помечаем active: тег не
					   пропадает со своей же страницы, а показывает текущий выбор. Ссылкой
					   он никуда не уводит — адрес свой же, нажатие гасит css
					   (pointer-events). Сначала он вёл в раздел без фильтра, но так
					   получалось «нажал тег — ушёл на раздел» (Ирина, 25.09.2026).

					   Сверяем и технический адрес фильтра, и красивый: на посадочной
					   Сотбит подменяет REQUEST_URI на первый, а в адресной строке
					   остаётся второй. */?>
					<?
					$ndCurPath = rtrim((string)parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') . '/';
					$ndCurDir  = rtrim((string)$APPLICATION->GetCurDir(), '/') . '/';
					$ndActive  = false;
					foreach (array($url, $url_tag) as $ndOne) {
						if (!strlen((string)$ndOne))
							continue;
						$ndOnePath = rtrim((string)parse_url((string)$ndOne, PHP_URL_PATH), '/') . '/';
						if ($ndOnePath !== '/' && ($ndOnePath === $ndCurPath || $ndOnePath === $ndCurDir))
							$ndActive = true;
					}
					?>
					<div class="item" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
						<div>
							<?if(strlen($url)):?>
								<a class="<?=($ndActive ? 'active' : '')?>"<?=($ndActive ? ' aria-current="page"' : '')?> href="<?=$url_link?>" ><?=$arItem['NAME']?></a>
								
							<?else:?>
								<span><?=$arItem['NAME']?></span>
							<?endif?>
						</div>
					</div>
					<?if($bHidden && !$bHiddenOK):?>
						<?
						$bHiddenOK = true;
						?>
						</div>
						<div class="hidden_items clearfix">
					<?endif?>
				<?endforeach?>
			</div>
			<?if($bHidden):?>
				<div class="more"><span data-opened="N" data-text="<?=GetMessage("HIDE");?>"><?=GetMessage("SHOW_ALL");?></span></div>
			<?endif?>
		</div>
		<?//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/uipo.txt', print_r($arResult['ITEMS'], 1));?>
	</div>
<?endif?>