<?php
/**
 * Разовый перенос роликов в инфоблок «Видео» (videos) из «Видео в попапе» товаров
 * (POPUP_VIDEO, ИБ 19) и «Товарного видео» предложений (PRODUCT_VIDEO, ИБ 20) —
 * та же логика, что у обработчиков сохранения (LatitudoVideoSync::syncProduct).
 *
 *     php local/tools/nd_videos_migrate.php                      — прогон без записи
 *     php local/tools/nd_videos_migrate.php apply                — записать
 *     php local/tools/nd_videos_migrate.php rollback=<файл.json> — вернуть как было
 *
 * Повторный запуск безопасен: ролик ищется по URL_NORM, товар второй раз не
 * привязывается. Ролики, заведённые переносом, сразу ВКЛЮЧЕНЫ (решение Ирины
 * 28.10.2026: иначе блок пуст, пока не включат все); новые из обработчика —
 * выключены (LatitudoVideoSync::ACTIVATE_NEW).
 *
 * Снимок «до» и список созданных — ~/nd_videos/<дата>_<режим>/ (вне www).
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only');
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
@set_time_limit(0);

if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('catalog')) {
    die("Модули iblock/catalog не подключены\n");
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/latitudo_videos.php';

$mode = 'dry';
$rollbackFile = '';
foreach (array_slice($argv, 1) as $a) {
    if ($a === 'apply') {
        $mode = 'apply';
    } elseif (strpos($a, 'rollback=') === 0) {
        $mode = 'rollback';
        $rollbackFile = substr($a, 9);
    } else {
        fwrite(STDERR, "Неизвестный параметр: $a\n");
        exit(1);
    }
}

$videosIb = LatitudoVideoSync::iblockId();
if (!$videosIb) {
    die("Нет инфоблока videos — сначала php local/tools/nd_videos_install.php\n");
}

$outDir = getenv('HOME') . '/nd_videos/' . date('Y-m-d_His') . '_' . $mode;
mkdir($outDir, 0700, true);

/** Состояние инфоблока: ID => [ACTIVE, PRODUCTS, BRAND] */
function ndVideosSnapshot(int $videosIb): array
{
    $out = [];
    $rs = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => $videosIb, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'ACTIVE']);
    while ($v = $rs->Fetch()) {
        $out[(int)$v['ID']] = ['ACTIVE' => $v['ACTIVE']];
    }
    if ($out) {
        $values = [];
        CIBlockElement::GetPropertyValuesArray($values, $videosIb, ['ID' => array_keys($out)], ['CODE' => ['PRODUCTS', 'BRAND']]);
        foreach ($out as $id => &$v) {
            $v['PRODUCTS'] = array_values(array_map('intval', array_filter((array)($values[$id]['PRODUCTS']['VALUE'] ?? []))));
            $v['BRAND'] = (int)($values[$id]['BRAND']['VALUE'] ?? 0);
        }
        unset($v);
    }
    return $out;
}

// --- откат: созданные удаляем, у прежних возвращаем товары, марку, активность
if ($mode === 'rollback') {
    $before = json_decode((string)@file_get_contents($rollbackFile), true);
    if (!is_array($before) || !isset($before['videos'])) {
        die("Не прочитан снимок: $rollbackFile\n");
    }
    $now = ndVideosSnapshot($videosIb);
    $el = new CIBlockElement();
    $deleted = $restored = 0;
    foreach ($now as $id => $v) {
        if (!isset($before['videos'][$id])) {
            CIBlockElement::Delete($id) && $deleted++;
            continue;
        }
        $b = $before['videos'][$id];
        CIBlockElement::SetPropertyValuesEx($id, $videosIb, ['PRODUCTS' => $b['PRODUCTS'] ?: false, 'BRAND' => $b['BRAND'] ?: false]);
        if ($b['ACTIVE'] !== $v['ACTIVE']) {
            $el->Update($id, ['ACTIVE' => $b['ACTIVE']]);
        }
        $restored++;
    }
    CIBlock::clearIblockTagCache($videosIb);
    echo "Откат: удалено роликов {$deleted}, восстановлено {$restored}\n";
    exit(0);
}

// --- товары: со ссылкой у себя или у предложения, и те, что уже стоят в роликах
$ids = [];
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => LatitudoVideoSync::CATALOG_IBLOCK, '!PROPERTY_' . LatitudoVideoSync::PRODUCT_PROP => false, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID']);
while ($r = $rs->Fetch()) {
    $ids[(int)$r['ID']] = true;
}
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => LatitudoVideoSync::OFFERS_IBLOCK, '!PROPERTY_' . LatitudoVideoSync::OFFER_PROP => false, 'CHECK_PERMISSIONS' => 'N'], false, false, ['PROPERTY_CML2_LINK']);
while ($r = $rs->Fetch()) {
    if ((int)$r['PROPERTY_CML2_LINK_VALUE'] > 0) {
        $ids[(int)$r['PROPERTY_CML2_LINK_VALUE']] = true;
    }
}
$before = ndVideosSnapshot($videosIb);
foreach ($before as $v) {
    foreach ($v['PRODUCTS'] as $pid) {
        $ids[$pid] = true;
    }
}
ksort($ids);
echo 'Товаров к обработке: ' . count($ids) . '; роликов в инфоблоке сейчас: ' . count($before) . "\n";

if ($mode === 'apply') {
    file_put_contents($outDir . '/before.json', json_encode(['videos' => $before], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// В прогоне без записи новые ролики не создаются, поэтому одна и та же ссылка у
// нескольких товаров дала бы несколько «новых» — считаем уникальные сами.
$stat = [];
$log = [];
$newNorms = [];
foreach (array_keys($ids) as $pid) {
    foreach (LatitudoVideoSync::syncProduct($pid, $mode !== 'apply', $stat, true) as $line) {
        if ($mode !== 'apply' && preg_match('~^новый ролик (\S+)~u', $line, $m)) {
            if (isset($newNorms[$m[1]])) {
                $stat['created']--;
                $stat['linked'] = ($stat['linked'] ?? 0) + 1;
                $line = "ролик (новый) {$m[1]} ← товар {$pid}";
            }
            $newNorms[$m[1]] = true;
        }
        $log[] = $line;
    }
}
file_put_contents($outDir . '/log.txt', implode("\n", $log) . "\n");

// дубли по URL_NORM — проверка «готово, когда»
$dups = [];
if ($mode === 'apply') {
    $seen = [];
    $rs = CIBlockElement::GetList([], ['IBLOCK_ID' => $videosIb, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'PROPERTY_URL_NORM']);
    while ($v = $rs->Fetch()) {
        $n = (string)$v['PROPERTY_URL_NORM_VALUE'];
        if (isset($seen[$n])) {
            $dups[] = $n;
        }
        $seen[$n] = true;
    }
}

echo "Новых роликов: " . ($stat['created'] ?? 0) . "\n"
    . "Привязок товара к имеющемуся ролику: " . ($stat['linked'] ?? 0) . "\n"
    . "Снято товаров с роликов: " . ($stat['unlinked'] ?? 0) . ", выключено роликов без товаров: " . ($stat['deactivated'] ?? 0) . "\n"
    . "Удалены на хостинге (заведены выключенными): " . ($stat['gone'] ?? 0) . "\n"
    . "Ошибок: " . ($stat['errors'] ?? 0) . "\n";
if ($mode === 'apply') {
    echo 'Дублей по URL_NORM: ' . count($dups) . ($dups ? ' — ' . implode(', ', $dups) : '') . "\n";
    echo "Журнал: {$outDir}/log.txt\nОткат: php " . __FILE__ . " rollback={$outDir}/before.json\n";
} else {
    echo "Журнал: {$outDir}/log.txt\nПрогон без записи — в базе ничего не изменено. Записать: добавьте apply\n";
}
