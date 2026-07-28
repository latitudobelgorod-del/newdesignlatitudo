<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Блок «Для профессионалов отрасли».
 *
 * Разметка живёт здесь, в шаблоне (под git). Заголовок, подзаголовок и сами
 * карточки вынесены во включаемые области — правятся из админки.
 * Стили — в css/newdesign.css этого же шаблона.
 */
$ndIncDir = SITE_DIR.'include/newdesign/mainpage/';
$ndArea = function ($file, $title) use ($ndIncDir) {
    global $APPLICATION;
    $APPLICATION->IncludeFile($ndIncDir.$file, [], ['MODE' => 'html', 'NAME' => $title]);
};
?>
<section class="nd-pro">
	<div class="nd-pro__head">
		<h2 class="nd-pro__title"><? $ndArea('professional_title.php', 'Профессионалам: заголовок'); ?></h2>
		<?/* Подзаголовок есть только в мобильном макете — на десктопе скрыт стилями */?>
		<div class="nd-pro__subtitle"><? $ndArea('professional_subtitle.php', 'Профессионалам: подзаголовок'); ?></div>
	</div>

	<div class="nd-pro__list"><? $ndArea('professional_cards.php', 'Профессионалам: карточки'); ?></div>
</section>
