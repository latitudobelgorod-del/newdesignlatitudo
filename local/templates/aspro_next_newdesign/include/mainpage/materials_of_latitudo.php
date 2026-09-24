<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Блок «Материалы от ЛАТИТУДО — современная замена дереву».
 *
 * Разметка живёт здесь, в шаблоне (под git). Тексты вынесены во включаемые области
 * в публичной части — правятся из админки без правки кода.
 * Стили — в css/newdesign.css этого же шаблона.
 */
$ndIncDir = SITE_DIR.'include/newdesign/mainpage/';
$ndArea = function ($file, $title) use ($ndIncDir) {
    global $APPLICATION;
    $APPLICATION->IncludeFile($ndIncDir.$file, [], ['MODE' => 'html', 'NAME' => $title]);
};
?>
<?/* Фон — WebP (241 КБ вместо 435 у JPEG, 24.09.2026). Основное правило
     .nd-materials__bg живёт в css/newdesign.css, а тот входит в общую сборку
     с урезанной копией (css/purged) — его правка потребовала бы пересборки
     копии, поэтому переопределяем здесь, с теми же градиентами. */?>
<style>
.nd-materials__bg {
    background:
        linear-gradient(90deg, rgba(0, 0, 0, 0.5) 0%, rgba(0, 0, 0, 0) 34%),
        linear-gradient(90deg, rgba(0, 0, 0, 0) 88%, rgba(0, 0, 0, 0.9) 100%),
        linear-gradient(332deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.9) 100%),
        #2b2b2b url("<?=SITE_TEMPLATE_PATH?>/images/newdesign/materials_bg.webp") center center / cover no-repeat;
}
</style>
<section class="nd-materials">
	<?// Фон с градиентами тянется во всю ширину экрана, содержимое — в контейнере 1440 ?>
	<div class="nd-materials__bg">
	<div class="nd-materials__inner">
		<div class="nd-materials__head">
			<h2 class="nd-materials__title"><? $ndArea('materials_title.php', 'Материалы: заголовок'); ?></h2>

			<div class="nd-materials__composition">
				<div class="nd-materials__composition-label"><? $ndArea('materials_composition.php', 'Материалы: подпись состава'); ?></div>
				<div class="nd-materials__tags"><? $ndArea('materials_tags.php', 'Материалы: состав ДПК'); ?></div>
			</div>
		</div>

		<div class="nd-materials__foot">
			<div class="nd-materials__advantages"><? $ndArea('materials_advantages.php', 'Материалы: преимущества'); ?></div>
			<div class="nd-materials__note"><? $ndArea('materials_note.php', 'Материалы: примечание'); ?></div>
		</div>
	</div>
	</div>
</section>
