<?
// Правая группа нижней строки шапки нового дизайна.
// Подключается из корневого .right_menu_header_newdesign.menu.php.
// Чтобы подсветить пункт красным, добавьте ему в четвёртый параметр
// Array("CLASS" => "accent").
$aMenuLinks = Array(
	Array(
		"Отзывы",
		"/company/reviews/",
		Array(),
		Array(),
		""
	),
	Array(
		"Производители",
		"/brands/",
		Array(),
		Array(),
		""
	),
	// «Услуги» — на месте «Работы у нас» (Ирина, 11 сентября 2026); выпадающая
	// панель услуг привязывается к пункту по адресу и переехала вместе с ним.
	Array(
		"Услуги",
		"/services/",
		Array(),
		Array(),
		""
	),
	Array(
		"Клиентам",
		"/info/kak-kupit/",
		Array(),
		Array(),
		""
	),
	Array(
		"О компании",
		"/info/company/",
		Array(),
		Array(),
		""
	),
	Array(
		"Контакты",
		"/contacts/",
		Array(),
		Array(),
		""
	)
);
?>
