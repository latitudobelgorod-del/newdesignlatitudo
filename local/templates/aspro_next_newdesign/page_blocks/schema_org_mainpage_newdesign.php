<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/**
 * Микроразметка компании и сайта — только для главной страницы.
 *
 * На главной не было никакой разметки: поисковики не знали ни названия
 * организации, ни телефона, ни адреса, ни соцсетей (проверка микроразметки,
 * Ирина, 4 сентября 2026). Google собирает по этим данным карточку знаний,
 * Яндекс — карточку организации.
 *
 * Данные берём у ТЕКУЩЕГО региона (инфоблок 7): на поддомене Белгорода в
 * разметке должны быть белгородские телефон, адрес и почта, а не московские.
 *
 * Печатаем одним графом: Organization с постоянным идентификатором
 * (<адрес сайта>/#organization) и WebSite, который на него ссылается.
 * Тогда поисковик понимает, что сайт и компания — одно целое.
 *
 * Ссылки на соцсети правятся здесь: в теме они лежат картинками в подвале,
 * отдельного поля с адресами нет.
 */

global $arRegion;

$ndSchemaHost = 'https://'.$_SERVER['HTTP_HOST'];

/** Значение тега региона: свойства инфоблока 7 хранят html-мнемоники. */
$ndSchemaRegion = static function ($code) use ($arRegion) {
	$value = $arRegion['PROPERTY_'.$code.'_VALUE'] ?? '';
	if (is_array($value)) {
		$value = $value['TEXT'] ?? '';
	}

	return trim(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));
};

$ndSchemaPhones = array_values(array_filter(array(
	$ndSchemaRegion('REGION_TAG_PHONE'),
	$ndSchemaRegion('REGION_TAG_PHONE_8800'),
)));

$ndSchemaOrg = array(
	'@type' => 'Organization',
	'@id' => $ndSchemaHost.'/#organization',
	'name' => 'Латитудо',
	'url' => $ndSchemaHost.'/',
	'logo' => $ndSchemaHost.'/images/company/logo.svg',
	'image' => $ndSchemaHost.'/images/company/logo.svg',
	'description' => 'Производство и продажа изделий из древесно-полимерного композита: '
		.'террасная доска, фасадные панели, ограждения, садовая мебель.',
	'sameAs' => array(
		'https://vk.com/latitudo',
		'https://t.me/latitudo_ru',
		'https://www.youtube.com/channel/UCRgn9WlVgrp3W2hRxEw6AwQ',
		'https://rutube.ru/channel/41631334/',
	),
);

if ($ndSchemaPhones) {
	$ndSchemaOrg['telephone'] = (count($ndSchemaPhones) === 1) ? $ndSchemaPhones[0] : $ndSchemaPhones;
}

if ($mail = $ndSchemaRegion('REGION_TAG_MAIL')) {
	$ndSchemaOrg['email'] = $mail;
}

if ($address = $ndSchemaRegion('REGION_TAG_ADDRESSMY')) {
	$ndSchemaOrg['address'] = array(
		'@type' => 'PostalAddress',
		'addressCountry' => 'RU',
		'streetAddress' => $address,
	);
	if (!empty($arRegion['NAME'])) {
		$ndSchemaOrg['address']['addressLocality'] = $arRegion['NAME'];
	}
}

/* Режим работы приводит к машинному виду ndSchemaOpeningHours (local/init.php):
   не разобрал фразу — свойства просто не будет.

   openingHours спецификацией определён не у Organization, а у LocalBusiness
   (валидатор Яндекса на это ругается предупреждением). LocalBusiness —
   потомок Organization, поэтому ссылка publisher у WebSite остаётся верной,
   а часы работы становятся законным полем. Тип повышаем только вместе с
   адресом: LocalBusiness без адреса поисковику бесполезен. */
if ($hours = ndSchemaOpeningHours($ndSchemaRegion('REGION_TAG_TIME'))) {
	if (isset($ndSchemaOrg['address'])) {
		$ndSchemaOrg['@type'] = 'LocalBusiness';
		$ndSchemaOrg['openingHours'] = $hours;
	}
}

$ndSchemaGraph = array(
	'@context' => 'https://schema.org',
	'@graph' => array(
		$ndSchemaOrg,
		array(
			'@type' => 'WebSite',
			'@id' => $ndSchemaHost.'/#website',
			'name' => 'Латитудо',
			'url' => $ndSchemaHost.'/',
			'inLanguage' => 'ru-RU',
			'publisher' => array('@id' => $ndSchemaHost.'/#organization'),
		),
	),
);
?>
<script type="application/ld+json"><?=\Bitrix\Main\Web\Json::encode($ndSchemaGraph)?></script>
