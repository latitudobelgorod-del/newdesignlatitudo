<?php
/**
 * Установка сервиса сквозного баннера: инфоблок, свойства и единственная запись.
 *
 * Скрипт идемпотентен — гоняется на каждой среде (локаль, regrutest, прод) и
 * повторный запуск ничего не портит: что уже есть, то не пересоздаётся.
 *
 *     php local/tools/nd_banner_install.php
 *     php local/tools/nd_banner_install.php --dry
 *
 * Почему инфоблок, а не своя таблица: в проекте весь контент живёт в
 * инфоблоках, у них готовая админка, права, кеш с тегами и загрузка файлов.
 * Тип берём существующий (aspro_next_content), новый заводить незачем.
 *
 * Даты показа — ШТАТНЫЕ «Начало/Окончание активности» элемента, а не свои
 * свойства. Так требует пункт про кеш: у своих свойств Битрикс не знает, что
 * баннер протух, и просроченный продолжал бы висеть из кеша до истечения TTL.
 * У штатных дат это решает сам движок — выборка с ACTIVE_DATE => 'Y' плюс
 * агент, сбрасывающий теги при смене активности по дате.
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

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/latitudo_banner.php';

$opts = getopt('', ['dry']);
$dry = isset($opts['dry']);

$IBLOCK_TYPE = 'aspro_next_content';
$IBLOCK_CODE = LatitudoBanner::IBLOCK_CODE;
$CATALOG_IBLOCK = 19;

function say(string $s): void
{
    echo $s . "\n";
}

// --- инфоблок ---

$iblock = CIBlock::GetList([], ['TYPE' => $IBLOCK_TYPE, 'CODE' => $IBLOCK_CODE, 'CHECK_PERMISSIONS' => 'N'])->Fetch();

if ($iblock) {
    $iblockId = (int) $iblock['ID'];
    say("Инфоблок уже есть: {$iblockId} ({$iblock['NAME']})");
} elseif ($dry) {
    say('СОЗДАЛ БЫ инфоблок ' . $IBLOCK_CODE);
    $iblockId = 0;
} else {
    $sites = [];
    $rsSite = CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
    while ($site = $rsSite->Fetch()) {
        $sites[] = $site['LID'];
    }

    $ib = new CIBlock();
    $iblockId = (int) $ib->Add([
        'ACTIVE' => 'Y',
        'NAME' => 'Сквозной баннер',
        'CODE' => $IBLOCK_CODE,
        'IBLOCK_TYPE_ID' => $IBLOCK_TYPE,
        'SITE_ID' => $sites,
        'SORT' => 900,
        'LIST_PAGE_URL' => '',
        'DETAIL_PAGE_URL' => '',
        'INDEX_ELEMENT' => 'N',
        'INDEX_SECTION' => 'N',
        'VERSION' => 2,
        'DESCRIPTION' => 'Единственная запись текущего сквозного баннера. '
            . 'Новый баннер запускается заменой данных в этой записи, а не созданием второй. '
            . 'Даты показа — «Начало/Окончание активности» элемента.',
        'DESCRIPTION_TYPE' => 'text',
    ]);

    if (!$iblockId) {
        die('Не удалось создать инфоблок: ' . $ib->LAST_ERROR . "\n");
    }
    say("Инфоблок создан: {$iblockId}");
}

// --- свойства ---

$props = [
    [
        'CODE' => 'LINK',
        'NAME' => 'Ссылка с баннера',
        'PROPERTY_TYPE' => 'S',
        'SORT' => 100,
        'HINT' => 'Куда ведёт баннер. Пусто — картинка выводится без ссылки.',
    ],
    [
        'CODE' => 'HOME_BANNER',
        'NAME' => 'Баннер для главной страницы',
        'PROPERTY_TYPE' => 'F',
        'SORT' => 200,
        'FILE_TYPE' => 'jpg, jpeg, png, gif, webp, svg',
        'HINT' => 'Широкая картинка под блоком категорий на главной. Пусто — на главной баннера нет.',
    ],
    [
        'CODE' => 'CATALOG_BANNER',
        'NAME' => 'Баннер для каталога',
        'PROPERTY_TYPE' => 'F',
        'SORT' => 300,
        'FILE_TYPE' => 'jpg, jpeg, png, gif, webp, svg',
        'HINT' => 'Картинка в сетке товаров, размером с карточку. Пусто — в каталоге баннера нет.',
    ],
    [
        'CODE' => 'CATALOG_SECTIONS',
        'NAME' => 'Разделы каталога',
        'PROPERTY_TYPE' => 'G',
        'MULTIPLE' => 'Y',
        'SORT' => 400,
        'LINK_IBLOCK_ID' => $CATALOG_IBLOCK,
        'HINT' => 'В каких разделах показывать баннер каталога. Подразделы выбранного включаются сами.',
    ],
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

// --- единственная запись ---

if ($iblockId) {
    $el = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'NAME'])->Fetch();
    if ($el) {
        say("Запись баннера уже есть: {$el['ID']} ({$el['NAME']})");
    } elseif ($dry) {
        say('СОЗДАЛ БЫ запись «Основной сквозной баннер»');
    } else {
        $obj = new CIBlockElement();
        /* Заводим ВЫКЛЮЧЕННОЙ: пока контент-менеджер не положит картинки,
           показывать нечего, а пустой активный баннер сбивал бы с толку. */
        $id = $obj->Add([
            'IBLOCK_ID' => $iblockId,
            'NAME' => 'Основной сквозной баннер',
            'ACTIVE' => 'N',
            'SORT' => 500,
        ]);
        say($id ? "Запись создана: {$id} (выключена — заполните картинки и включите)" : 'Не удалось создать запись: ' . $obj->LAST_ERROR);
    }
}

say($dry ? 'ПРОГОН БЕЗ ЗАПИСИ' : 'Готово.');
