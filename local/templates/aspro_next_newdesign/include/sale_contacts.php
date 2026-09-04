<?php
/* Телефоны и адреса под списком товаров на детальной акции — новый дизайн.

   Раньше этот список контент-менеджер добавлял руками в блок редактора каждой
   акции. Теперь он печатается шаблоном, поэтому одинаков на всех акциях и
   правится в одном месте.

   ВАЖНО: у акций, где список уже стоит в блоках редактора, он какое-то время
   будет виден дважды — из редактора его нужно убрать вручную.

   Файл лежит в шаблоне, а не в /include: за пределами /local ничего не
   версионируется, а этот код должен уезжать на прод и на вторую машину вместе
   с шаблоном (WORKFLOW.md).

   Подмена телефонов (Ирина, 4 сентября 2026). У рекламного трафика каждая
   строка показывает подменный номер СВОЕГО города, а не только того региона, в
   котором сейчас посетитель. Номер берём прямо у офиса (свойство PHONE_PODMENA
   инфоблока контактов) по его ID — а не заменой по тексту, как это делает общий
   обработчик ndOfficePhonePodmena. Причина: у белгородского офиса в свойстве
   PHONE сейчас стоит воронежский номер, и заменять «по совпадению» его строку
   не с чем. Привязка к ID от этой ошибки в данных не зависит. */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/* Порядок и адреса — как в исходном списке. ID офисов те же, что у карусели
   шоу-румов на «О компании» (include/company/about_page.php). */
$ndSaleOffices = array(
	array('id' => 4764,  'phone' => '+7 (473) 22-99-800', 'text' => 'г. Воронеж, ул. Летчика Колесниченко, дом 67, помещение номер 1/16'),
	array('id' => 79,    'phone' => '+7 (4722) 407-337',  'text' => 'г. Белгород, ул. Есенина, 9Б'),
	array('id' => 22068, 'phone' => '+7 (495) 135-93-05', 'text' => 'г. Москва, Киевское шоссе 22-й км, Бизнес парк Румянцево, корп. Г, этаж 6, офис 635Г (подъезды 17 и 18)'),
	array('id' => 10053, 'phone' => '+7 (861) 290-07-17', 'text' => 'г. Краснодар, ул. Гаражная, 107/1, офис 6'),
	array('id' => 22051, 'phone' => '+7 (863) 22-999-50', 'text' => 'г. Ростов-на-Дону, ул. Ларина, 45с2'),
);

$ndSalePodmena = array();
if (function_exists('ndIsUtmVisit') && ndIsUtmVisit() && CModule::IncludeModule('iblock')) {
	$ndSaleRes = CIBlockElement::GetList(
		array(),
		array('IBLOCK_ID' => 10, 'ID' => array_column($ndSaleOffices, 'id'), 'CHECK_PERMISSIONS' => 'N'),
		false,
		false,
		array('ID', 'IBLOCK_ID', 'PROPERTY_PHONE_PODMENA')
	);
	while ($ndSaleRow = $ndSaleRes->GetNext()) {
		$ndSaleNumber = trim((string)$ndSaleRow['~PROPERTY_PHONE_PODMENA_VALUE']);
		if ($ndSaleNumber !== '') {
			$ndSalePodmena[(int)$ndSaleRow['ID']] = $ndSaleNumber;
		}
	}
}
?>
<ul class="c-lists nd-sale-contacts">
	<?foreach($ndSaleOffices as $ndSaleOffice):?>
		<?$ndSalePhone = $ndSalePodmena[$ndSaleOffice['id']] ?? $ndSaleOffice['phone'];?>
		<li><?=htmlspecialcharsbx($ndSalePhone)?> / <?=$ndSaleOffice['text']?></li>
	<?endforeach;?>
	<?/* Общий номер ничей, подменять его нечем — печатаем как есть. */?>
	<li>8 (800) 505-45-40 бесплатный звонок по РФ</li>
</ul>
