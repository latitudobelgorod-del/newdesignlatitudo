<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Блок «Мы Латитудо» (о компании).
 *
 * Разметка живёт здесь, в шаблоне (под git). Тексты вынесены во включаемые области
 * в публичной части — правятся из админки без правки кода.
 * Стили — в css/newdesign.css этого же шаблона.
 *
 * Рейтинг — виджет Яндекса из регионального тега #REGION_TAG_RAITING#: у каждого
 * региона свой id организации, значение подставляется в буфер страницы обработчиком
 * Аспро. Тот же тег выводится в подвале — см. include/region_info_new_footer.php.
 */
global $arRegion;

$ndIncDir = SITE_DIR.'include/newdesign/mainpage/';
$ndArea = function ($file, $title) use ($ndIncDir) {
    global $APPLICATION;
    $APPLICATION->IncludeFile($ndIncDir.$file, [], ['MODE' => 'html', 'NAME' => $title]);
};
?>
<section class="nd-about">
	<div class="nd-about__inner">
		<?/* Порядок в разметке — мобильный: текст, фото, цифры.
		   На десктопе фото переезжает в левую колонку раскладкой grid в css. */?>
		<div class="nd-about__text">
			<h2 class="nd-about__title"><? $ndArea('about_title.php', 'О компании: заголовок'); ?></h2>
			<div class="nd-about__desc"><? $ndArea('about_desc.php', 'О компании: описание'); ?></div>
		</div>

		<div class="nd-about__photo">
			<img class="nd-about__img"
			     src="<?=SITE_TEMPLATE_PATH?>/images/newdesign/about_team.webp"
			     alt="Команда Латитудо" loading="lazy" width="1312" height="960">
			<? if ($arRegion && $arRegion['PROPERTY_REGION_TAG_RAITING_VALUE']): ?>
				<?/* Значок рейтинга Яндекс Бизнеса (iframe yandex.ru/sprav) рисует js/nd-lazy-widgets.js
				   по первому действию посетителя: в HTML — только номер организации из поля региона,
				   без адреса виджета (vlip.site, «трекеры до взаимодействия», 07.10.2026). Если номер
				   из поля не достаётся — значок не выводим. */
				$ndRating = $arRegion['PROPERTY_REGION_TAG_RAITING_VALUE'];
				if (is_array($ndRating)) $ndRating = $ndRating['TEXT'] ?? '';
				$ndRatingId = preg_match('~rating-badge/(\d+)~', html_entity_decode((string)$ndRating), $m) ? $m[1] : '';?>
				<? if ($ndRatingId !== ''): ?>
					<div class="nd-about__rating" data-nd-rating-id="<?=$ndRatingId?>" style="min-width:150px;min-height:50px"></div>
				<? endif; ?>
			<? endif; ?>
		</div>

		<div class="nd-about__stats"><? $ndArea('about_stats.php', 'О компании: цифры'); ?></div>
	</div>
</section>
