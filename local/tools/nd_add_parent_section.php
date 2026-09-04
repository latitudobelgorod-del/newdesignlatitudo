<?php
/**
 * Привязать товары подразделов ещё и к родительскому разделу.
 *
 *     php local/tools/nd_add_parent_section.php --section=<ID или код>          # только показать
 *     php local/tools/nd_add_parent_section.php --section=<ID или код> --apply  # записать
 *     php local/tools/nd_add_parent_section.php --section=… --apply --undo=файл # откат по списку
 *
 * Зачем. У раздела «Уличные диваны для сада, дачи и террасы» товары лежат по
 * подразделам, и в самом разделе их не видно. Битрикс умеет держать элемент в
 * нескольких разделах сразу: основной остаётся прежним (по нему строится адрес
 * и хлебные крошки), а дополнительный добавляется в привязки
 * (b_iblock_section_element). Просьба Ирины, 4 сентября 2026.
 *
 * Осторожности:
 * - по умолчанию скрипт НИЧЕГО не пишет, только показывает, что сделал бы;
 * - при записи пишется файл отката со списком прежних привязок каждого товара,
 *   чтобы вернуть всё одной командой;
 * - основной раздел (IBLOCK_SECTION_ID) не трогаем — адреса товаров не меняются.
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only');
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!CModule::IncludeModule('iblock')) {
    die("Модуль iblock не подключён\n");
}

$opts = getopt('', ['section:', 'iblock::', 'apply', 'undo::']);
$iblockId = isset($opts['iblock']) ? (int) $opts['iblock'] : 19;
$apply = isset($opts['apply']);
$undoFile = $opts['undo'] ?? '';

if (empty($opts['section']) && !$undoFile) {
    die("Укажите --section=<ID или символьный код раздела>\n");
}

/* ------------------------------------------------------------------ откат */

if ($undoFile) {
    $rows = json_decode((string) file_get_contents($undoFile), true);
    if (!is_array($rows)) {
        die("Файл отката не читается: {$undoFile}\n");
    }
    echo 'Откат по файлу '.$undoFile.': элементов '.count($rows)."\n";
    foreach ($rows as $id => $sections) {
        if ($apply) {
            CIBlockElement::SetElementSection((int) $id, array_map('intval', $sections), false, 0);
        }
    }
    echo $apply ? "Готово.\n" : "Это прогон без записи, добавьте --apply.\n";
    exit;
}

/* --------------------------------------------------------------- раздел */

$sectionFilter = ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'];
if (preg_match('/^\d+$/', (string) $opts['section'])) {
    $sectionFilter['ID'] = (int) $opts['section'];
} else {
    $sectionFilter['=CODE'] = $opts['section'];
}

$parent = CIBlockSection::GetList([], $sectionFilter, false, ['ID', 'NAME', 'CODE', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL'])->Fetch();
if (!$parent) {
    die("Раздел не найден\n");
}
$parentId = (int) $parent['ID'];
echo "Раздел: {$parent['NAME']} (ID {$parentId}, код {$parent['CODE']})\n";

/* Подразделы любой глубины: берём по границам дерева, а не по SECTION_ID —
   так попадут и вложенные во вложенные. */
$subs = [];
$rs = CIBlockSection::GetList(
    ['LEFT_MARGIN' => 'ASC'],
    [
        'IBLOCK_ID' => $iblockId,
        '>LEFT_MARGIN' => $parent['LEFT_MARGIN'],
        '<RIGHT_MARGIN' => $parent['RIGHT_MARGIN'],
    ],
    false,
    ['ID', 'NAME', 'ACTIVE']
);
while ($row = $rs->Fetch()) {
    $subs[(int) $row['ID']] = $row['NAME'];
}

if (!$subs) {
    die("У раздела нет подразделов — нечего переносить\n");
}
echo 'Подразделов: '.count($subs)."\n";
foreach ($subs as $id => $name) {
    echo "  {$id} — {$name}\n";
}

/* ---------------------------------------------------------------- товары */

$elements = [];
$rs = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => $iblockId, 'SECTION_ID' => array_keys($subs), 'INCLUDE_SUBSECTIONS' => 'Y'],
    false,
    false,
    ['ID', 'NAME', 'ACTIVE']
);
while ($row = $rs->Fetch()) {
    $elements[(int) $row['ID']] = $row['NAME'];
}

echo 'Товаров в подразделах: '.count($elements)."\n";

$toChange = [];
$undo = [];
foreach ($elements as $id => $name) {
    $current = [];
    $rsG = CIBlockElement::GetElementGroups($id, true, ['ID']);
    while ($g = $rsG->Fetch()) {
        $current[] = (int) $g['ID'];
    }
    $current = array_values(array_unique($current));

    if (in_array($parentId, $current, true)) {
        continue;                      // уже привязан — не трогаем
    }

    $undo[$id] = $current;
    $toChange[$id] = array_merge($current, [$parentId]);
}

echo 'Нужно добавить привязку: '.count($toChange)." товарам\n";
$shown = 0;
foreach ($toChange as $id => $sections) {
    if ($shown++ >= 5) {
        break;
    }
    echo "  {$id} {$elements[$id]} : разделы ".implode(',', $sections)."\n";
}

if (!$toChange) {
    echo "Все товары уже привязаны — делать нечего.\n";
    exit;
}

if (!$apply) {
    echo "\nЭто прогон БЕЗ ЗАПИСИ. Чтобы применить, добавьте --apply\n";
    exit;
}

/* ----------------------------------------------------------------- запись */

$undoPath = $_SERVER['DOCUMENT_ROOT'].'/nd_section_undo_'.$parentId.'_'.date('Ymd_His').'.json';
file_put_contents($undoPath, json_encode($undo, JSON_UNESCAPED_UNICODE));
echo "Файл отката: {$undoPath}\n";

$ok = $fail = 0;
foreach ($toChange as $id => $sections) {
    /* SetElementSection перезаписывает набор привязок целиком, поэтому передаём
       прежние разделы плюс наш. Основной раздел элемента при этом сохраняется:
       он хранится отдельно, в поле IBLOCK_SECTION_ID. */
    if (CIBlockElement::SetElementSection($id, $sections, false, 0)) {
        $ok++;
    } else {
        $fail++;
        echo "  ! не удалось: {$id}\n";
    }
}

/* Списки раздела кешируются с тегом инфоблока — без сброса товары появятся
   в разделе только после истечения кеша. */
if (isset($GLOBALS['CACHE_MANAGER'])) {
    $GLOBALS['CACHE_MANAGER']->ClearByTag('iblock_id_'.$iblockId);
    echo "Кеш инфоблока {$iblockId} сброшен" . PHP_EOL;
}

echo "Готово: обновлено {$ok}, ошибок {$fail}\n";
echo "Откат: php local/tools/nd_add_parent_section.php --undo={$undoPath} --apply\n";
