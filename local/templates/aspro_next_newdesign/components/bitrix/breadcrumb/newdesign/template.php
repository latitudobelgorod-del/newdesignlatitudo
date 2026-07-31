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
