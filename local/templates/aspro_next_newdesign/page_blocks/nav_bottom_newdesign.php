<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/**
 * Прибитая нижняя панель мобильного нового дизайна и две её шторки.
 *
 * Макет Figma «ЛАТИТУДО FINAL _ 2026», страница «Чистовик»:
 *   - navbottombar 360×56 — узлы 20506:34484 / 34531 / 34578 (три состояния),
 *   - панель «Меню» 360×800 — узел 20506:103302,
 *   - шторка «Связаться с нами» — узел 20506:104021.
 *
 * Панель фиксирована снизу, пять вкладок: Главная, Меню, Каталог, Корзина,
 * Контакты. «Меню» и «Контакты» открывают шторки, остальные — обычные ссылки.
 * Активная вкладка красится в #c60000.
 *
 * Подключается из footer.php шаблона (после всего контента, чтобы шторки
 * лежали поверх). Стили — css/newdesign-mobile.css, поведение —
 * js/newdesign-mobile.js.
 */

global $APPLICATION, $arTheme, $arRegion, $arBasketPrices;

$imgPath = SITE_TEMPLATE_PATH.'/images/newdesign/mobile';

// Свойства региона читаем через хелпер: если регионы отключены, $arRegion пуст,
// и прямое обращение к ключам сыпало бы warning'ами в лог на каждый хит.
$ndRegion = function($prop) use ($arRegion) {
	return isset($arRegion['PROPERTY_'.$prop.'_VALUE']) ? $arRegion['PROPERTY_'.$prop.'_VALUE'] : '';
};

// Регион-теги подменяются обработчиком из bitrix/php_interface/init.php.
$REGION_TAG_PHONE         = "#REGION_TAG_PHONE#";
$REGION_TAG_PHONE_PODMENA = "#REGION_TAG_PHONE_PODMENA#";
$REGION_TAG_PHONESKLAD    = "#REGION_TAG_PHONESKLAD#";
$REGION_TAG_MAIL          = "#REGION_TAG_MAIL#";
$REGION_TAG_EMAIL_PODMENA = "#REGION_TAG_EMAIL_PODMENA#";

// Подмена номера и почты для платного трафика — как в шапке.
$utm_source = (!empty($_SESSION['UTM']['utm_source']) ? $_SESSION['UTM']['utm_source'] : 'empty');
$bPodmena = (
	strpos($utm_source, 'ya') !== false ||
	strpos($utm_source, 'tg') !== false ||
	strpos($utm_source, 'vk') !== false ||
	strpos($utm_source, 'maps') !== false
);

$ndTelHref = function($value) {
	$value = trim((string)$value);
	if(!strlen($value))
		return '';
	return 'tel:'.preg_replace('/[^0-9+]/', '', $value);
};

$basketUrl   = trim($arTheme['BASKET_PAGE_URL']['VALUE']);
$bShowBasket = (strlen($basketUrl) && CNext::getShowBasket());
$basketCount = (int)$arBasketPrices['BASKET_COUNT'];

$curPage = $APPLICATION->GetCurPage(true);
$curDir  = $APPLICATION->GetCurDir();

$bIsIndex   = ($curPage === SITE_DIR.'index.php');
$bIsCatalog = (strpos($curDir, SITE_DIR.'catalog/') === 0);
$bIsBasket  = ($bShowBasket && strpos($curDir, rtrim($basketUrl, '/').'/') === 0);

/**
 * Иконка панели: SVG подключаем маской, чтобы активная вкладка красилась
 * из CSS (в выгрузке из Figma цвет зашит в сам файл).
 */
$ndIco = function($name) use ($imgPath) {
	$url = $imgPath.'/'.$name.'.svg';
	return '<i class="nd-ico" style="-webkit-mask-image:url(\''.$url.'\');mask-image:url(\''.$url.'\')"></i>';
};
?>

<!-- Нижняя панель -->
<nav class="nd-navbar" id="nd-navbar">
	<a class="nd-navbar__tab<?=($bIsIndex ? ' is-active' : '')?>" href="/">
		<span class="nd-navbar__ico"><?=$ndIco('home')?></span>
		<span class="nd-navbar__label">Главная</span>
	</a>

	<button class="nd-navbar__tab" type="button" data-nd-open="menu" aria-expanded="false">
		<span class="nd-navbar__ico"><?=$ndIco('burger')?></span>
		<span class="nd-navbar__label">Меню</span>
	</button>

	<a class="nd-navbar__tab<?=($bIsCatalog ? ' is-active' : '')?>" href="/catalog/">
		<span class="nd-navbar__ico"><?=$ndIco('catalog')?></span>
		<span class="nd-navbar__label">Каталог</span>
	</a>

	<?if($bShowBasket):?>
		<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('nd-navbar-basket');?>
		<!-- noindex -->
		<?// Классы basket-link + basket и вложенный .count — не украшательство:
		// по этим селекторам скрипт темы (onCompleteAction → loadBasket
		// в js/main.js) обновляет счётчик после добавления товара, без
		// перезагрузки страницы. Бейдж выводим всегда, пустой прячет класс
		// empted — его тот же скрипт снимает и вешает сам.?>
		<a class="nd-navbar__tab basket-link basket<?=($basketCount ? ' basket-count' : '')?><?=($bIsBasket ? ' is-active' : '')?>" rel="nofollow" href="<?=$basketUrl?>">
			<span class="nd-navbar__ico">
				<?=$ndIco('bag')?>
				<span class="nd-navbar__count count<?=($basketCount ? '' : ' empted')?>"><?=$basketCount?></span>
			</span>
			<span class="nd-navbar__label">Корзина</span>
		</a>
		<!-- /noindex -->
		<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('nd-navbar-basket');?>
	<?endif;?>

	<button class="nd-navbar__tab" type="button" data-nd-open="contacts" aria-expanded="false">
		<span class="nd-navbar__ico"><?=$ndIco('phone-fill')?></span>
		<span class="nd-navbar__label">Контакты</span>
	</button>
</nav>
<!-- /Нижняя панель -->

<!-- Панель «Меню» -->
<div class="nd-sheet nd-sheet--full" id="nd-sheet-menu" hidden>
	<div class="nd-sheet__panel">

		<div class="nd-mmenu-head">
			<div class="nd-mmenu-head__city">
				<i class="nd-ico nd-mmenu-head__pin" style="-webkit-mask-image:url('<?=$imgPath?>/pin24.svg');mask-image:url('<?=$imgPath?>/pin24.svg')"></i>
				<span class="nd-mmenu-head__name"><?=($arRegion ? htmlspecialcharsbx($arRegion['NAME']) : '')?></span>
			</div>
			<?// Тот же штатный триггер выбора города, что и в шапке.?>
			<button class="nd-mmenu-head__change" type="button" data-nd-city-chooser>Изменить</button>
			<button class="nd-sheet__close" type="button" data-nd-close aria-label="Закрыть">
				<i class="nd-ico" style="-webkit-mask-image:url('<?=$imgPath?>/close24.svg');mask-image:url('<?=$imgPath?>/close24.svg')"></i>
			</button>
		</div>

		<div class="nd-mmenu-search">
			<?$APPLICATION->IncludeComponent(
				"bitrix:search.title",
				"newdesign_mobile",
				array(
					"NUM_CATEGORIES" => "1",
					"TOP_COUNT" => "5",
					"ORDER" => "rank",
					"USE_LANGUAGE_GUESS" => "N",
					"CHECK_DATES" => "Y",
					"SHOW_OTHERS" => "N",
					"PAGE" => CNext::GetFrontParametrValue("CATALOG_PAGE_URL"),
					"CATEGORY_0_TITLE" => "ALL",
					"CATEGORY_OTHERS_TITLE" => "OTHER",
					"CATEGORY_0_iblock_aspro_next_catalog" => array(19, 20),
					"SHOW_INPUT" => "Y",
					"INPUT_ID" => "nd-msearch-input",
					"CONTAINER_ID" => "nd-msearch",
					"PREVIEW_TRUNCATE_LEN" => "",
					"SHOW_PREVIEW" => "Y",
					"PRICE_CODE" => array("BASE", "OPT"),
					"CONVERT_CURRENCY" => "Y",
					"CURRENCY_ID" => "RUB",
					"PREVIEW_WIDTH" => "25",
					"PREVIEW_HEIGHT" => "25",
				),
				false, array("HIDE_ICONS" => "Y")
			);?>
		</div>

		<div class="nd-mmenu-body">
			<?$APPLICATION->IncludeComponent(
				"bitrix:menu",
				"mobile_newdesign",
				array(
					"ALLOW_MULTI_SELECT"    => "N",
					"CHILD_MENU_TYPE"       => "",
					"COMPONENT_TEMPLATE"    => "mobile_newdesign",
					"DELAY"                 => "N",
					"MAX_LEVEL"             => "1",
					"MENU_CACHE_TYPE"       => "A",
					"MENU_CACHE_TIME"       => "3600",
					"MENU_CACHE_USE_GROUPS" => "N",
					"CACHE_SELECTED_ITEMS"  => "N",
					"ROOT_MENU_TYPE"        => "mobile_menu_newdesign",
					"USE_EXT"               => "N",
					"MENU_CACHE_GET_VARS"   => array(),
				),
				false, array("HIDE_ICONS" => "Y")
			);?>
		</div>

	</div>
</div>
<!-- /Панель «Меню» -->

<!-- Панели второго уровня: каталог и разделы пунктов меню -->
<?// Макеты Figma: «Каталог» 20512:85302, «Категория» 20512:85390,
// «Каталог услуг» 20566:28664. Панель находит свой пункт по адресу
// (data-nd-msub-key = href), как и выпадающие панели десктопной шапки.?>
<?$APPLICATION->IncludeComponent(
	"bitrix:menu",
	"catalog_mobile_newdesign",
	array(
		"ALLOW_MULTI_SELECT"    => "N",
		"CHILD_MENU_TYPE"       => "top_menu_new",
		"COMPONENT_TEMPLATE"    => "catalog_mobile_newdesign",
		"COUNT_ITEM"            => "6",
		"DELAY"                 => "N",
		"MAX_LEVEL"             => "4",
		"MENU_CACHE_TYPE"       => "A",
		"MENU_CACHE_TIME"       => "3600",
		"MENU_CACHE_USE_GROUPS" => "N",
		"CACHE_SELECTED_ITEMS"  => "N",
		"ROOT_MENU_TYPE"        => "top_content_multilevel",
		"USE_EXT"               => "Y",
		"MENU_CACHE_GET_VARS"   => array(),
	),
	false, array("HIDE_ICONS" => "Y")
);?>
<?
$ndDropsMode = 'mobile';
include(__DIR__.'/header_drops_newdesign.php');
?>
<!-- /Панели второго уровня -->

<!-- Шторка «Связаться с нами» -->
<div class="nd-sheet nd-sheet--bottom" id="nd-sheet-contacts" hidden>
	<div class="nd-sheet__overlay" data-nd-close></div>
	<div class="nd-sheet__panel">

		<div class="nd-sheet__head">
			<span class="nd-sheet__grip"></span>
			<div class="nd-sheet__headrow">
				<span class="nd-sheet__title">Связаться с нами</span>
				<button class="nd-sheet__closetext" type="button" data-nd-close>Закрыть</button>
			</div>
		</div>

		<div class="nd-contacts">
			<?
			$bMainPodmena = ($bPodmena && $ndRegion('REGION_TAG_PHONE_PODMENA'));
			$mainPhoneValue = $bMainPodmena
				? $ndRegion('REGION_TAG_PHONE_PODMENA')
				: $ndRegion('REGION_TAG_PHONE');
			?>
			<?if($mainPhoneValue):?>
				<a class="nd-contacts__row" rel="nofollow" href="<?=$ndTelHref($mainPhoneValue)?>">
					<i class="nd-ico nd-contacts__ico" style="-webkit-mask-image:url('<?=$imgPath?>/phone24.svg');mask-image:url('<?=$imgPath?>/phone24.svg')"></i>
					<span class="nd-contacts__body">
						<span class="nd-contacts__val"><?=($bMainPodmena ? $REGION_TAG_PHONE_PODMENA : $REGION_TAG_PHONE)?></span>
						<span class="nd-contacts__note">Звонок бесплатный</span>
					</span>
				</a>
			<?endif;?>

			<?if($ndRegion('REGION_TAG_PHONESKLAD')):?>
				<a class="nd-contacts__row" rel="nofollow" href="<?=$ndTelHref($ndRegion('REGION_TAG_PHONESKLAD'))?>">
					<i class="nd-ico nd-contacts__ico" style="-webkit-mask-image:url('<?=$imgPath?>/phone24.svg');mask-image:url('<?=$imgPath?>/phone24.svg')"></i>
					<span class="nd-contacts__body">
						<span class="nd-contacts__val"><?=$REGION_TAG_PHONESKLAD?></span>
						<span class="nd-contacts__note">Звонок бесплатный (дополнительный)</span>
					</span>
				</a>
			<?endif;?>

			<?
			$bMailPodmena = ($bPodmena && $ndRegion('REGION_TAG_EMAIL_PODMENA'));
			$mailValue = $bMailPodmena
				? $ndRegion('REGION_TAG_EMAIL_PODMENA')
				: $ndRegion('REGION_TAG_MAIL');
			?>
			<?if($mailValue):?>
				<a class="nd-contacts__row" href="mailto:<?=htmlspecialcharsbx($mailValue)?>">
					<i class="nd-ico nd-contacts__ico" style="-webkit-mask-image:url('<?=$imgPath?>/mail24.svg');mask-image:url('<?=$imgPath?>/mail24.svg')"></i>
					<span class="nd-contacts__body">
						<span class="nd-contacts__val"><?=($bMailPodmena ? $REGION_TAG_EMAIL_PODMENA : $REGION_TAG_MAIL)?></span>
					</span>
				</a>
			<?endif;?>

			<?// Мессенджеры открывают те же веб-формы, что и кнопки десктопной шапки.
			// Заголовок окна тема берёт из текста триггера, а в макете подписи
			// короткие — задаём его отдельным атрибутом data-nd-form-title,
			// его читает js/newdesign-header.js.?>
			<a class="nd-contacts__row nd-contacts__row--max" data-event="jqm" data-param-form_id="MAX" data-name="spbuttonMAXmobileNewDesign" data-nd-form-title="Написать в MAX">
				<img class="nd-contacts__ico" src="<?=$imgPath?>/max24.svg" alt="" width="24" height="24">
				<span class="nd-contacts__body"><span class="nd-contacts__val">MAX</span></span>
			</a>
			<a class="nd-contacts__row nd-contacts__row--tg" data-event="jqm" data-param-form_id="TELEGRAM" data-name="spbuttonTELEGRAMmobileNewDesign" data-nd-form-title="Написать в Telegram">
				<img class="nd-contacts__ico" src="<?=$imgPath?>/tg24.svg" alt="" width="24" height="24">
				<span class="nd-contacts__body"><span class="nd-contacts__val">Телеграм</span></span>
			</a>
			<a class="nd-contacts__row nd-contacts__row--wa" data-event="jqm" data-param-form_id="WHATSAPP" data-name="spbuttonWHATSAPPmobileNewDesign" data-nd-form-title="Написать в WhatsApp">
				<img class="nd-contacts__ico" src="<?=$imgPath?>/wa24.svg" alt="" width="24" height="24">
				<span class="nd-contacts__body"><span class="nd-contacts__val">Вотсапп</span></span>
			</a>

			<?// Адрес офиса — та же включаемая область, что и в поповере «Шоурум»
			// десктопной шапки, чтобы правился из публички в одном месте.?>
			<div class="nd-contacts__row nd-contacts__row--addr">
				<i class="nd-ico nd-contacts__ico" style="-webkit-mask-image:url('<?=$imgPath?>/pin-fill24.svg');mask-image:url('<?=$imgPath?>/pin-fill24.svg')"></i>
				<span class="nd-contacts__body">
					<span class="nd-contacts__addr">
						<?$APPLICATION->IncludeComponent("bitrix:main.include", "",
							array(
								"AREA_FILE_SHOW" => "file",
								"PATH" => SITE_DIR."include/address_my.php",
								"EDIT_TEMPLATE" => "include_area.php"
							)
						);?>
					</span>
				</span>
			</div>

			<a class="nd-contacts__more" href="/contacts/">Контакты</a>
		</div>

	</div>
</div>
<!-- /Шторка «Связаться с нами» -->

<script src="<?=SITE_TEMPLATE_PATH?>/js/newdesign-mobile.js?<?=@filemtime($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/newdesign-mobile.js')?>"></script>
