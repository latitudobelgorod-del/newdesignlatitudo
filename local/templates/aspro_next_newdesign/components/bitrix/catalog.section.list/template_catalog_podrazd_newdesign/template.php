<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
/**
 * Меню подразделов посадочных страниц каталога в левой колонке нового дизайна.
 *
 * Один шаблон вместо трёх копий старого дизайна — template_catalog_podrazd
 * (Фасады, раздел 83), template_catalog_podrazd_ogr (Ограждения, 104) и
 * template_catalog_podrazd_sad (Садовая мебель, 503). Они отличались только
 * прошитым ID раздела, а он и так приходит параметром SECTION_ID.
 *
 * Вид тот же, что у меню разделов каталога (template_catalog_newdesign):
 * заголовок раздела и сворачиваемый список подразделов. Раскрыт сразу —
 * мы уже внутри этого раздела, прятать его содержимое незачем.
 *
 * Из старых шаблонов не перенесён file_put_contents с дампом $arResult:
 * он писал служебные файлы в корень сайта на каждом хите.
 *
 * Стили — .nd-catmenu* в css/newdesign.css.
 */
$this->setFrameMode(true);

if(!$arResult["SECTIONS"])
	return;

$curPage = $APPLICATION->GetCurPage(false);

// Родительский раздел посадочной. Раньше его ID был прошит в каждом шаблоне.
$parentID = (int)$arParams["SECTION_ID"];
$arParent = array();
if($parentID)
{
	$rs = CIBlockSection::GetByID($parentID);
	if($row = $rs->GetNext())
		$arParent = $row;
}
?>
<nav class="nd-catmenu">
	<?foreach($arResult["SECTIONS"] as $arItems):?>
		<?if(empty($arItems["SECTIONS"])) continue;?>
		<?$bParentCurrent = ($arParent && $arParent['SECTION_PAGE_URL'] == $curPage);?>

		<details class="nd-catmenu__group<?=($bParentCurrent ? ' is-current' : '')?>" open>
			<summary class="nd-catmenu__head">
				<?if(!$arParent):?>
					<span class="nd-catmenu__name"><?=$arItems["NAME"]?></span>
				<?elseif($bParentCurrent):?>
					<span class="nd-catmenu__name"><?=$arParent["NAME"]?></span>
				<?else:?>
					<a class="nd-catmenu__name" href="<?=$arParent["SECTION_PAGE_URL"]?>"><?=$arParent["NAME"]?></a>
				<?endif;?>
				<i class="nd-catmenu__arrow" aria-hidden="true"></i>
			</summary>

			<ul class="nd-catmenu__list">
				<?foreach($arItems["SECTIONS"] as $arItem):?>
					<?$bCurrent = ($arItem['SECTION_PAGE_URL'] == $curPage);?>
					<li class="nd-catmenu__item<?=($bCurrent ? ' is-current' : '')?>">
						<a class="nd-catmenu__link" href="<?=$arItem["SECTION_PAGE_URL"]?>"><?=$arItem["NAME"]?></a>
					</li>
				<?endforeach;?>
			</ul>
		</details>
	<?endforeach;?>
</nav>
