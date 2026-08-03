<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Список пунктов мобильного меню нового дизайна.
 *
 * Тип меню — mobile_menu_newdesign (файл .mobile_menu_newdesign.menu.php
 * в корне сайта, пункты — в menus/mobile_menu_newdesign.php шаблона).
 *
 * Оформление пункта приходит из PARAMS (четвёртый параметр пункта меню):
 *   CLASS = strong — крупный жирный пункт верхней группы макета;
 *   ICON  = catalog — иконка слева;
 *   ARROW = N — без стрелки справа.
 * Стили — css/newdesign-mobile.css, класс .nd-mmenu.
 */
if(!$arResult) return;
$imgPath = SITE_TEMPLATE_PATH.'/images/newdesign/mobile';
?>
<nav class="nd-mmenu">
	<?foreach($arResult as $arItem):?>
		<?if($arItem["PERMISSION"] == "D") continue;?>
		<?
		$params  = (isset($arItem['PARAMS']) && is_array($arItem['PARAMS'])) ? $arItem['PARAMS'] : array();
		$bStrong = (isset($params['CLASS']) && trim($params['CLASS']) === 'strong');
		$bArrow  = (!isset($params['ARROW']) || $params['ARROW'] !== 'N');
		$icon    = isset($params['ICON']) ? preg_replace('/[^a-z0-9\-_]/', '', strtolower($params['ICON'])) : '';
		?>
		<a class="nd-mmenu__item<?=($bStrong ? ' nd-mmenu__item--strong' : '')?><?=($arItem['SELECTED'] ? ' is-active' : '')?>"
		   href="<?=$arItem['LINK']?>">
			<span class="nd-mmenu__ico">
				<?if($icon):?>
					<i class="nd-ico nd-ico--<?=$icon?>" style="-webkit-mask-image:url('<?=$imgPath?>/<?=$icon?>.svg');mask-image:url('<?=$imgPath?>/<?=$icon?>.svg')"></i>
				<?endif;?>
			</span>
			<span class="nd-mmenu__text"><?=$arItem['TEXT']?></span>
			<?if($bArrow):?>
				<i class="nd-mmenu__arrow"></i>
			<?endif;?>
		</a>
	<?endforeach;?>
</nav>
