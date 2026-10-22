<?php
// Обработчики событий из b_module_to_module, которые PHP 8 не сможет вызвать статически. Только чтение.
$_SERVER['DOCUMENT_ROOT'] = $argv[1];
define('NO_KEEP_STATISTIC', true); define('NOT_CHECK_PERMISSIONS', true); define('NO_AGENT_CHECK', true); define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
$res = $GLOBALS['DB']->Query("SELECT FROM_MODULE_ID, MESSAGE_ID, TO_MODULE_ID, TO_CLASS, TO_METHOD, TO_PATH FROM b_module_to_module WHERE TO_CLASS<>'' ORDER BY TO_MODULE_ID");
$bad = 0; $all = 0;
while ($r = $res->Fetch()) {
    $all++;
    if ($r['TO_MODULE_ID'] && !\Bitrix\Main\Loader::includeModule($r['TO_MODULE_ID'])) { /* модуль не ставится — пропуск */ }
    if ($r['TO_PATH'] && file_exists($_SERVER['DOCUMENT_ROOT'].$r['TO_PATH'])) include_once $_SERVER['DOCUMENT_ROOT'].$r['TO_PATH'];
    $why = '';
    if (!class_exists($r['TO_CLASS'])) $why = 'класса нет';
    elseif (!method_exists($r['TO_CLASS'], $r['TO_METHOD'])) { if (!method_exists($r['TO_CLASS'], '__callStatic')) $why = 'метода нет'; }
    else { $m = new ReflectionMethod($r['TO_CLASS'], $r['TO_METHOD']); if (!$m->isStatic()) $why = 'НЕ static'; }
    if ($why) { $bad++; echo "{$r['TO_MODULE_ID']} | {$r['TO_CLASS']}::{$r['TO_METHOD']} | {$r['FROM_MODULE_ID']}:{$r['MESSAGE_ID']} | $why\n"; }
}
echo "всего обработчиков с классом: $all, проблемных: $bad\n";
