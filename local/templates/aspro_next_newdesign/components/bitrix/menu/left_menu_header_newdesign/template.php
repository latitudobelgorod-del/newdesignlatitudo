<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Левая группа нижней строки шапки нового дизайна.
 *
 * Тип меню — left_menu_header_newdesign (файл .left_menu_header_newdesign.menu.php
 * в корне сайта). Пункты идут одной группой: в макете они прижаты друг к другу,
 * а правая группа отодвинута к другому краю строки.
 *
 * Пункт с PARAMS['CLASS'] = 'accent' выводится красным (как «Акции» в макете).
 * Стили — css/newdesign-header.css, класс .nd-menu-link.
 */
?>
<?if($arResult):?>
	<div class="nd-header__menu-group">
		<?foreach($arResult as $arItem):?>
			<?$class = isset($arItem['PARAMS']['CLASS']) ? trim($arItem['PARAMS']['CLASS']) : '';?>
			<a class="nd-menu-link<?=($class === 'accent' ? ' nd-menu-link--accent' : '')?><?=($arItem['SELECTED'] ? ' is-active' : '')?>"
			   href="<?=$arItem['LINK']?>"><?=$arItem['TEXT']?></a>
		<?endforeach;?>
	</div>
<?endif;?>
