<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Правая группа нижней строки шапки нового дизайна.
 *
 * Тип меню — right_menu_header_newdesign (файл .right_menu_header_newdesign.menu.php
 * в корне сайта). В отличие от левой группы пункты не сбиваются в блок, а
 * раскладываются по строке — этим занимается justify-content родителя.
 *
 * Пункт с PARAMS['CLASS'] = 'accent' выводится красным.
 * Стили — css/newdesign-header.css, класс .nd-menu-link.
 */
?>
<?if($arResult):?>
	<?foreach($arResult as $arItem):?>
		<?$class = isset($arItem['PARAMS']['CLASS']) ? trim($arItem['PARAMS']['CLASS']) : '';?>
		<a class="nd-menu-link<?=($class === 'accent' ? ' nd-menu-link--accent' : '')?><?=($arItem['SELECTED'] ? ' is-active' : '')?>"
		   href="<?=$arItem['LINK']?>"><?=$arItem['TEXT']?></a>
	<?endforeach;?>
<?endif;?>
