<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Контакты региона (Москва, Воронеж, Белгород, Краснодар, Ростов) в новом дизайне.
 *
 * Размеры из макета Figma («Чистовик», фрейм «Контакты» 20493:77152):
 * галерея — три кадра 441×294 с зазором 6, радиус 4, рамка rgba(14,17,23,.12);
 * под ней ряд из трёх плашек 441 (поля 24, радиус 4, заливка rgba(82,82,100,.05)):
 * адрес офиса, телефоны с почтой, режим работы с кнопкой «Заказать пропуск»;
 * ниже ряд склада — широкая плашка с адресом и узкая с режимом работы;
 * в конце карта и кнопки «Схема проезда».
 *
 * Склад есть не у всех регионов (в Ростове нет) — весь его ряд и кнопка
 * появляются только при заполненных ADDRESS_SKLAD / SCHEDULE_SKLAD.
 *
 * Карты берём из отдельных свойств YMAP_CONSTR_OFFICE и YMAP_CONSTR_SKLAD
 * (готовый <iframe> из конструктора Яндекса) — на каждую своя кнопка-
 * переключатель. Если они не заполнены, работает старое общее YMAP_CONSTR.
 *
 * Кнопка «Заказать пропуск» — только у московского офиса.
 *
 * Подмена телефона и почты по utm-метке перенесена из старого шаблона
 * contacts_org2. Телефон подменный лежит у самого офиса (свойство
 * PHONE_PODMENA), а почта — у РЕГИОНА: печатаем тег
 * #REGION_TAG_EMAIL_PODMENA#, его заменяет обработчик из
 * bitrix/php_interface/init.php. Так же сделано в шапке
 * (page_blocks/header_newdesign.php) и на общей странице контактов.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
	return;
}

$ndIco = SITE_TEMPLATE_PATH.'/images/newdesign/contacts/';

$ndUtmSource = !empty($_SESSION['UTM']['utm_source']) ? $_SESSION['UTM']['utm_source'] : 'empty';
$ndIsAdSource = (
	str_contains($ndUtmSource, 'ya') || str_contains($ndUtmSource, 'tg')
	|| str_contains($ndUtmSource, 'vk') || str_contains($ndUtmSource, 'maps')
);
$ndTel = static function ($phone) {
	return 'tel:'.preg_replace('/[^0-9+]/', '', $phone);
};

/* Почта у платного трафика — подменная почта РЕГИОНА, а не адрес офиса:
   у элемента контактов своего подменного адреса нет (в инфоблоке есть только
   PHONE_PODMENA). Раньше на странице региона почта не подменялась вовсе —
   в шапке стояла подменная, а в карточке офиса настоящая (Ирина, 4 сентября 2026). */
global $arRegion;
$ndMailPodmena = (ndIsUtmVisit() && !empty($arRegion['PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE']))
	? '#REGION_TAG_EMAIL_PODMENA#'
	: '';
?>
<? foreach ($arResult['ITEMS'] as $arItem): ?>
	<?
	$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

	$props = $arItem['DISPLAY_PROPERTIES'];

	$phones = [];
	if (ndIsUtmVisit() && !empty($props['PHONE_PODMENA']['VALUE'])) {
		$phones[] = $props['PHONE_PODMENA']['VALUE'];
	} elseif (!empty($props['PHONE']['VALUE'])) {
		$phones = (array) $props['PHONE']['VALUE'];
	}

	$city = $arItem['ND_CITY'];
	$ndIsMoscow = (mb_strtolower(trim((string) $city)) === 'москва');
	$hasStore = !empty($props['ADDRESS_SKLAD']) || !empty($props['SCHEDULE_SKLAD']);
	$maps = $arItem['ND_MAPS'];

	/* Данные для микроразметки: абсолютный адрес сайта, первое фото офиса
	   и координаты из свойства MAP («широта,долгота»). */
	$ndHost = 'https://'.$_SERVER['HTTP_HOST'];
	$ndOrgImage = !empty($arItem['ND_GALLERY']) ? $ndHost.reset($arItem['ND_GALLERY']) : '';
	$ndGeo = [];
	$ndMapValue = trim((string) ($arItem['PROPERTIES']['MAP']['VALUE'] ?? ''));
	if (preg_match('/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/', $ndMapValue, $m)) {
		$ndGeo = [$m[1], $m[2]];
	}
	?>
	<?/* Заголовок ставит шаблон, а не страница: /contacts/index.php делает
	     SetTitle(''), потому что имя зависит от региона. Берём SEO-заголовок
	     элемента, если он заполнен, иначе название — как в старом шаблоне. */?>
	<? $ndTitle = $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] ?: $arItem['NAME']; ?>
	<?/* Обёртка maxwidth-theme обязательна: у /contacts/ в .section.php стоит
	     WIDE_PAGE=Y, и при нём тема не выводит контейнер .wrapper_inner —
	     без неё на мобильном контент уезжает за край экрана. */?>
	<div class="maxwidth-theme">
	<?/* Микроразметка организации. Имя — «Латитудо», а не заголовок страницы:
	     раньше в name уходило «Контакты Латитудо в Москве», и поисковик считал
	     это названием компании. Адрес разложен на PostalAddress, координаты и
	     режим работы отданы отдельно — так требуют Google и Яндекс от карточки
	     организации (проверка микроразметки, 4 сентября 2026). */?>
	<div class="nd-region" id="<?= $this->GetEditAreaId($arItem['ID']) ?>" itemscope itemtype="https://schema.org/LocalBusiness">
		<h1 id="pagetitle" class="nd-region__h1"><?= $ndTitle ?></h1>
		<meta itemprop="name" content="Латитудо">
		<meta itemprop="description" content="<?= htmlspecialcharsbx($ndTitle) ?>">
		<link itemprop="url" href="<?= $ndHost.$APPLICATION->GetCurPage(false) ?>">
		<link itemprop="logo" href="<?= $ndHost ?>/images/company/logo.svg">
		<? if ($ndOrgImage): ?><link itemprop="image" href="<?= $ndOrgImage ?>"><? endif; ?>
		<? if ($ndGeo): ?>
			<span itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">
				<meta itemprop="latitude" content="<?= $ndGeo[0] ?>">
				<meta itemprop="longitude" content="<?= $ndGeo[1] ?>">
			</span>
		<? endif; ?>

		<? if ($arItem['ND_GALLERY']): ?>
			<div class="nd-region__gallery" data-nd-gallery>
				<div class="nd-region__shots" data-nd-gallery-track>
					<? foreach ($arItem['ND_GALLERY'] as $src): ?>
						<a class="nd-region__shot popup_video various" href="<?= $src ?>" data-fancybox="office-<?= $arItem['ID'] ?>">
							<img src="<?= $src ?>" alt="<?= $arItem['NAME'] ?>" loading="lazy">
						</a>
					<? endforeach; ?>
				</div>
				<button type="button" class="nd-region__gnav nd-region__gnav--prev" data-nd-gallery-prev aria-label="Предыдущие фото">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<button type="button" class="nd-region__gnav nd-region__gnav--next" data-nd-gallery-next aria-label="Следующие фото">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
			</div>
		<? endif; ?>

		<div class="nd-region__row nd-region__row--three">
			<div class="nd-region__card">
				<div class="nd-region__label">
					<img src="<?= $ndIco ?>pin.svg" alt="" width="20" height="20">Адрес офиса
				</div>
				<div class="nd-region__text" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
					<meta itemprop="addressCountry" content="RU">
					<? if ($city): ?><meta itemprop="addressLocality" content="<?= htmlspecialcharsbx($city) ?>"><? endif; ?>
					<span itemprop="streetAddress"><?= html_entity_decode($arItem['PROPERTIES']['ADDRESS']['VALUE'] ?? '') ?></span>
				</div>
			</div>

			<div class="nd-region__card">
				<? if ($phones): ?>
					<div class="nd-region__label">
						<img src="<?= $ndIco ?>phone.svg" alt="" width="20" height="20">Телефоны для связи
					</div>
					<div class="nd-region__links">
						<? foreach ($phones as $phone): ?>
							<a itemprop="telephone" rel="nofollow" href="<?= $ndTel($phone) ?>"><?= $phone ?></a>
						<? endforeach; ?>
					</div>
				<? endif; ?>
				<? if (!empty($props['EMAIL'])): ?>
					<div class="nd-region__label nd-region__label--second">
						<img src="<?= $ndIco ?>mail.svg" alt="" width="20" height="20">Электронная почта
					</div>
					<div class="nd-region__links">
						<? $ndMail = $ndMailPodmena ?: $props['EMAIL']['DISPLAY_VALUE']; ?>
						<a itemprop="email" href="mailto:<?= $ndMail ?>"><?= $ndMail ?></a>
					</div>
				<? endif; ?>
			</div>

			<div class="nd-region__card">
				<? if (!empty($props['SCHEDULE'])): ?>
					<div class="nd-region__label">
						<img src="<?= $ndIco ?>clock.svg" alt="" width="20" height="20">Режим работы офиса
					</div>
					<? $ndHours = ndSchemaOpeningHours($props['SCHEDULE']['VALUE']['TEXT']); ?>
					<? if ($ndHours): ?><meta itemprop="openingHours" content="<?= $ndHours ?>"><? endif; ?>
					<div class="nd-region__text"><?= htmlspecialcharsBack($props['SCHEDULE']['VALUE']['TEXT']) ?></div>
				<? endif; ?>
				<?/* Пропуск нужен только в московский офис (бизнес-парк «Румянцево»),
				     в остальных регионах кнопки быть не должно.

				     Регион берём по названию города из связанного элемента ИБ 7
				     (result_modifier, ND_CITY): символьных кодов у регионов нет,
				     сравнивать больше не по чему. */?>
				<? if ($ndIsMoscow): ?>
					<?/* Кнопка вызывает ту же форму PROPUSK, что и в старом шаблоне.
					     data-nd-form-title — чтобы тема подставила верный заголовок (см. newdesign-header.js) */?>
					<a class="nd-region__btn animate-load" data-event="jqm" data-param-form_id="PROPUSK"
					   data-name="spbuttonPROPUSKcontact<?= $arItem['ID'] ?>" data-nd-form-title="Заказать пропуск">Заказать пропуск</a>
					<div class="nd-region__note">Для пропуска нужен паспорт или права</div>
				<? endif; ?>
			</div>
		</div>

		<? if ($hasStore): ?>
			<div class="nd-region__row nd-region__row--store">
				<div class="nd-region__card">
					<div class="nd-region__label">
						<img src="<?= $ndIco ?>pin.svg" alt="" width="20" height="20">Адрес склада
					</div>
					<div class="nd-region__text"><?= $props['ADDRESS_SKLAD']['DISPLAY_VALUE'] ?? '' ?></div>
				</div>
				<? if (!empty($props['SCHEDULE_SKLAD'])): ?>
					<div class="nd-region__card">
						<div class="nd-region__label">
							<img src="<?= $ndIco ?>clock.svg" alt="" width="20" height="20">Режим работы склада
						</div>
						<div class="nd-region__text"><?= htmlspecialcharsBack($props['SCHEDULE_SKLAD']['VALUE']['TEXT']) ?></div>
					</div>
				<? endif; ?>
			</div>
		<? endif; ?>

		<? if ($maps): ?>
			<?
			// Подписи кнопок: тип карты приходит из result_modifier — офис
			// и склад лежат в своих свойствах, а не берутся по порядку.
			$mapTitles = [];
			foreach ($maps as $map) {
				$what = (($map['TYPE'] ?? '') === 'store') ? 'складу' : 'офису';
				$mapTitles[] = 'Схема проезда к '.$what.($city ? ' г. '.$city : '');
			}
			?>
			<div class="nd-region__maps" data-nd-maps>
				<? foreach ($maps as $i => $map): ?>
					<div class="nd-region__map<?= $i ? '' : ' is-active' ?>" data-nd-map="<?= $i ?>">
						<?= str_replace(['width=', 'height='], ['data-width=', 'data-height='], $map['HTML']) ?>
					</div>
				<? endforeach; ?>

				<div class="nd-region__mapnav">
					<? foreach ($mapTitles as $i => $title): ?>
						<button type="button" class="nd-region__mapbtn<?= $i ? '' : ' is-active' ?>" data-nd-map-btn="<?= $i ?>"><?= htmlspecialcharsbx($title) ?></button>
					<? endforeach; ?>
				</div>
			</div>
		<? endif; ?>
	</div>
	</div>
<? endforeach; ?>

<?/* Тизеры-преимущества под контактами (макет 20560:104625 / 20560:104712).
   Блок общий с общей страницей контактов — лежит в page_blocks шаблона. */?>
<?include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/page_blocks/contacts_teasers_newdesign.php';?>
