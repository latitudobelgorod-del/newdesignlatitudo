<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/**
 * Тизеры-преимущества под контактами.
 *
 * Макет Figma «ЛАТИТУДО FINAL _ 2026», страница «Чистовик»: десктоп —
 * узел 20560:104625 (4 карточки 316×234, зазор 24), мобильный — 20560:104712
 * (сетка 2×2, карточки 160×201, зазор 8).
 *
 * Подключается из шаблонов обеих страниц контактов:
 * news.list/contacts_region_newdesign (контакты региона) и
 * news.list/contacts_newdesign (общая разводка по городам).
 *
 * Состав задаётся руками — как у меню подвала: пунктов ровно четыре,
 * они не про контент из инфоблока, а про постоянные преимущества компании.
 * Иконки лежат в images/newdesign/contacts/icons/ (выгружены из макета,
 * 96×96, цвет зашит в файл).
 *
 * Стили — .nd-teasers* в css/newdesign.css.
 */

$ndTeasersIco = SITE_TEMPLATE_PATH.'/images/newdesign/contacts/icons/';

$arNdTeasers = array(
	array(
		'LABEL' => 'Преимущество 1',
		'ICON'  => 'Shield_1.svg',
		'TEXT'  => 'Гарантия лучшей цены на продукцию из ДПК',
	),
	array(
		'LABEL' => 'Преимущество 2',
		'ICON'  => 'Shield_2.svg',
		'TEXT'  => 'Проверенные временем, качественные материалы',
	),
	array(
		'LABEL' => 'Преимущество 3',
		'ICON'  => 'Shield_3.svg',
		'TEXT'  => 'Опытные технические специалисты и монтажники',
	),
	array(
		'LABEL' => 'Преимущество 4',
		'ICON'  => 'Shield_4.svg',
		'TEXT'  => 'Сервис полного цикла: замер, расчет, доставка, монтаж',
	),
);
?>
<?// Обёртка maxwidth-theme обязательна: у /contacts/ в .section.php стоит
   // WIDE_PAGE=Y, и тема не выводит .wrapper_inner — поля по краям задать
   // больше некому, блок пошёл бы от края до края.?>
<div class="maxwidth-theme">
	<div class="nd-teasers">
		<?foreach($arNdTeasers as $arTeaser):?>
			<div class="nd-teasers__item">
				<div class="nd-teasers__label"><?=$arTeaser['LABEL']?></div>
				<img class="nd-teasers__ico" src="<?=$ndTeasersIco.$arTeaser['ICON']?>" alt="" width="96" height="96" loading="lazy">
				<div class="nd-teasers__text"><?=$arTeaser['TEXT']?></div>
			</div>
		<?endforeach;?>
	</div>
</div>
