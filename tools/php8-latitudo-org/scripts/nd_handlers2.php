<?php
// проверка «класса нет»: подключается ли модуль-получатель и есть ли класс после подключения (как делает ExecuteModuleEventEx)
$_SERVER["DOCUMENT_ROOT"] = $argv[1];
define("NO_KEEP_STATISTIC", true); define("NOT_CHECK_PERMISSIONS", true); define("NO_AGENT_CHECK", true); define("BX_NO_ACCELERATOR_RESET", true);
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";
$r = $GLOBALS["DB"]->Query("SELECT * FROM b_module_to_module ORDER BY TO_MODULE_ID");
while ($e = $r->Fetch()) {
	if ($e["TO_CLASS"] == "" || $e["TO_METHOD"] == "") continue;
	$mod = $e["TO_MODULE_ID"];
	if ($mod != "" && $mod != "main" && !CModule::IncludeModule($mod)) continue;  // так обработчик и не вызовется
	if ($e["TO_PATH"] != "" && file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT.$e["TO_PATH"])) include_once $_SERVER["DOCUMENT_ROOT"].BX_ROOT.$e["TO_PATH"];
	$cls = $e["TO_CLASS"];
	if (!class_exists($cls)) { echo "НЕТ КЛАССА\t$mod\t$cls::{$e["TO_METHOD"]}\t{$e["FROM_MODULE_ID"]}:{$e["MESSAGE_ID"]}\n"; continue; }
	if (!method_exists($cls, $e["TO_METHOD"]) && !method_exists($cls, "__callStatic")) { echo "НЕТ МЕТОДА\t$mod\t$cls::{$e["TO_METHOD"]}\t{$e["FROM_MODULE_ID"]}:{$e["MESSAGE_ID"]}\n"; continue; }
	if (method_exists($cls, $e["TO_METHOD"])) { $m = new ReflectionMethod($cls, $e["TO_METHOD"]); if (!$m->isStatic()) echo "НЕ STATIC\t$mod\t$cls::{$e["TO_METHOD"]}\t{$e["FROM_MODULE_ID"]}:{$e["MESSAGE_ID"]}\n"; }
}
