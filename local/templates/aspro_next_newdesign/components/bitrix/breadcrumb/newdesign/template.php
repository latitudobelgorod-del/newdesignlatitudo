<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Хлебные крошки нового дизайна.
 *
 * По макету Figma (компонент Breadcrumbs, например 21349:57072): строка 12/16 500,
 * цвет #101014, разделитель — короткое тире, шаг между элементами 4.
 * Разметка своя и намеренно простая: у штатного шаблона next1 есть выпадашки
 * соседних разделов и мобильные стрелки, в макете их нет.
 *
 * Микроразметку BreadcrumbList сохраняем — она нужна поисковикам.
 */
$strReturn = '';

/**
 * Своя цепочка для отдельных страниц.
 *
 * Цепочку собирает CMain::GetNavChain из $sSectionName в .section.php по пути
 * страницы, и подменить её обычным способом нельзя: крошки печатает header.php
 * шаблона, то есть до того, как отработает сам компонент страницы. Но вывод
 * отложенный (bitrix:breadcrumb вешает GetNavChain через AddBufferContent), и
 * этот файл — как раз chain_template, он выполняется в самом конце страницы.
 * Поэтому страница может положить свои пункты в ND_CRUMBS_REPLACE, и они
 * заменят всё после «Главная».
 *
 * Так сделана страница поиска /catalog/?q=… (search.php шаблона комплексного
 * компонента): по адресу она лежит в каталоге, и без подмены цепочка выходила
 * «Главная — Каталог ДПК» вместо «Главная — Поиск» из макета.
 */
if (isset($GLOBALS['ND_CRUMBS_REPLACE']) && is_array($GLOBALS['ND_CRUMBS_REPLACE'])) {
	$arResult = array_merge(
		isset($arResult[0]) ? array($arResult[0]) : array(),
		$GLOBALS['ND_CRUMBS_REPLACE']
	);
}

if (!$arResult) {
	return $strReturn;
}

$cnt = count($arResult);

for ($index = 0; $index < $cnt; ++$index) {
	$arItem = $arResult[$index];
	$title = htmlspecialcharsex($arItem['TITLE']);
	$link = $arItem['LINK'];
	// последний пункт и ссылку на саму себя выводим текстом
	$isCurrent = ($link == '' || $link === GetPagePath() || $link.'index.php' === GetPagePath() || $index === $cnt - 1);

	if ($index) {
		$strReturn .= '<span class="nd-crumbs__sep" aria-hidden="true"></span>';
	}

	$strReturn .= '<span class="nd-crumbs__item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';

	if ($isCurrent) {
		$strReturn .= '<link href="'.($link ?: GetPagePath()).'" itemprop="item" />'
			.'<span class="nd-crumbs__current" itemprop="name">'.$title.'</span>';
	} else {
		$strReturn .= '<a class="nd-crumbs__link" href="'.$link.'" title="'.$title.'" itemprop="item">'
			.'<span itemprop="name">'.$title.'</span></a>';
	}

	$strReturn .= '<meta itemprop="position" content="'.($index + 1).'"></span>';
}

return '<nav class="nd-crumbs" aria-label="Хлебные крошки" itemscope itemtype="http://schema.org/BreadcrumbList">'.$strReturn.'</nav>';
