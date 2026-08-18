<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Баннер «Комплект Пергола + мебель» — сразу под
 * блоком «Категории товаров», как на главной старого дизайна
 * (indexblocks_index1.php, область include/mainpage/pergola_banner.php).
 *
 * Картинка — pergola_main_r4.jpg, копия исходной pergola_main.jpg со
 * скруглёнными углами фотографии. Одним css скругление не сделать: в самой
 * картинке фотография лежит между двумя белыми полями (заголовок сверху,
 * плашки снизу), поэтому border-radius у <img> приходится на белое и не
 * виден. Радиус в файле 6px при ширине 1920 — это ровно 4px на экране,
 * где баннер выводится шириной 1336. Правило .nd-mainbanner img в
 * css/newdesign.css со скруглением 4 оставлено: оно нужно, если картинку
 * заменят на обычную, без белых полей.
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
