<?php
/**
 * Разовое заполнение свойства «Короткая ссылка» (ND_SHORT_URI) у всех товаров.
 *
 * Ручка /local/short-link/ заводит ссылку лениво — в момент, когда приложение
 * спросило конкретный товар. Этот скрипт делает то же самое пачкой, чтобы
 * ссылка была видна в админке у всех сразу.
 *
 * Логика намеренно повторяет ручку (см. local/short-link/index.php):
 * хранилище — штатная b_short_uri, свойство лишь витрина, а при переезде
 * товара строка в таблице переписывается, чтобы код ссылки не менялся.
 *
 * Запуск из консоли:
 *     php local/tools/nd_short_uri_fill.php            — все товары
 *     php local/tools/nd_short_uri_fill.php --limit=20 — первые 20 (проверка)
 *     php local/tools/nd_short_uri_fill.php --dry      — ничего не пишет
 *
 * Скрипт идемпотентен: повторный запуск ничего не портит и не плодит коды.
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

$config = $_SERVER['DOCUMENT_ROOT'] . '/local/short-link/config.php';
if (!file_exists($config)) {
    die("Нет {$config} — скопируйте config.sample.php и подставьте токен\n");
}
require $config;

$opts = getopt('', ['limit::', 'dry']);
$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$dry = isset($opts['dry']);

$filter = ['IBLOCK_ID' => ND_SHORT_LINK_IBLOCK, 'ACTIVE' => 'Y'];
$nav = $limit > 0 ? ['nTopCount' => $limit] : false;

$res = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    $filter,
    false,
    $nav,
    ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL']
);

$total = $created = $updated = $kept = $skipped = 0;

while ($row = $res->GetNext()) {
    $total++;
    $id = (int) $row['ID'];
    $uri = $row['~DETAIL_PAGE_URL'];        // GetNext() экранирует — берём оригинал

    if ($uri === '' || $uri === null) {
        $skipped++;
        continue;
    }

    // Фильтр по коду свойства — ПЯТЫЙ аргумент, третий и четвёртый сортировка.
    $saved = CIBlockElement::GetProperty(
        ND_SHORT_LINK_IBLOCK,
        $id,
        'sort',
        'asc',
        ['CODE' => ND_SHORT_LINK_PROP]
    )->Fetch();

    // Разделитель регулярки «#»: тильда есть внутри шаблона.
    $code = '';
    if ($saved && preg_match('#/(~[0-9a-zA-Z]+)$#', (string) $saved['VALUE'], $m)) {
        $code = $m[1];
    }

    if ($code !== '') {
        $exists = CBXShortUri::GetList([], ['SHORT_URI' => $code])->Fetch();
        if (!$exists) {
            $code = '';
        } elseif ($exists['URI'] !== $uri) {
            if (!$dry) {
                CBXShortUri::Update($exists['ID'], ['URI' => $uri, 'STATUS' => 301]);
            }
            $updated++;
        }
    }

    if ($code === '') {
        if ($dry) {
            $created++;
            continue;
        }
        $code = ltrim((string) CBXShortUri::GetShortUri($uri), '/');
        if ($code === '') {
            echo "  ! {$id} — не удалось создать короткую ссылку\n";
            $skipped++;
            continue;
        }
        $created++;
    }

    $short = ND_SHORT_LINK_HOST . '/' . $code;

    if (!$saved || (string) $saved['VALUE'] !== $short) {
        if (!$dry) {
            CIBlockElement::SetPropertyValuesEx($id, ND_SHORT_LINK_IBLOCK, [ND_SHORT_LINK_PROP => $short]);
        }
    } else {
        $kept++;
    }

    if ($total % 200 === 0) {
        echo "  … {$total}\n";
    }
}

echo ($dry ? "ПРОГОН БЕЗ ЗАПИСИ\n" : '');
echo "Товаров: {$total}\n";
echo "  новых ссылок:     {$created}\n";
echo "  адрес обновлён:   {$updated}\n";
echo "  уже было:         {$kept}\n";
echo "  пропущено:        {$skipped}\n";
