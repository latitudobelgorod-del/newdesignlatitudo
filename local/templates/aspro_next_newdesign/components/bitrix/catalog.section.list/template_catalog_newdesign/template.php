<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
/**
 * Меню разделов каталога в левой колонке нового дизайна.
 *
 * Копия шаблона template_catalog: тот же компонент, тот же result_modifier,
 * те же ссылки-анкоры UF_LINKSEO. Переделано одно — вложенность больше не
 * вываливается вся сразу (в старом виде колонка вырастала почти на 1900px),
 * а сворачивается: раскрыт только тот раздел, внутри которого мы находимся.
 *
 * Свёртка на <details>/<summary> — работает и без JS, как панель фильтров
 * на отзывах и портфолио. Стили — .nd-catmenu* в css/newdesign.css (общие с меню посадочных).
 */
$this->setFrameMode(true);

if(!$arResult["SECTIONS"])
	return;

$curPage = $APPLICATION->GetCurPage(false);

/**
 * Ссылки-анкоры из пользовательского поля раздела (UF_LINKSEO).
 * Выводились и в старом шаблоне; возвращаем разметкой, чтобы решить,
 * рисовать ли раздел сворачиваемым.
 */
$ndSeoLinks = function($sectionID) {
	$html = '';
	$rs = CIBlockSection::GetList(
		array(),
		array('IBLOCK_ID' => 19, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'ID' => $sectionID),
		false,
		array('UF_LINKSEO')
	);
	while($row = $rs->GetNext())
	{
		if(!is_array($row['UF_LINKSEO']))
			continue;
		foreach($row['UF_LINKSEO'] as $anchor)
			$html .= '<div class="nd-catmenu__seo">'.html_entity_decode($anchor).'</div>';
	}
	return $html;
};

/**
 * Раздел раскрыт, если открыта его страница или страница любого потомка.
 */
$ndIsOpen = function($arItem) use ($curPage, &$ndIsOpen) {
	if($arItem['SECTION_PAGE_URL'] == $curPage)
		return true;
	if(!empty($arItem['SECTIONS']))
	{
		foreach($arItem['SECTIONS'] as $arChild)
		{
			if($ndIsOpen($arChild))
				return true;
		}
	}
	return false;
};
?>
<nav class="nd-catmenu">
	<?foreach($arResult["SECTIONS"] as $arItem):?>
		<?
		$bCurrent = ($arItem['SECTION_PAGE_URL'] == $curPage);
		// Раздел 104 («Ограждения») в старом шаблоне детей не показывал —
		// у него своё меню на посадочной, поведение сохраняем.
		$bChildren = (!empty($arItem['SECTIONS']) && $arItem['ID'] != 104);
		// У текущего раздела анкоры не показывали и раньше.
		$seoHtml = $bCurrent ? '' : $ndSeoLinks($arItem['ID']);
		// Сворачиваем всё, что вообще имеет вложенность, — иначе у разделов
		// без подразделов анкоры торчали бы наружу и колонка снова разъезжалась.
		$bFoldable = ($bChildren || $seoHtml !== '');
		$bOpen = $ndIsOpen($arItem);
		?>

		<?if($bFoldable):?>
			<details class="nd-catmenu__group<?=($bCurrent ? ' is-current' : '')?>"<?=($bOpen ? ' open' : '')?>>
				<summary class="nd-catmenu__head">
					<?if($bCurrent):?>
						<span class="nd-catmenu__name"><?=$arItem["NAME"]?></span>
					<?else:?>
						<a class="nd-catmenu__name" href="<?=$arItem["SECTION_PAGE_URL"]?>"><?=$arItem["NAME"]?></a>
					<?endif;?>
					<i class="nd-catmenu__arrow" aria-hidden="true"></i>
				</summary>

				<?=$seoHtml?>

				<?if($bChildren):?>
					<ul class="nd-catmenu__list">
						<?foreach($arItem["SECTIONS"] as $arChild):?>
							<?$bChildCurrent = ($arChild['SECTION_PAGE_URL'] == $curPage);?>
							<li class="nd-catmenu__item<?=($bChildCurrent ? ' is-current' : '')?>">
								<a class="nd-catmenu__link" href="<?=$arChild["SECTION_PAGE_URL"]?>"><?=$arChild["NAME"]?></a>
								<?=$ndSeoLinks($arChild["ID"])?>
							</li>
						<?endforeach;?>
					</ul>
				<?endif;?>
			</details>
		<?else:?>
			<div class="nd-catmenu__group nd-catmenu__group--flat<?=($bCurrent ? ' is-current' : '')?>">
				<div class="nd-catmenu__head">
					<?if($bCurrent):?>
						<span class="nd-catmenu__name"><?=$arItem["NAME"]?></span>
					<?else:?>
						<a class="nd-catmenu__name" href="<?=$arItem["SECTION_PAGE_URL"]?>"><?=$arItem["NAME"]?></a>
					<?endif;?>
				</div>
			</div>
		<?endif;?>
	<?endforeach;?>
</nav>
