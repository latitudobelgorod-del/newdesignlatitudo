<?php


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



?>
<?/* Стиль ссылок здесь: css/newdesign.css отдаётся урезанной копией (css/purged), новое правило туда без пересборки не попадёт. */?>
<style>
.nd-sale-contacts__tel{color:inherit;text-decoration:none;white-space:nowrap}
.nd-sale-contacts__tel:hover{color:#c8161d;text-decoration:underline}
</style>
<ul class="c-lists nd-sale-contacts">

	<?foreach($ndSaleOffices as $ndSaleOffice):?>
		<?$ndSalePhone = $ndSaleOffice['phone'];?>
		<li><a class="nd-sale-contacts__tel" href="tel:+<?=preg_replace('/\D/', '', $ndSalePhone)?>"><?=htmlspecialcharsbx($ndSalePhone)?></a> / <?=$ndSaleOffice['text']?></li>
	<?endforeach;?>

	<li><a class="nd-sale-contacts__tel" href="tel:88005054540">8 (800) 505-45-40</a> бесплатный звонок по РФ</li>
</ul>
