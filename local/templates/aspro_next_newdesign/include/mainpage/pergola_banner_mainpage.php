<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Баннер «Комплект Пергола + мебель» — сразу под
 * блоком «Категории товаров», как на главной старого дизайна
 * (indexblocks_index1.php, область include/mainpage/pergola_banner.php).
 *
 * Картинка та же — /images/banners/pergola_main.jpg, разница только в
 * скруглении 4 (стили .nd-mainbanner в css/newdesign.css).
 *
 * Содержимое — включаемая область, правится из админки как соседние блоки
 * главной: include/newdesign/mainpage/pergola_banner.php. Тот файл вне Git,
 * поэтому на прод его копируем руками.
 */
?>
<div class="nd-mainbanner">
	<? $APPLICATION->IncludeFile(
		SITE_DIR.'include/newdesign/mainpage/pergola_banner.php',
		[],
		['MODE' => 'html', 'NAME' => 'Главная: баннер под категориями']
	); ?>
</div>
