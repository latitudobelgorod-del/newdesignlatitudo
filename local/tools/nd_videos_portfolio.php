<?php
/**
 * Разовый перенос роликов Rutube из карточек портфолио (ИБ 18, блоки «video»
 * редактора в EDITOR1/EDITOR2) в инфоблок «Видео» (videos) — Ирина, 28.10.2026.
 *
 *     php local/tools/nd_videos_portfolio.php                      — прогон без записи
 *     php local/tools/nd_videos_portfolio.php apply                — записать
 *     php local/tools/nd_videos_portfolio.php rollback=<файл.json> — вернуть как было
 *
 * Тип ролика (TYPE) — по раскладке, которую Ирина утвердила 28.10.2026 (TYPE_BY_ID
 * ниже), остальные — «Наши объекты». Ролик ищется по URL_NORM: уже заведённому
 * (например, «Умная пергола» из товара) только дописываются проекты, тип не
 * меняется. Новые — включены; удалённые на Rutube и стоящие только в выключенных
 * карточках — выключены. YouTube из портфолио не переносим (просили Rutube).
 *
 * Сначала — nd_videos_install.php (свойства TYPE и PROJECT).
 * Снимок «до» и журнал — ~/nd_videos/<дата>_portfolio_<режим>/ (вне www).
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

if (!CModule::IncludeModule('iblock')) {
    die("Модуль iblock не подключён\n");
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/latitudo_videos.php';

/** Начало номера ролика Rutube => тип; кого нет — objects */
const TYPE_BY_ID = [
    // видеообзоры продукции
    '2fdc7680' => 'product', 'c905e52b' => 'product', 'd3788188' => 'product',
    '60ac9f0f' => 'product', 'e61102ff' => 'product', '10ed60ce' => 'product',
    // видеоотзывы клиентов («Хороший пример применения ДПК» начинается с отзыва)
    'b317bb9b' => 'review', '2de0a4f9' => 'review', 'b10d7c0d' => 'review',
    'e764c4a3' => 'review', 'bbd45769' => 'review', 'f85d89eb' => 'review',
    // эксперт объясняет
    '37d289bb' => 'expert', 'e01a7575' => 'expert', '443ff869' => 'expert', '7b64b485' => 'expert',
];

/** Порядок на /video/ во «Все»: сначала обзоры продукции, потом отзывы, эксперт, объекты */
const SORT_BY_TYPE = ['product' => 500, 'review' => 600, 'expert' => 700, 'objects' => 800];

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
    die("Нет инфоблока videos\n");
}
$typeEnum = [];
$rs = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $videosIb, 'CODE' => 'TYPE']);
while ($e = $rs->Fetch()) {
    $typeEnum[$e['XML_ID']] = (int)$e['ID'];
}
if (count($typeEnum) < count(LatitudoVideoRepository::TYPES)
    || !CIBlockProperty::GetList([], ['IBLOCK_ID' => $videosIb, 'CODE' => 'PROJECT'])->Fetch()) {
    die("Нет свойств TYPE/PROJECT — сначала php local/tools/nd_videos_install.php\n");
}

$outDir = getenv('HOME') . '/nd_videos/' . date('Y-m-d_His') . '_portfolio_' . $mode;
mkdir($outDir, 0700, true);

/** ID ролика => [PROJECT] */
function ndPortfolioSnapshot(int $videosIb): array
{
    $out = [];
    $rs = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => $videosIb, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID']);
    while ($v = $rs->Fetch()) {
        $out[(int)$v['ID']] = ['PROJECT' => []];
    }
    if ($out) {
        $values = [];
        CIBlockElement::GetPropertyValuesArray($values, $videosIb, ['ID' => array_keys($out)], ['CODE' => ['PROJECT']]);
        foreach ($out as $id => &$v) {
            $v['PROJECT'] = array_values(array_map('intval', array_filter((array)($values[$id]['PROJECT']['VALUE'] ?? []))));
        }
        unset($v);
    }
    return $out;
}

// --- откат: созданные удаляем, у прежних возвращаем проекты
if ($mode === 'rollback') {
    $before = json_decode((string)@file_get_contents($rollbackFile), true);
    if (!is_array($before) || !isset($before['videos'])) {
        die("Не прочитан снимок: $rollbackFile\n");
    }
    $deleted = $restored = 0;
    foreach (ndPortfolioSnapshot($videosIb) as $id => $v) {
        if (!isset($before['videos'][$id])) {
            CIBlockElement::Delete($id) && $deleted++;
        } elseif ($before['videos'][$id]['PROJECT'] !== $v['PROJECT']) {
            CIBlockElement::SetPropertyValuesEx($id, $videosIb, ['PROJECT' => $before['videos'][$id]['PROJECT'] ?: false]);
            $restored++;
        }
    }
    CIBlock::clearIblockTagCache($videosIb);
    echo "Откат: удалено роликов {$deleted}, возвращены проекты у {$restored}\n";
    exit(0);
}

// --- ролики из карточек: NORM => [PARSED, CARDS => [ID => ACTIVE], NAME карточки]
$found = [];
$skipped = [];
$rs = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => LatitudoVideoRepository::PORTFOLIO_IBLOCK, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'NAME', 'ACTIVE']);
while ($card = $rs->Fetch()) {
    foreach (['EDITOR1', 'EDITOR2'] as $code) {
        $prop = CIBlockElement::GetProperty(LatitudoVideoRepository::PORTFOLIO_IBLOCK, $card['ID'], [], ['CODE' => $code])->Fetch();
        $data = json_decode((string)($prop['VALUE'] ?? ''), true);
        foreach ((array)($data['blocks'] ?? []) as $b) {
            if (($b['name'] ?? '') !== 'video' || empty($b['url'])) {
                continue;
            }
            $parsed = LatitudoVideoUrl::parse((string)$b['url']);
            if (!$parsed || $parsed['HOST'] !== 'rutube') {
                $skipped[] = "карточка {$card['ID']}: " . $b['url'];
                continue;
            }
            $found[$parsed['NORM']] = $found[$parsed['NORM']] ?? ['PARSED' => $parsed + ['URL' => 'https://rutube.ru/video/' . $parsed['ID'] . '/'], 'CARDS' => [], 'CARD_NAME' => $card['NAME']];
            $found[$parsed['NORM']]['CARDS'][(int)$card['ID']] = $card['ACTIVE'] === 'Y';
        }
    }
}

// уже заведённые ролики
$existing = [];
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => $videosIb, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'PROPERTY_URL_NORM']);
while ($v = $rs->Fetch()) {
    $existing[(string)$v['PROPERTY_URL_NORM_VALUE']] = (int)$v['ID'];
}
$before = ndPortfolioSnapshot($videosIb);
if ($mode === 'apply') {
    file_put_contents($outDir . '/before.json', json_encode(['videos' => $before], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

echo 'Роликов Rutube в портфолио: ' . count($found) . ', не перенесены (не Rutube или пусто): ' . count($skipped) . "\n";
$log = $skipped ? array_map(fn($s) => 'пропуск ' . $s, $skipped) : [];
$stat = ['created' => 0, 'linked' => 0, 'off' => 0, 'errors' => 0, 'type' => []];
$el = new CIBlockElement();

foreach ($found as $norm => $f) {
    $p = $f['PARSED'];
    $type = 'objects';
    foreach (TYPE_BY_ID as $prefix => $t) {
        if (strpos($p['ID'], $prefix) === 0) {
            $type = $t;
            break;
        }
    }
    // сначала включённые карточки — ссылка «Смотреть проект» ведёт на первую из них
    $cards = $f['CARDS'];
    uksort($cards, fn($a, $b) => ($cards[$b] <=> $cards[$a]) ?: $a <=> $b);
    $cardIds = array_keys($cards);
    $anyActive = in_array(true, $cards, true);

    if (isset($existing[$norm])) {
        $id = $existing[$norm];
        $projects = array_values(array_unique(array_merge($before[$id]['PROJECT'] ?? [], $cardIds)));
        if ($projects === ($before[$id]['PROJECT'] ?? [])) {
            $log[] = "есть #{$id} {$norm}: проекты уже стоят";
            continue;
        }
        $log[] = "есть #{$id} {$norm}: + проекты " . implode(',', $cardIds) . ' (тип не трогаем)';
        if ($mode === 'apply') {
            CIBlockElement::SetPropertyValuesEx($id, $videosIb, ['PROJECT' => $projects]);
        }
        $stat['linked']++;
        continue;
    }

    $meta = LatitudoVideoHosting::meta($p);
    $gone = !empty($meta['GONE']);
    $active = $anyActive && !$gone;
    $name = trim((string)($meta['TITLE'] ?? '')) ?: $f['CARD_NAME'];
    $stat['type'][$type] = ($stat['type'][$type] ?? 0) + 1;
    $active || $stat['off']++;
    $line = sprintf('новый [%s] %s «%s» ← карточки %s%s', $type, $norm, $name, implode(',', $cardIds),
        $gone ? ' — УДАЛЁН на Rutube, выключен' : ($anyActive ? '' : ' — карточки выключены, ролик выключен'));

    if ($mode !== 'apply') {
        $log[] = $line;
        $stat['created']++;
        continue;
    }
    $fields = [
        'IBLOCK_ID' => $videosIb,
        'NAME' => $name,
        'CODE' => LatitudoVideoSync::uniqueCode($videosIb, $name),
        'ACTIVE' => $active ? 'Y' : 'N',
        'SORT' => SORT_BY_TYPE[$type],
        'PROPERTY_VALUES' => [
            'URL' => $p['URL'],
            'URL_NORM' => $norm,
            'DURATION' => !empty($meta['DURATION']) ? LatitudoVideoRepository::formatDuration((int)$meta['DURATION']) : '',
            'UPLOAD_DATE' => (string)($meta['DATE'] ?? ''),
            'TYPE' => $typeEnum[$type],
            'PROJECT' => $cardIds,
        ],
    ];
    if (!empty($meta['THUMB'])) {
        $file = CFile::MakeFileArray($meta['THUMB']);
        if (is_array($file) && !empty($file['tmp_name'])) {
            $file['name'] = 'video-' . $p['ID'] . '.jpg';
            $file['MODULE_ID'] = 'iblock';
            $fields['PREVIEW_PICTURE'] = $file;
        }
    }
    $id = (int)$el->Add($fields);
    if ($id) {
        $existing[$norm] = $id;
        $stat['created']++;
        $log[] = $line . " → #{$id}";
    } else {
        $stat['errors']++;
        $log[] = '! ' . $line . ': ' . $el->LAST_ERROR;
    }
}
if ($mode === 'apply') {
    CIBlock::clearIblockTagCache($videosIb);
}
file_put_contents($outDir . '/log.txt', implode("\n", $log) . "\n");
echo implode("\n", $log) . "\n\n";

ksort($stat['type']);
echo 'Новых роликов: ' . $stat['created'] . ' (' . implode(', ', array_map(fn($t, $n) => "$t $n", array_keys($stat['type']), $stat['type'])) . ")\n"
    . 'Из них выключены (удалены на Rutube или карточки выключены): ' . $stat['off'] . "\n"
    . 'Уже заведённым дописаны проекты: ' . $stat['linked'] . "\n"
    . 'Ошибок: ' . $stat['errors'] . "\n";
if ($mode === 'apply') {
    echo "Журнал: {$outDir}/log.txt\nОткат: php " . __FILE__ . " rollback={$outDir}/before.json\n";
} else {
    echo "Журнал: {$outDir}/log.txt\nПрогон без записи — в базе ничего не изменено. Записать: добавьте apply\n";
}
