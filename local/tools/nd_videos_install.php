<?php
/**
 * Установка инфоблока «Видео» (videos) — единый источник роликов о продукции
 * (ТЗ Ирины, 28.10.2026). Логика — local/php_interface/include/latitudo_videos.php.
 *
 * Скрипт идемпотентен: что уже есть, не пересоздаётся; недостающие свойства
 * и настройки дописывает.
 *
 *     php local/tools/nd_videos_install.php --dry
 *     php local/tools/nd_videos_install.php
 *
 * Тип — существующий aspro_next_content (там же «Производители», ИБ 12).
 * Символьный код элемента — обязательный, уникальный, транслит из названия:
 * пригодится для ссылок на конкретный ролик на будущей странице «Видео».
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
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/latitudo_videos.php';

$dry = isset(getopt('', ['dry'])['dry']);

$BRANDS_IBLOCK = 12;

function say(string $s): void
{
    echo $s . "\n";
}

// --- инфоблок ---

$fieldsSettings = [
    'CODE' => [
        'IS_REQUIRED' => 'Y',
        'DEFAULT_VALUE' => [
            'UNIQUE' => 'Y',
            'TRANSLITERATION' => 'Y',
            'TRANS_LEN' => 90,
            'TRANS_CASE' => 'L',
            'TRANS_SPACE' => '-',
            'TRANS_OTHER' => '-',
            'TRANS_EAT' => 'Y',
            'USE_GOOGLE' => 'N',
        ],
    ],
];

$iblock = CIBlock::GetList([], ['TYPE' => LatitudoVideoSync::IBLOCK_TYPE, 'CODE' => LatitudoVideoSync::IBLOCK_CODE, 'CHECK_PERMISSIONS' => 'N'])->Fetch();

if ($iblock) {
    $iblockId = (int)$iblock['ID'];
    say("Инфоблок уже есть: {$iblockId} ({$iblock['NAME']})");
} elseif ($dry) {
    say('СОЗДАЛ БЫ инфоблок ' . LatitudoVideoSync::IBLOCK_CODE);
    $iblockId = 0;
} else {
    $sites = [];
    $rsSite = CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
    while ($site = $rsSite->Fetch()) {
        $sites[] = $site['LID'];
    }

    $ib = new CIBlock();
    $iblockId = (int)$ib->Add([
        'ACTIVE' => 'Y',
        'NAME' => 'Видео',
        'CODE' => LatitudoVideoSync::IBLOCK_CODE,
        'API_CODE' => 'videos',
        'IBLOCK_TYPE_ID' => LatitudoVideoSync::IBLOCK_TYPE,
        'SITE_ID' => $sites,
        'SORT' => 910,
        'LIST_PAGE_URL' => '',
        'DETAIL_PAGE_URL' => '',
        'INDEX_ELEMENT' => 'N',
        'INDEX_SECTION' => 'N',
        'VERSION' => 2,
        'GROUP_ID' => [2 => 'R'],
        'FIELDS' => $fieldsSettings,
        'DESCRIPTION' => 'Ролики о продукции — по одному на ролик, без дублей. Заводятся сами, когда у товара '
            . 'заполняют «Видео в попапе» или у предложения «Товарное видео» (новые — выключенными: проверьте '
            . 'название и превью и включите). Выводятся на странице производителя, блок «Видео о продукции».',
        'DESCRIPTION_TYPE' => 'text',
    ]);

    if (!$iblockId) {
        die('Не удалось создать инфоблок: ' . $ib->LAST_ERROR . "\n");
    }
    say("Инфоблок создан: {$iblockId}");
}

if ($iblockId && $iblock && !$dry) {
    // у созданного раньше — дописать настройки символьного кода
    CIBlock::SetFields($iblockId, array_replace(CIBlock::GetFields($iblockId), $fieldsSettings));
    say('  настройки символьного кода проверены');
}

// --- свойства ---

$props = [
    ['CODE' => 'URL', 'NAME' => 'Ссылка на ролик', 'PROPERTY_TYPE' => 'S', 'SORT' => 100,
        'HINT' => 'Исходная ссылка (Rutube, YouTube, VK). Меняется вместе с URL_NORM — дубли ищутся по нему.'],
    ['CODE' => 'URL_NORM', 'NAME' => 'Ссылка для поиска дублей', 'PROPERTY_TYPE' => 'S', 'SORT' => 110, 'FILTRABLE' => 'Y',
        'HINT' => 'Заполняется сама: хостинг и номер ролика без параметров. Руками не править.'],
    ['CODE' => 'DURATION', 'NAME' => 'Длительность (м:сс)', 'PROPERTY_TYPE' => 'S', 'SORT' => 200,
        'HINT' => 'Например 1:24. Для Rutube заполняется сама.'],
    ['CODE' => 'UPLOAD_DATE', 'NAME' => 'Дата публикации на хостинге', 'PROPERTY_TYPE' => 'S', 'SORT' => 210,
        'HINT' => 'Для микроразметки. Пусто — берётся дата создания записи.'],
    ['CODE' => 'SERIES', 'NAME' => 'Серия', 'PROPERTY_TYPE' => 'S', 'SORT' => 300,
        'HINT' => 'Бейдж на превью (Вуд-Икс, Ко-Экструзия). Пусто — определяется по названию.'],
    ['CODE' => 'PRODUCTS', 'NAME' => 'Товары', 'PROPERTY_TYPE' => 'E', 'MULTIPLE' => 'Y', 'SORT' => 400,
        'LINK_IBLOCK_ID' => LatitudoVideoSync::CATALOG_IBLOCK, 'FILTRABLE' => 'Y',
        'HINT' => 'Заполняются сами по «Видео в попапе» товаров и «Товарному видео» предложений.'],
    ['CODE' => 'BRAND', 'NAME' => 'Производитель', 'PROPERTY_TYPE' => 'E', 'SORT' => 500,
        'LINK_IBLOCK_ID' => $BRANDS_IBLOCK, 'FILTRABLE' => 'Y',
        'HINT' => 'Ставится по первому товару. Для ролика без товаров (обзор марки, монтаж) выберите вручную.'],
    // типы роликов и проекты портфолио (Ирина, 28.10.2026): фильтр на /video/, перенос — nd_videos_portfolio.php
    ['CODE' => 'TYPE', 'NAME' => 'Тип видео', 'PROPERTY_TYPE' => 'L', 'SORT' => 50, 'FILTRABLE' => 'Y',
        'HINT' => 'Ряд фильтра на странице «Видео». Пусто — «Видеообзоры продукции». На странице производителя — только обзоры с товарами этой марки.',
        'VALUES' => array_map(function ($xml, $name, $i) {
            return ['XML_ID' => $xml, 'VALUE' => $name, 'SORT' => ($i + 1) * 100];
        }, array_keys(LatitudoVideoRepository::TYPES), LatitudoVideoRepository::TYPES, array_keys(array_keys(LatitudoVideoRepository::TYPES)))],
    ['CODE' => 'PROJECT', 'NAME' => 'Проекты портфолио', 'PROPERTY_TYPE' => 'E', 'MULTIPLE' => 'Y', 'SORT' => 450,
        'LINK_IBLOCK_ID' => LatitudoVideoRepository::PORTFOLIO_IBLOCK, 'FILTRABLE' => 'Y',
        'HINT' => 'Карточки портфолио с этим роликом. У ролика без товаров в окне — ссылка на первый включённый проект.'],
];

foreach ($props as $p) {
    if (!$iblockId) {
        say('  создал бы свойство ' . $p['CODE']);
        continue;
    }
    $exists = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $p['CODE']])->Fetch();
    if ($exists) {
        say("  свойство {$p['CODE']} уже есть (id {$exists['ID']})");
        continue;
    }
    if ($dry) {
        say('  создал бы свойство ' . $p['CODE']);
        continue;
    }
    $obj = new CIBlockProperty();
    $id = $obj->Add($p + [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'MULTIPLE' => 'N',
        'IS_REQUIRED' => 'N',
    ]);
    say($id ? "  свойство {$p['CODE']} создано (id {$id})" : "  ! {$p['CODE']}: " . $obj->LAST_ERROR);
}

say($dry ? 'ПРОГОН БЕЗ ЗАПИСИ' : 'Готово.');
