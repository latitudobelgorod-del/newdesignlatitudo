<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?php
/**
 * Блок «Доставка» в правой колонке корзины — под «Уточните наличие и условия
 * доставки» (макет Figma «Чистовик», фрейм корзины 20496:82623).
 *
 * Текст не свой: берём ту же включаемую область, что и детальная товара
 * (include/newdesign/element/delivery.php, классы .nd-pd__delivery-*), чтобы
 * условия доставки правились из публички в одном месте. Здесь только
 * заголовок и оформление под ширину колонки корзины.
 */
?>
<div class="nd-basket-delivery">
	<div class="nd-basket-delivery__title">Доставка</div>
	<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
		array(
			"COMPONENT_TEMPLATE" => ".default",
			"PATH" => SITE_DIR."include/newdesign/element/delivery.php",
			"AREA_FILE_SHOW" => "file",
			"AREA_FILE_SUFFIX" => "",
			"AREA_FILE_RECURSIVE" => "Y",
			"EDIT_TEMPLATE" => "standard.php"
		),
		false
	);?>
</div>
