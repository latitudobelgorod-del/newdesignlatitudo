<?
/**
 * Порядок карточек — тот, в котором менеджер перечислил элементы в редакторе
 * блоков. news.list вернул бы их по своей сортировке, поэтому пересобираем
 * массив по списку ID из фильтра блока (см. iblock_elements__aspro-licenses.php).
 */
global $sprintSearchFilter;

$arIDs = is_array($sprintSearchFilter['ID']) ? $sprintSearchFilter['ID'] : array();

$arSorted = array();
foreach($arIDs as $id)
{
	foreach($arResult['ITEMS'] as $arItem)
	{
		if($id == $arItem['ID'])
			$arSorted[] = $arItem;
	}
}
if($arSorted)
	$arResult['ITEMS'] = $arSorted;

/* Картинке нужны размеры и alt/title — их проставляет тема. */
foreach($arResult['ITEMS'] as $key => $arItem)
{
	CNext::getFieldImageData($arResult['ITEMS'][$key], array('PREVIEW_PICTURE'));
}
?>
