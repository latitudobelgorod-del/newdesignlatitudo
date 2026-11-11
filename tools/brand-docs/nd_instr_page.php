<?php
/**
 * /materials/montazhnye-skhemy-i-instruktsii/ (ИБ 14, элемент 25100): блок iblock_elements (ИБ 13)
 * переводится на вариант style6 — все активные инструкции/схемы активных марок (11.11.2026).
 *   php nd_instr_page.php dry|run|rollback
 * Бэкап EDITOR1 — instr_editor1_before.json рядом со скриптом.
 */
$_SERVER['DOCUMENT_ROOT'] = getenv('HOME') . '/www/latitudo.ru';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

const EL = 25100;
const IB = 14;
$mode = $argv[1] ?? 'dry';
$bak = __DIR__ . '/instr_editor1_before.json';

$p = CIBlockElement::GetProperty(IB, EL, [], ['CODE' => 'EDITOR1'])->Fetch();
$raw = is_array($p['VALUE']) ? ($p['VALUE']['TEXT'] ?? '') : (string)$p['VALUE'];

if ($mode === 'rollback') {
    $old = file_get_contents($bak);
    CIBlockElement::SetPropertyValuesEx(EL, IB, ['EDITOR1' => $old]);
    CIBlock::clearIblockTagCache(IB);
    CIBlock::clearIblockTagCache(13);
    rename($bak, $bak . '.rolledback.' . date('YmdHis'));
    exit("restored\n");
}

$j = json_decode($raw, true);
if (!is_array($j) || empty($j['blocks'])) {
    exit("EDITOR1 не разобран\n");
}
$found = false;
foreach ($j['blocks'] as $i => $b) {
    if (($b['name'] ?? '') === 'iblock_elements' && (int)($b['iblock_id'] ?? 0) === 13) {
        echo "блок $i: param1 «" . ($b['settings']['param1'] ?? '') . "» → style6\n";
        $j['blocks'][$i]['settings']['param1'] = 'style6';
        $found = true;
    }
}
if (!$found) {
    exit("блок iblock_elements(13) не найден\n");
}
if ($mode !== 'run') {
    exit("прогон без записи\n");
}
if (file_exists($bak)) {
    exit("Уже применено (есть бэкап). Сначала rollback.\n");
}
file_put_contents($bak, $raw);
CIBlockElement::SetPropertyValuesEx(EL, IB, ['EDITOR1' => json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
CIBlock::clearIblockTagCache(IB);
CIBlock::clearIblockTagCache(13);
echo "done\n";
