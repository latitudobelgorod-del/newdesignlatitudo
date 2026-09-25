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
		<?/* Фото обложки с 25.09.2026 — терраса с плетёной мебелью и гамаком
		     (исходник 3524×1267, ужат до 1440 — 156 КБ, как прежняя обложка). Прежние лежат рядом:
		     cover_main.webp (2004×720, было до 25.09), cover_main_20260925.webp
		     (терраса с диваном) и cover_main_20260925b.webp (это же фото, 1334). */?>
		<img class="nd-cover__img"
		     src="<?=SITE_TEMPLATE_PATH?>/images/newdesign/cover_main_2026.webp?v=2"
		     alt="Терраса с настилом, ограждениями и фасадом из ДПК Латитудо" width="1440" height="518">
		<div class="nd-cover__content"><?
			$APPLICATION->IncludeFile(
				$ndIncDir.'cover_text.php',
				[],
				['MODE' => 'html', 'NAME' => 'Обложка: текст и кнопка']
			);
		?></div>
	</div>
</section>
