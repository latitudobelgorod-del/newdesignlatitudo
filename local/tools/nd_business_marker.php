<?php
/**
 * Свойство «Выгрузка в Яндекс Бизнес» в каталоге товаров и отметка товаров
 * EasyDecking и LATITUDO для нового фида.
 *
 * Зачем: фид для карточки компании в Яндекс Бизнесе — не весь каталог, а только
 * продукция этих брендов. Отметка живёт в админке (товар → вкладка «Для
 * прайс-листа»), как «Назначение фида», чтобы состав можно было менять руками.
 *
 * Запуск:
 *   php local/tools/nd_business_marker.php --dry-run
 *   php local/tools/nd_business_marker.php                 # создать свойство и отметить бренды
 *   php local/tools/nd_business_marker.php --mark-all      # отметить весь активный каталог
 *   php local/tools/nd_business_marker.php --unmark        # снять отметки
 *
 * Повторный запуск безопасен: свойство ищется по коду, уже отмеченные не дублируются.
 */

$SITE_ROOT = dirname(__DIR__, 2);
$_SERVER['DOCUMENT_ROOT']  = $SITE_ROOT;
$_SERVER['HTTP_HOST']      = 'latitudo.ru';
$_SERVER['SERVER_NAME']    = 'latitudo.ru';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/';

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('DisableEventsCheck', true);
define('NO_AGENT_CHECK', true);

require $SITE_ROOT . '/bitrix/modules/main/include/prolog_before.php';
if (!CModule::IncludeModule('iblock')) {
    fwrite(STDERR, "Не подключился модуль iblock\n");
    exit(1);
}
@set_time_limit(0);

const IB_PRODUCTS   = 19;
const PROP_BRAND    = 134;
const PROP_CODE     = 'LIST_BUSINESS';
const PROP_NAME     = 'Выгрузка в Яндекс Бизнес';
const ENUM_VALUE    = 'Да';
const ENUM_XML_ID   = 'da';
const BRANDS        = [26365 => 'EasyDecking', 24936 => 'LATITUDO'];

$dry    = in_array('--dry-run', $argv ?? [], true);
$markAll = in_array('--mark-all', $argv ?? [], true);
$unmark  = in_array('--unmark', $argv ?? [], true);

// --- Свойство -------------------------------------------------------------
$propId = 0;
$enumId = 0;
$rs = CIBlockProperty::GetList([], ['IBLOCK_ID' => IB_PRODUCTS, 'CODE' => PROP_CODE]);
if ($p = $rs->Fetch()) {
    $propId = (int)$p['ID'];
    echo "свойство уже есть: #{$propId} {$p['NAME']}\n";
} elseif (!$dry) {
    $prop = new CIBlockProperty();
    $res = $prop->Add([
        'IBLOCK_ID'     => IB_PRODUCTS,
        'NAME'          => PROP_NAME,
        'CODE'          => PROP_CODE,
        'PROPERTY_TYPE' => 'L',
        'MULTIPLE'      => 'Y',
        'ACTIVE'        => 'Y',
        'SORT'          => 510,
        'VALUES'        => [[
            'VALUE'  => ENUM_VALUE,
            'DEF'    => 'N',
            'SORT'   => 500,
            'XML_ID' => ENUM_XML_ID,
        ]],
    ]);
    if (!$res) {
        fwrite(STDERR, "не создать свойство: " . $prop->LAST_ERROR . "\n");
        exit(1);
    }
    $propId = (int)$res;
    echo "создано свойство #{$propId} «" . PROP_NAME . "»\n";
} else {
    echo "[dry-run] свойство будет создано\n";
}

if ($propId) {
    $rs = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $propId]);
    while ($e = $rs->Fetch()) {
        if ($e['VALUE'] === ENUM_VALUE) {
            $enumId = (int)$e['ID'];
        }
    }
    if (!$enumId && !$dry) {
        fwrite(STDERR, "не нашлось значение «" . ENUM_VALUE . "» у свойства #{$propId}\n");
        exit(1);
    }
}

// --- Товары ---------------------------------------------------------------
$filter = ['IBLOCK_ID' => IB_PRODUCTS, 'ACTIVE' => 'Y'];
if (!$markAll) {
    $filter['PROPERTY_BRAND'] = array_keys(BRANDS);
}
$ids = [];
$rs = CIBlockElement::GetList([], $filter, false, false, ['ID', 'NAME']);
while ($row = $rs->GetNext()) {
    $ids[] = (int)$row['ID'];
}
printf("товаров к отметке: %d (%s)\n", count($ids), $markAll ? 'весь активный каталог' : 'EasyDecking + LATITUDO');

if ($dry) {
    if ($ids) {
        $rs = CIBlockElement::GetList([], ['ID' => array_slice($ids, 0, 3)], false, false, ['ID', 'NAME']);
        while ($row = $rs->GetNext()) {
            echo "  пример: #{$row['ID']} {$row['NAME']}\n";
        }
    }
    exit(0);
}

$done = 0;
$skipped = 0;
foreach ($ids as $id) {
    if ($unmark) {
        CIBlockElement::SetPropertyValuesEx($id, IB_PRODUCTS, [PROP_CODE => false]);
        $done++;
        continue;
    }
    $values = [];
    CIBlockElement::GetPropertyValuesArray($values, IB_PRODUCTS, ['ID' => [$id]], ['ID' => [$propId]]);
    if (!empty($values[$id][$propId]['VALUE'])) {
        $skipped++;
        continue;
    }
    // MULTIPLE=L: значения передаются массивом ID вариантов списка.
    CIBlockElement::SetPropertyValuesEx($id, IB_PRODUCTS, [PROP_CODE => [$enumId]]);
    $done++;
    if ($done % 100 === 0) {
        echo "  … отмечено {$done}\n";
    }
}

printf("%s: отмечено %d, уже были отмечены %d, всего %d\n",
    $unmark ? 'Снято' : 'Готово', $done, $skipped, count($ids));
