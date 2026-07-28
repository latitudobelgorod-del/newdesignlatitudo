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
<section class="nd-materials">
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
</section>
