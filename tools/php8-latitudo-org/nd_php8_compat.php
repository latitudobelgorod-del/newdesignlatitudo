<?php
// Посетителям ошибки PHP не показываем: на PHP 8.3 хостинга display_errors включён по умолчанию, а init.php
// ставит error_reporting(-1) — предупреждения Аспро выводились прямо в страницу (22.10.2026). В журнал пишутся.
ini_set("display_errors", "0");
/* Перевод latitudo.org на PHP 8 (php2shell, 22.10.2026).
   nd_count7() — count() как в PHP 7.4: null → 0, строка/число → 1, массив и Countable — обычный count.
   PHP 8 на не-массивах бросает TypeError, а старые шаблоны Аспро и модуль aspro.next так считают
   «пустые» свойства повсюду; все их вызовы count() заменены на nd_count7(). Подключается из
   php_interface/init.php и modules/aspro.next/include.php — что раньше загрузится. */
if (!function_exists("nd_count7")) {
	function nd_count7($value, $mode = COUNT_NORMAL)
	{
		if (is_array($value) || $value instanceof \Countable) {
			return count($value, $mode);
		}
		return $value === null ? 0 : 1;
	}
}
