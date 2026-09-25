<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Обложка — первый блок под меню.
 *
 * Разметка живёт здесь, в шаблоне (под git). Текст и кнопка вынесены во
 * включаемую область — правятся из админки. Стили — в css/newdesign.css.
 *
 * Заголовок обложки во включаемой области — единственный H1 главной
 * (<h1 class="nd-cover__title">, с 14 сентября 2026; до этого был div).
 */
$ndIncDir = SITE_DIR.'include/newdesign/mainpage/';
?>
<section class="nd-cover">
	<div class="nd-cover__card">
		<?/* Фото обложки заменено 25.09.2026 (Ирина). Прежнее лежит рядом —
		     images/newdesign/cover_main.webp, 2004×720: вернуть — поменять src
		     и размеры обратно. */?>
		<img class="nd-cover__img"
		     src="<?=SITE_TEMPLATE_PATH?>/images/newdesign/cover_main_20260925.webp"
		     alt="Терраса с настилом, ограждениями и фасадом из ДПК Латитудо" width="1334" height="478">
		<div class="nd-cover__content"><?
			$APPLICATION->IncludeFile(
				$ndIncDir.'cover_text.php',
				[],
				['MODE' => 'html', 'NAME' => 'Обложка: текст и кнопка']
			);
		?></div>
	</div>
</section>
