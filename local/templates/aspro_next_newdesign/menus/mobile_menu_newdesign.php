<?
// Пункты мобильного меню нового дизайна (панель по кнопке «Меню» в нижней панели).
// Подключается из корневого .mobile_menu_newdesign.menu.php.
//
// Оформление пункта задаётся четвёртым параметром:
//   "CLASS" => "strong"  — крупный жирный пункт верхней группы (как в макете
//                          «Каталог», «Услуги», «Партнерам», «Портфолио»);
//                          без него пункт выводится обычным серым.
//   "ICON"  => "catalog" — иконка слева (сейчас есть только "catalog").
//   "ARROW" => "N"       — убрать стрелку справа.
$aMenuLinks = Array(
	Array(
		"Каталог",
		"/catalog/",
		Array(),
		Array("CLASS" => "strong", "ICON" => "catalog"),
		""
	),
	Array(
		"Услуги",
		"/services/",
		Array(),
		Array("CLASS" => "strong"),
		""
	),
	Array(
		"Партнерам",
		"/info/",
		Array(),
		Array("CLASS" => "strong"),
		""
	),
	Array(
		"Портфолио",
		"/projects/",
		Array(),
		Array("CLASS" => "strong"),
		""
	),
	Array(
		"Акции и скидки",
		"/sale/",
		Array(),
		Array("ARROW" => "N"),
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
		"Клиентам",
		"/info/kak-kupit/",
		Array(),
		Array(),
		""
	),
	Array(
		"Отзывы",
		"/company/reviews/",
		Array(),
		Array("ARROW" => "N"),
		""
	),
	Array(
		"Работа у нас",
		"/info/vakansii/",
		Array(),
		Array("ARROW" => "N"),
		""
	),
	Array(
		"Статьи",
		"/materials/",
		Array(),
		Array("ARROW" => "N"),
		""
	),
	Array(
		"Контакты",
		"/contacts/",
		Array(),
		Array("ARROW" => "N"),
		""
	)
);
?>
