<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/**
 * Мобильная шапка нового дизайна.
 *
 * Макет Figma «ЛАТИТУДО FINAL _ 2026», страница «Чистовик», фрейм «Главная»
 * 360×800 (узел 20498:111272), шапка — инстанс Header_Desktop 360×64:
 * логотип 258×40 слева, город (пин 16 + название 14/19.6 500) справа,
 * поля по 16, фон белый.
 *
 * Подключается напрямую из header.php шаблона вместо штатного
 * CNext::ShowPageType('header_mobile'): тот выбирает файл по настройке темы,
 * а она одна на весь сайт и к шаблону не привязана — боевой aspro_next тогда
 * тоже переехал бы на новую шапку.
 *
 * Стили — css/newdesign-mobile.css, поведение — js/newdesign-mobile.js.
 */

global $arRegion;

$imgPath  = SITE_TEMPLATE_PATH.'/images/newdesign/header';
$mImgPath = SITE_TEMPLATE_PATH.'/images/newdesign/mobile';
?>
<div class="nd-mheader" id="nd-mheader">
	<a class="nd-mlogo" href="/" title="Латитудо">
		<img class="nd-mlogo__mark" src="<?=$imgPath?>/logo-mark.svg" alt="Латитудо" width="42" height="40">
		<span class="nd-mlogo__text">
			<img class="nd-mlogo__word" src="<?=$imgPath?>/logo-word.svg" alt="LATITUDO" width="138" height="20">
			<span class="nd-mlogo__slogan">Террасы Заборы Фасады</span>
		</span>
	</a>

	<?if($arRegion && strlen($arRegion['NAME'])):?>
		<?// Свой попап выбора города не делаем: жмём штатный триггер
		// .js_city_chooser из десктопной шапки (она есть в разметке, просто
		// скрыта на мобильном) — иначе на странице появится второй
		// aspro:regionality.list.next и продублируется окно «Ваш город … ?».?>
		<button class="nd-mcity" type="button" data-nd-city-chooser>
			<img class="nd-mcity__pin" src="<?=$imgPath?>/pin.svg" alt="" width="16" height="16">
			<span class="nd-mcity__name"><?=htmlspecialcharsbx($arRegion['NAME'])?></span>
		</button>
	<?endif;?>
</div>
