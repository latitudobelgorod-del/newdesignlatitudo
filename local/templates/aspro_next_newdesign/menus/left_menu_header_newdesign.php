<?
// Левая группа нижней строки шапки нового дизайна.
// Подключается из корневого .left_menu_header_newdesign.menu.php.
// Чтобы подсветить пункт красным (как «Акции» в макете), добавьте ему
// в четвёртый параметр Array("CLASS" => "accent").
// 11 сентября 2026 (Ирина): на месте «Акций и скидок» — «Работа у нас» с тем
// же красным оформлением; «Акции» переехали красной кнопкой в основную строку
// рядом с «Каталогом» (page_blocks/header_newdesign.php, $arMainNav).
$aMenuLinks = Array(
	Array(
		"Работа у нас",
		"/info/vakansii/",
		Array(),
		Array("CLASS" => "accent"),
		""
	),
	Array(
		"Доска из ДПК",
		"/catalog/terrasnaya-doska-iz-dpk/",
		Array(),
		Array(),
		""
	),
	Array(
		"Комплектующие",
		"/catalog/komplektuyushie/",
		Array(),
		Array(),
		""
	),
	Array(
		"Заборная доска",
		"/catalog/zabor-iz-dpk/",
		Array(),
		Array(),
		""
	)
);
?>
