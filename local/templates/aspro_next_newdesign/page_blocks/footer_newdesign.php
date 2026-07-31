<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Подвал нового дизайна. Макет Figma — узлы 20463:355744 (десктоп)
 * и 20497:93041 (мобильный).
 *
 * Копия рабочего подвала старого дизайна: page_blocks/footer_1.php →
 * include/footer/f-desk.php + include/footer/footer_block/footer_6_col.php
 * (мобильный — include/footer/f-mobile.php) + include/region_info_new_footer.php.
 * Оттуда без изменений перенесены: логика регионов (адрес, режим работы,
 * телефоны с подменой по utm-метке, «Заказать пропуск»), формы мессенджеров
 * и ссылки соцсетей.
 *
 * Подключается напрямую из footer.php шаблона, а не через
 * CNext::ShowPageType('footer'): тот выбирает файл по настройке темы
 * FOOTER_TYPE, а она одна на весь сайт — как HEADER_TYPE и INDEX_TYPE.
 *
 * По просьбе Ирины (2026-07-31) в новом подвале не выводим: e-mail и рейтинг
 * Яндекса, «Скачать брошюру», курс ЦБ РФ и два юридических абзаца — в макете
 * их нет. В старом дизайне всё осталось на месте.
 */
global $arRegion, $arTheme, $APPLICATION;

$sImg = SITE_TEMPLATE_PATH.'/images/newdesign/footer/';
$sTpl = SITE_TEMPLATE_PATH;
$regionID = ($arRegion ? $arRegion['ID'] : '');

// Регионы со своим офисом — у них адрес и режим работы приезжают из инфоблока
// регионов, у остальных показываем название региона и общий график.
$arOfficeRegions = array(9277, 9278, 9568, 10039, 22018);
$bOffice = in_array($regionID, $arOfficeRegions);

// Теги региона подменяются на значения свойств уже в буфере страницы.
$REGION_TAG_ADDRESSMY      = '#REGION_TAG_ADDRESSMY#';
$REGION_TAG_TIME           = '#REGION_TAG_TIME#';
$REGION_TAG_PHONE          = '#REGION_TAG_PHONE#';
$REGION_TAG_PHONE_8800     = '#REGION_TAG_PHONE_8800#';
$REGION_TAG_PHONE_MOBILE   = '#REGION_TAG_PHONE_MOBILE#';
$REGION_TAG_PHONE_PODMENA  = '#REGION_TAG_PHONE_PODMENA#';
$REGION_TAG_PHONESKLAD     = '#REGION_TAG_PHONESKLAD#';
$REGION_TAG_PHONE3         = '#REGION_TAG_PHONE3#';
$REGION_TAG_SEO_OBLAST_PP  = '#REGION_TAG_SEO_OBLAST_PP#';

// Подмена телефона по utm-метке — перенесено из region_info_new_footer.php.
$utm_source = 'empty';
if(isset($_SESSION['UTM']['utm_source']) && $_SESSION['UTM']['utm_source'])
	$utm_source = $_SESSION['UTM']['utm_source'];
$bUtmPodmena = false;
foreach(array('ya', 'tg', 'vk', 'maps') as $sMark)
{
	if(strpos($utm_source, $sMark) !== false)
	{
		$bUtmPodmena = true;
		break;
	}
}

if(!function_exists('ndFooterPhoneHref'))
{
	function ndFooterPhoneHref($sPhone)
	{
		return 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $sPhone);
	}
}

// Колонки меню: тип меню, заголовок, ссылка заголовка и ссылка «Смотреть все».
$arMenuColumns = array(
	array('TYPE' => 'footer_company_newdesign',   'TITLE' => 'О компании', 'URL' => '/info/company/', 'ALL' => '/info/company/'),
	array('TYPE' => 'footer_catalog_newdesign',   'TITLE' => 'Каталог',    'URL' => '/catalog/',      'ALL' => '/catalog/'),
	array('TYPE' => 'footer_projects_newdesign',  'TITLE' => 'Проекты',    'URL' => '/projects/',     'ALL' => '/projects/'),
	array('TYPE' => 'footer_materials_newdesign', 'TITLE' => 'Материалы',  'URL' => '/materials/',    'ALL' => '/materials/'),
	array('TYPE' => 'footer_clients_newdesign',   'TITLE' => 'Клиентам',   'URL' => '',               'ALL' => ''),
	array('TYPE' => 'footer_services_newdesign',  'TITLE' => 'Услуги',     'URL' => '/services/',     'ALL' => '/services/'),
);
// Раскладка по колонкам десктопа: третья колонка держит «Проекты» и «Материалы».
$arMenuLayout = array(array(0), array(1), array(2, 3), array(4), array(5));

if(!function_exists('ndFooterMenuColumn'))
{
	// $bOpen — на мобильном колонки складываются в аккордеон, и первая по макету
	// раскрыта. Класс ставим сразу в разметке, чтобы не мигало до загрузки скрипта.
	function ndFooterMenuColumn($arColumn, $bOpen = false)
	{
		global $APPLICATION;
		?>
		<div class="nd-fmenu__group<?=($bOpen ? ' is-open' : '')?>" data-nd-acc>
			<div class="nd-fmenu__head" data-nd-acc-head>
				<?if($arColumn['URL']):?>
					<a class="nd-fmenu__title" href="<?=$arColumn['URL']?>"><?=$arColumn['TITLE']?></a>
				<?else:?>
					<span class="nd-fmenu__title"><?=$arColumn['TITLE']?></span>
				<?endif;?>
				<span class="nd-fmenu__chev" aria-hidden="true"></span>
			</div>
			<div class="nd-fmenu__body" data-nd-acc-body>
				<?$APPLICATION->IncludeComponent(
					"bitrix:menu",
					"footer_newdesign",
					array(
						"ROOT_MENU_TYPE" => $arColumn['TYPE'],
						"MENU_CACHE_TYPE" => "N",
						"MENU_CACHE_TIME" => "172800",
						"MENU_CACHE_USE_GROUPS" => "N",
						"MENU_CACHE_GET_VARS" => array(),
						"CACHE_SELECTED_ITEMS" => "N",
						"MAX_LEVEL" => "1",
						"CHILD_MENU_TYPE" => "left",
						"USE_EXT" => "N",
						"DELAY" => "N",
						"ALLOW_MULTI_SELECT" => "Y",
					),
					false
				);?>
				<?if($arColumn['ALL']):?>
					<a class="nd-fmenu__all" href="<?=$arColumn['ALL']?>">Смотреть все</a>
				<?endif;?>
			</div>
		</div>
		<?
	}
}
?>
<link rel="stylesheet" href="<?=$sTpl?>/css/newdesign-footer.css?<?=@filemtime($_SERVER['DOCUMENT_ROOT'].$sTpl.'/css/newdesign-footer.css')?>">

<div class="nd-footer">

	<?// ===== Промо-блок «Вам есть на что посмотреть» ===== ?>
	<section class="nd-fpromo">
		<div class="nd-fpromo__bg">
			<div class="nd-fpromo__inner">
				<div class="nd-fpromo__media">
					<?// Десктоп: то же видео звонка, что и в старом подвале, но без рамки-айфона —
					   // в макете это просто скруглённый прямоугольник. ?>
					<div class="nd-fpromo__phone">
						<video class="lazy-video" playsinline autoplay loop muted poster="<?=$sImg?>promo-mobile.jpg">
							<source data-src="/files/video/videozvonok.mp4" type="video/mp4">
						</video>
						<?// Кнопки звонка вырезаны из накладки старого дизайна
						   // /images/icons/iPhone_12_icons.png — в ней они лежали поверх
						   // видео вместе с рамкой айфона. Рамка в макете не нужна,
						   // кнопки остались. ?>
						<div class="nd-fpromo__calls" aria-hidden="true">
							<img src="<?=$sImg?>call-camera.png" alt="" width="53" height="53" loading="lazy">
							<img src="<?=$sImg?>call-hangup.png" alt="" width="53" height="53" loading="lazy">
							<img src="<?=$sImg?>call-mic.png" alt="" width="53" height="53" loading="lazy">
						</div>
					</div>
					<?// Мобильный: в макете вместо видео фотография. ?>
					<img class="nd-fpromo__photo" src="<?=$sImg?>promo-mobile.jpg" alt="Закажите видеозвонок" loading="lazy">
				</div>
				<div class="nd-fpromo__content">
					<div class="nd-fpromo__title">
						<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array(
							"AREA_FILE_SHOW" => "file",
							"PATH" => SITE_DIR."include/newdesign/footer/promo_title.php",
							"EDIT_TEMPLATE" => "",
						), false);?>
					</div>
					<div class="nd-fpromo__sub">
						<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array(
							"AREA_FILE_SHOW" => "file",
							"PATH" => SITE_DIR."include/newdesign/footer/promo_subtitle.php",
							"EDIT_TEMPLATE" => "",
						), false);?>
					</div>
					<span class="nd-fpromo__btn callback-block animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="topfooter" data-nd-form-title="Заказать видеозвонок">Заказать видеозвонок</span>
					<div class="nd-fpromo__bonus">
						<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array(
							"AREA_FILE_SHOW" => "file",
							"PATH" => SITE_DIR."include/newdesign/footer/promo_bonus.php",
							"EDIT_TEMPLATE" => "",
						), false);?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?// ===== Контакты региона ===== ?>
	<?if($arRegion):?>
	<div class="nd-footer__contacts">
		<div class="nd-footer__inner">
			<div class="nd-fcont">

				<div class="nd-fcont__col nd-fcont__col--addr">
					<?if($bOffice && $arRegion['PROPERTY_REGION_TAG_ADDRESSMY_VALUE']):?>
						<div class="nd-fcont__addr">
							<?=$REGION_TAG_ADDRESSMY?><br>
							<a class="nd-link-accent" href="/contacts/">Схема проезда</a>
						</div>
					<?elseif(!$bOffice):?>
						<div class="nd-fcont__addr"><b><?=($arRegion ? $arRegion['NAME'] : '')?></b></div>
					<?endif;?>

					<div class="nd-fcont__muted">
						<?if($bOffice && $arRegion['PROPERTY_REGION_TAG_TIME_VALUE']):?>
							<?=$REGION_TAG_TIME?>
						<?else:?>
							С понедельника по пятницу: с 9:00 до 18:00<br>Суббота: с 10:00 до 15:00<br>Воскресенье: выходной день
							<?if($arRegion['PROPERTY_REGION_TAG_OBLAST_DP_VALUE']):?>
								<br>Доставка по адресу или на объект в <?=$REGION_TAG_SEO_OBLAST_PP?>
							<?endif;?>
						<?endif;?>
					</div>
				</div>

				<div class="nd-fcont__col nd-fcont__col--phones">
					<div class="nd-fcont__phones">
						<?if($arRegion['PROPERTY_REGION_TAG_USE_NUMBERS_PHONE_VALUE'] == 'Y'):?>
							<?// Резервные номера ?>
							<?if($arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE']):?>
								<div class="nd-fcont__phone"><a rel="nofollow" href="<?=ndFooterPhoneHref($arRegion['PROPERTY_REGION_TAG_PHONE_8800_VALUE'])?>"><?=$REGION_TAG_PHONE_8800?></a></div>
								<div class="nd-fcont__muted">Звонок бесплатный</div>
							<?endif;?>
							<?if($arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE']):?>
								<div class="nd-fcont__phone"><a rel="nofollow" href="<?=ndFooterPhoneHref($arRegion['PROPERTY_REGION_TAG_PHONE_MOBILE_VALUE'])?>"><?=$REGION_TAG_PHONE_MOBILE?></a></div>
							<?endif;?>
						<?elseif($bUtmPodmena && $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
							<?// Пришли с метки — показываем подменный номер ?>
							<div class="nd-fcont__phone"><a rel="nofollow" href="<?=ndFooterPhoneHref($arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE'])?>"><?=$REGION_TAG_PHONE_PODMENA?></a></div>
						<?else:?>
							<div class="nd-fcont__phone"><a rel="nofollow" href="<?=ndFooterPhoneHref($arRegion['PROPERTY_REGION_TAG_PHONE_VALUE'])?>"><?=$REGION_TAG_PHONE?></a></div>
							<?if(!$bOffice):?>
								<div class="nd-fcont__muted">Звонок бесплатный</div>
							<?endif;?>
							<?if($arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE']):?>
								<div class="nd-fcont__phone"><a rel="nofollow" href="<?=ndFooterPhoneHref($arRegion['PROPERTY_REGION_TAG_PHONESKLAD_VALUE'])?>"><?=$REGION_TAG_PHONESKLAD?></a></div>
							<?endif;?>
							<?if($arRegion['PROPERTY_REGION_TAG_PHONE3_VALUE']):?>
								<div class="nd-fcont__phone"><a rel="nofollow" href="<?=ndFooterPhoneHref($arRegion['PROPERTY_REGION_TAG_PHONE3_VALUE'])?>"><?=$REGION_TAG_PHONE3?></a></div>
							<?endif;?>
						<?endif;?>
					</div>

					<?if($regionID == 10039):?>
						<div class="nd-fcont__muted">
							Для пропуска нужен паспорт или права<br>
							<a class="nd-link-accent" data-event="jqm" data-param-form_id="PROPUSK" data-name="spbuttonPROPUSKfooter8Sl64782XDMFy" data-nd-form-title="Заказать пропуск">Заказать пропуск</a>
						</div>
					<?endif;?>
				</div>

				<div class="nd-fcont__col nd-fcont__col--msg">
					<a class="nd-fmsg nd-fmsg--max" data-event="jqm" data-param-form_id="MAX" data-name="spbuttonMAXfooter8Sl64782XDMFy" data-nd-form-title="Написать в MAX">
						<img src="<?=$sImg?>msg-max.svg" alt="" width="16" height="16" loading="lazy">Написать в MAX
					</a>
					<a class="nd-fmsg nd-fmsg--tg" data-event="jqm" data-param-form_id="TELEGRAM" data-name="spbuttonTELEGRAMfooter8Sl64782XDMFy" data-nd-form-title="Написать в Telegram">
						<img src="<?=$sImg?>msg-tg.svg" alt="" width="16" height="16" loading="lazy">Написать в Telegram
					</a>
					<a class="nd-fmsg nd-fmsg--wa" data-event="jqm" data-param-form_id="WHATSAPP" data-name="spbuttonWHATSAPPfooter8Sl64782XDMFy" data-nd-form-title="Написать в WhatsApp">
						<img src="<?=$sImg?>msg-wa.svg" alt="" width="16" height="16" loading="lazy">Написать в WhatsApp
					</a>
				</div>

				<div class="nd-fcont__col nd-fcont__col--soc">
					<?$APPLICATION->IncludeComponent("aspro:social.info.next", "footer_newdesign", array(
						"CACHE_TYPE" => "A",
						"CACHE_TIME" => "172800",
						"CACHE_GROUPS" => "N",
						"TITLE_BLOCK" => "",
					), false);?>
				</div>

			</div>

			<div class="nd-footer__wordmark">
				<img src="<?=$sImg?>latitudo-watermark.svg" alt="" width="1336" height="205" loading="lazy">
			</div>
		</div>
	</div>
	<?endif;?>

	<?// ===== Меню ===== ?>
	<div class="nd-footer__menu">
		<div class="nd-footer__inner">
			<nav class="nd-fmenu">
				<?foreach($arMenuLayout as $arGroupIndexes):?>
					<div class="nd-fmenu__col">
						<?foreach($arGroupIndexes as $iIndex):?>
							<?ndFooterMenuColumn($arMenuColumns[$iIndex], $iIndex === 0);?>
						<?endforeach;?>
					</div>
				<?endforeach;?>
			</nav>
		</div>
	</div>

	<?// ===== Нижняя строка ===== ?>
	<div class="nd-footer__bottom">
		<div class="nd-footer__inner">
			<div class="nd-fbot">
				<div class="nd-fbot__left">
					<div class="nd-fbot__copy">2017-<?=date('Y')?> Latitudo. Все права защищены</div>
					<div class="nd-fbot__links">
						<a href="/karta-sayta/">Карта сайта</a>
						<a href="/info/licenses_detail/">Политика конфиденциальности</a>
					</div>
				</div>
				<div class="nd-fbot__pay">
					<span class="nd-fpay"><img src="<?=$sImg?>pay-mastercard.svg" alt="Mastercard" width="50" height="36" loading="lazy"></span>
					<span class="nd-fpay"><img src="<?=$sImg?>pay-visa.svg" alt="Visa" width="50" height="36" loading="lazy"></span>
					<span class="nd-fpay"><img src="<?=$sImg?>pay-mir.svg" alt="МИР" width="50" height="36" loading="lazy"></span>
					<span class="nd-fpay"><img src="<?=$sImg?>pay-jcb.svg" alt="JCB" width="50" height="36" loading="lazy"></span>
					<span class="nd-fpay"><img src="<?=$sImg?>pay-unionpay.svg" alt="UnionPay" width="50" height="36" loading="lazy"></span>
					<span class="nd-fpay nd-fpay--text">Наличный и безналичный расчет</span>
				</div>
			</div>
		</div>
	</div>

</div>
<script src="<?=$sTpl?>/js/newdesign-footer.js?<?=@filemtime($_SERVER['DOCUMENT_ROOT'].$sTpl.'/js/newdesign-footer.js')?>" defer></script>
