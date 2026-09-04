<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/**
 * Шапка нового дизайна (десктоп).
 *
 * Сделана по макету Figma «ЛАТИТУДО FINAL _ 2026», страница «Чистовик»,
 * компонент Header_Desktop (узел 21029:61339) и попапы «Шоурум»/«Склад»
 * (21029:61916, 21029:62007). Логика повторяет шапку easydecking.ru
 * (page_blocks/header_9.php), плюс добавлено поле поиска.
 *
 * Подключается напрямую из header.php шаблона aspro_next_newdesign, а не через
 * CNext::ShowPageType('header'): штатный механизм выбирает файл по настройке
 * темы HEADER_TYPE, а она одна на сайт и к шаблону не привязана — боевой
 * aspro_next тогда тоже переехал бы на новую шапку.
 *
 * Стили — css/newdesign-header.css, поведение — js/newdesign-header.js,
 * иконки — images/newdesign/header/*.svg (выгружены из того же макета).
 */

global $arTheme, $arRegion, $arBasketPrices;

$arRegions = CNextRegionality::getRegions();
$regionID  = ($arRegion ? $arRegion['ID'] : '');

// Свойства региона читаем через хелпер: если регионы отключены, $arRegion пуст,
// и прямое обращение к ключам сыпало бы warning'ами в лог на каждый хит.
$ndRegion = function($prop) use ($arRegion) {
	return isset($arRegion['PROPERTY_'.$prop.'_VALUE']) ? $arRegion['PROPERTY_'.$prop.'_VALUE'] : '';
};

// Регион-теги подменяются обработчиком из bitrix/php_interface/init.php.
$REGION_TAG_PHONE           = "#REGION_TAG_PHONE#";
$REGION_TAG_PHONE_PODMENA   = "#REGION_TAG_PHONE_PODMENA#";
$REGION_TAG_PHONESKLAD      = "#REGION_TAG_PHONESKLAD#";
$REGION_TAG_MAIL            = "#REGION_TAG_MAIL#";
$REGION_TAG_EMAIL_PODMENA   = "#REGION_TAG_EMAIL_PODMENA#";
$REGION_TAG_LINKVIDEO       = "#REGION_TAG_LINKVIDEO#";

// Подмена номера для платного трафика — как в боевой шапке header_8.php.
$utm_source = (!empty($_SESSION['UTM']['utm_source']) ? $_SESSION['UTM']['utm_source'] : 'empty');
/* Подмена контактов — при любой utm-метке (ndIsUtmVisit из local/init.php).
   Раньше здесь был список источников ya/tg/vk/maps. */
$bPodmena = ndIsUtmVisit();

// Регионы, у которых в шапке показываем шоурум и склад (списки из header_8.php).
$arShowroomRegions = array(9277, 9278, 9568, 10039, 22018);
$arSkladRegions    = array(9277, 9278, 9568, 10039);

$imgPath = SITE_TEMPLATE_PATH.'/images/newdesign/header';

/**
 * Телефон из свойства региона: возвращает готовый href или пустую строку.
 */
$ndTelHref = function($value) {
	$value = trim((string)$value);
	if(!strlen($value))
		return '';
	return 'tel:'.preg_replace('/[^0-9+]/', '', $value);
};

// Кнопки основной строки. Порядок и подписи — из макета.
$arMainNav = array(
	array('TEXT' => 'Услуги',       'LINK' => '/services/'),
	array('TEXT' => 'Партнерам',    'LINK' => '/info/'),
	array('TEXT' => 'Портфолио',    'LINK' => '/projects/'),
);

// Нижняя строка меню — два обычных меню Битрикса, правятся из админки:
// .left_menu_header_newdesign.menu.php и .right_menu_header_newdesign.menu.php
// в корне сайта. Параметры одинаковые, отличаются только типом и шаблоном.
$arBottomMenuParams = array(
	"ALLOW_MULTI_SELECT"    => "N",
	"CHILD_MENU_TYPE"       => "",
	"DELAY"                 => "N",
	"MAX_LEVEL"             => "1",
	"MENU_CACHE_TYPE"       => "A",
	"MENU_CACHE_TIME"       => "3600",
	"MENU_CACHE_USE_GROUPS" => "N",
	"CACHE_SELECTED_ITEMS"  => "N",
	"USE_EXT"               => "N",
	"MENU_CACHE_GET_VARS"   => array(),
);

$basketUrl   = trim($arTheme['BASKET_PAGE_URL']['VALUE']);
$bShowBasket = (strlen($basketUrl) && CNext::getShowBasket());
$basketCount = (int)$arBasketPrices['BASKET_COUNT'];
?>
<?=bitrix_sessid_post();?>

<div class="nd-header" id="nd-header">
	<div class="nd-header__inner">

		<!-- Верхняя строка -->
		<div class="nd-header__top">

			<div class="nd-header__geo">
				<?if($arRegions):?>
					<span class="nd-geo__city">
						<img class="nd-geo__pin" src="<?=$imgPath?>/pin.svg" alt="" width="16" height="16">
						<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
							array(
								"COMPONENT_TEMPLATE" => ".default",
								"PATH" => SITE_DIR."include/top_page/regionality.list.php",
								"AREA_FILE_SHOW" => "file",
								"AREA_FILE_SUFFIX" => "",
								"AREA_FILE_RECURSIVE" => "Y",
								"EDIT_TEMPLATE" => "include_area.php"
							),
							false
						);?>
					</span>
				<?endif;?>

				<?if(in_array($regionID, $arShowroomRegions)):?>
					<!-- Шоурум -->
					<span class="nd-pop" data-nd-pop="showroom">
						<span class="nd-pop__label" tabindex="0">Шоурум</span>
						<div class="nd-pop__body" hidden>
							<button class="nd-pop__close" type="button" aria-label="Закрыть">
								<img src="<?=$imgPath?>/close.svg" alt="" width="24" height="24">
							</button>
							<div class="nd-pop__title">Шоурум</div>
							<div class="nd-pop__row">
								<img class="nd-pop__icon" src="<?=$imgPath?>/pin20.svg" alt="" width="20" height="20">
								<div class="nd-pop__text nd-pop__text--strong">
									<?$APPLICATION->IncludeComponent("bitrix:main.include", "",
										array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/address_my.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
									);?>
								</div>
							</div>
							<div class="nd-pop__row">
								<img class="nd-pop__icon" src="<?=$imgPath?>/clock20.svg" alt="" width="20" height="20">
								<div class="nd-pop__text">
									<?$APPLICATION->IncludeComponent("bitrix:main.include", "",
										array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/region_time.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
									);?>
								</div>
							</div>
							<?if($ndRegion('REGION_TAG_LINKVIDEO')):?>
								<a class="nd-pop__btn popup_video _border various video_link" href="<?=$REGION_TAG_LINKVIDEO?>">
									<span>Видео офиса</span>
									<img src="<?=$imgPath?>/play.svg" alt="" width="24" height="24">
								</a>
							<?endif;?>
						</div>
					</span>
				<?endif;?>

				<?if(in_array($regionID, $arSkladRegions)):?>
					<!-- Склад -->
					<span class="nd-pop" data-nd-pop="sklad">
						<span class="nd-pop__label" tabindex="0">Склад</span>
						<div class="nd-pop__body" hidden>
							<button class="nd-pop__close" type="button" aria-label="Закрыть">
								<img src="<?=$imgPath?>/close.svg" alt="" width="24" height="24">
							</button>
							<div class="nd-pop__title">Склад</div>
							<div class="nd-pop__row">
								<img class="nd-pop__icon" src="<?=$imgPath?>/pin20.svg" alt="" width="20" height="20">
								<div class="nd-pop__text nd-pop__text--strong">
									<?$APPLICATION->IncludeComponent("bitrix:main.include", "",
										array(
											"AREA_FILE_SHOW" => "file",
											"PATH" => SITE_DIR."include/address_my_sklad.php",
											"EDIT_TEMPLATE" => "include_area.php"
										)
									);?>
								</div>
							</div>
							<div class="nd-pop__row">
								<img class="nd-pop__icon" src="<?=$imgPath?>/clock20.svg" alt="" width="20" height="20">
								<div class="nd-pop__text">
									Пн&nbsp;— Пт: с 9:00 до 18:00<br>
									(перерыв с 13:00 до 14:00)<br>
									Сб&nbsp;— Вс: выходные дни
								</div>
							</div>
						</div>
					</span>
				<?endif;?>
			</div>

			<div class="nd-header__contacts">
				<?// Каждая кнопка открывает свою веб-форму (MAX / TELEGRAM / WHATSAPP) — как в боевом
				// header_8.php. Там заголовок окна брался из текста ссылки, а она подписана
				// «Написать в MAX»; в макете подпись короткая, поэтому заголовок задаём
				// отдельным атрибутом data-nd-form-title (его читает js/newdesign-header.js).?>
				<a class="nd-contact nd-contact--max" data-event="jqm" data-param-form_id="MAX" data-name="spbuttonMAXheaderNewDesign" data-nd-form-title="Написать в MAX">
					<img src="<?=$imgPath?>/max.svg" alt="" width="16" height="16"><span>MAX</span>
				</a>
				<a class="nd-contact nd-contact--tg" data-event="jqm" data-param-form_id="TELEGRAM" data-name="spbuttonTELEGRAMheaderNewDesign" data-nd-form-title="Написать в Telegram">
					<img src="<?=$imgPath?>/telegram.svg" alt="" width="16" height="16"><span>Telegram</span>
				</a>
				<a class="nd-contact nd-contact--wa" data-event="jqm" data-param-form_id="WHATSAPP" data-name="spbuttonWHATSAPPheaderNewDesign" data-nd-form-title="Написать в WhatsApp">
					<img src="<?=$imgPath?>/whatsapp.svg" alt="" width="16" height="16"><span>WhatsApp</span>
				</a>

				<?
				// Основной телефон. Для платного трафика — номер подмены, если он задан.
				$bMainPodmena = ($bPodmena && $ndRegion('REGION_TAG_PHONE_PODMENA'));
				$mainPhoneValue = $bMainPodmena
					? $ndRegion('REGION_TAG_PHONE_PODMENA')
					: $ndRegion('REGION_TAG_PHONE');
				?>
				<?if($mainPhoneValue):?>
					<a class="nd-contact nd-contact--phone" rel="nofollow" href="<?=$ndTelHref($mainPhoneValue)?>">
						<img src="<?=$imgPath?>/phone.svg" alt="" width="16" height="16">
						<span><?=($bMainPodmena ? $REGION_TAG_PHONE_PODMENA : $REGION_TAG_PHONE)?></span>
					</a>
				<?endif;?>

				<?/* При подмене показываем ТОЛЬКО подменный номер: второй (складской)
				   рядом с ним сводил подмену на нет — звонили по настоящему, и звонок
				   не засчитывался рекламному источнику (Ирина, 4 сентября 2026). */?>
				<?if(!$bMainPodmena && $ndRegion('REGION_TAG_PHONESKLAD')):?>
					<a class="nd-contact nd-contact--phone" rel="nofollow" href="<?=$ndTelHref($ndRegion('REGION_TAG_PHONESKLAD'))?>">
						<img src="<?=$imgPath?>/phone.svg" alt="" width="16" height="16">
						<span><?=$REGION_TAG_PHONESKLAD?></span>
					</a>
				<?endif;?>

				<?
				/* Почта — при любой метке (ndIsUtmVisit из local/init.php), телефон выше —
				   только для источников колл-трекинга. Разные правила намеренно. */
				$bMailPodmena = (ndIsUtmVisit() && $ndRegion('REGION_TAG_EMAIL_PODMENA'));
				$mailValue = $bMailPodmena
					? $ndRegion('REGION_TAG_EMAIL_PODMENA')
					: $ndRegion('REGION_TAG_MAIL');
				?>
				<?if($mailValue):?>
					<a class="nd-contact nd-contact--mail" href="mailto:<?=htmlspecialcharsbx($mailValue)?>">
						<img src="<?=$imgPath?>/mail.svg" alt="" width="16" height="16">
						<span><?=($bMailPodmena ? $REGION_TAG_EMAIL_PODMENA : $REGION_TAG_MAIL)?></span>
					</a>
				<?endif;?>
			</div>
		</div>
		<!-- /Верхняя строка -->

		<!-- Основная строка -->
		<div class="nd-header__main">

			<a class="nd-logo" href="/" title="Латитудо">
				<img class="nd-logo__mark" src="<?=$imgPath?>/logo-mark.svg" alt="Латитудо" width="50" height="49">
				<span class="nd-logo__text">
					<img class="nd-logo__word" src="<?=$imgPath?>/logo-word.svg" alt="LATITUDO" width="165" height="25">
					<span class="nd-logo__slogan">Террасы Заборы Фасады</span>
				</span>
			</a>

			<a class="nd-btn nd-btn--dark nd-btn--catalog" href="/catalog/" id="nd-catalog-btn">
				<img class="nd-btn__icon" src="<?=$imgPath?>/catalog.svg" alt="" width="24" height="24">
				<span>Каталог</span>
			</a>

			<?foreach($arMainNav as $arItem):?>
				<a class="nd-btn nd-btn--muted" href="<?=$arItem['LINK']?>"><span><?=$arItem['TEXT']?></span></a>
			<?endforeach;?>

			<?$APPLICATION->IncludeComponent(
				"bitrix:search.title",
				"newdesign",
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
					"INPUT_ID" => "nd-title-search-input",
					"CONTAINER_ID" => "nd-title-search",
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

			<div class="nd-header__actions">
				<?if($bShowBasket):?>
					<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('nd-header-basket');?>
					<!-- noindex -->
					<?// Классы basket-link + basket и вложенный .count — не украшательство:
					// по этим селекторам скрипт темы (onCompleteAction → loadBasket
					// в js/main.js) обновляет счётчик после добавления товара, без
					// перезагрузки страницы. Бейдж выводим всегда, пустой прячет
					// класс empted — его тот же скрипт снимает и вешает сам.?>
					<a class="nd-cart basket-link basket<?=($basketCount ? ' basket-count' : '')?>" rel="nofollow" href="<?=$basketUrl?>" title="Корзина">
						<img src="<?=$imgPath?>/cart.svg" alt="Корзина" width="24" height="24">
						<span class="nd-cart__count count<?=($basketCount ? '' : ' empted')?>"><?=$basketCount?></span>
					</a>
					<!-- /noindex -->
					<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('nd-header-basket');?>
				<?endif;?>

				<?// Разметка кнопки повторяет боевую из header_8.php вплоть до классов
				// и того, что подпись лежит прямо внутри: скрипт темы берёт заголовок
				// всплывающей формы из текста триггера ($(hash.t).text() в onLoadjqm),
				// а привязку строит по набору атрибутов элемента. Обёртка подписи
				// в лишний span и другой набор классов ломают подстановку —
				// в окне вместо «Оставить заявку» появляется «Общая форма».?>
				<span class="nd-btn nd-btn--red callback-block animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="question">Оставить заявку</span>
			</div>
		</div>
		<!-- /Основная строка -->

		<!-- Нижняя строка меню -->
		<nav class="nd-header__menu">
			<?$APPLICATION->IncludeComponent(
				"bitrix:menu",
				"left_menu_header_newdesign",
				array_merge($arBottomMenuParams, array(
					"COMPONENT_TEMPLATE" => "left_menu_header_newdesign",
					"ROOT_MENU_TYPE"     => "left_menu_header_newdesign",
				)),
				false, array("HIDE_ICONS" => "Y")
			);?>
			<?$APPLICATION->IncludeComponent(
				"bitrix:menu",
				"right_menu_header_newdesign",
				array_merge($arBottomMenuParams, array(
					"COMPONENT_TEMPLATE" => "right_menu_header_newdesign",
					"ROOT_MENU_TYPE"     => "right_menu_header_newdesign",
				)),
				false, array("HIDE_ICONS" => "Y")
			);?>
		</nav>
		<!-- /Нижняя строка меню -->

	</div>
</div>

<!-- Выпадающий каталог -->
<div class="nd-catalog-drop" id="nd-catalog-drop">
	<?// Источник данных тот же, что у боевого меню (include/menu/menu.top.php):
	// тип top_content_multilevel, дети раздела /catalog/ приходят из
	// catalog/.top_menu_new.menu_ext.php. Так список разделов и подразделов
	// совпадает с боевым, включая галку «показывать в меню».?>
	<?$APPLICATION->IncludeComponent(
		"bitrix:menu",
		"catalog_wide_newdesign",
		array(
			"ALLOW_MULTI_SELECT"    => "N",
			"CHILD_MENU_TYPE"       => "top_menu_new",
			"COMPONENT_TEMPLATE"    => "catalog_wide_newdesign",
			"COUNT_ITEM"            => "6",
			"DELAY"                 => "N",
			"MAX_LEVEL"             => "4",
			// Кэш меню включён (на боевом "N"): дерево каталога собирается
			// запросами к инфоблоку, и на каждый хит это лишняя работа.
			// Сбрасывается по тегам инфоблока при правке разделов.
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
</div>

<?// Остальные выпадающие панели («Услуги», «Партнерам», «Портфолио»,
// «Производители»). Привязываются к пунктам шапки по адресу ссылки.?>
<?include(__DIR__.'/header_drops_newdesign.php');?>

<div class="nd-catalog-overlay" id="nd-catalog-overlay"></div>

<script src="<?=SITE_TEMPLATE_PATH?>/js/newdesign-header.js?<?=@filemtime($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/newdesign-header.js')?>"></script>
