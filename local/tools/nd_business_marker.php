<?php
/**
 * Отметка товаров для фида Яндекс Бизнеса.
 *
 * Используется то же свойство каталога, что и у остальных выгрузок, —
 * «Назначение фида» (LIST_PRISE): в него добавляется значение «Яндекс Бизнес».
 * Прежние значения (полный фид, брендовые фиды) не затрагиваются — новое
 * дописывается к уже отмеченным. Отмечать можно в одном месте: карточка
 * товара → вкладка «Для прайс-листа».
 *
 * Запуск:
 *   php local/tools/nd_business_marker.php --dry-run
 *   php local/tools/nd_business_marker.php                  # отметить EasyDecking + LATITUDO
 *   php local/tools/nd_business_marker.php --mark-all       # весь активный каталог
 *   php local/tools/nd_business_marker.php --unmark         # снять только отметку Бизнеса
 *   php local/tools/nd_business_marker.php --remove-old     # удалить прежнее свойство LIST_BUSINESS
 *
 * Повторный запуск безопасен: значение и отметки не дублируются.
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

const IB_PRODUCTS     = 19;
const PROP_BRAND      = 134;
const PROP_CODE       = 'LIST_PRISE';
const PROP_NAME       = 'Назначение фида';
const ENUM_VALUE      = 'Яндекс Бизнес';
const ENUM_XML_ID     = 'yandex-business';
const OLD_PROP_CODE   = 'LIST_BUSINESS';
const BRANDS          = [26365 => 'EasyDecking', 24936 => 'LATITUDO'];

$dry      = in_array('--dry-run', $argv ?? [], true);
$markAll  = in_array('--mark-all', $argv ?? [], true);
$unmark   = in_array('--unmark', $argv ?? [], true);
$removeOld = in_array('--remove-old', $argv ?? [], true);

$conn = \Bitrix\Main\Application::getConnection();

// --- Свойство «Назначение фида» ------------------------------------------
$prop = $conn->query(
    "SELECT ID, NAME FROM b_iblock_property
      WHERE IBLOCK_ID = " . IB_PRODUCTS . " AND CODE = '" . PROP_CODE . "'"
)->fetch();
if (!$prop) {
    fwrite(STDERR, "Не найдено свойство " . PROP_CODE . " в инфоблоке " . IB_PRODUCTS . "\n");
    exit(1);
}
$propId = (int)$prop['ID'];
echo "свойство: #{$propId} «{$prop['NAME']}»\n";

$enum = $conn->query(
    "SELECT ID, VALUE FROM b_iblock_property_enum
      WHERE PROPERTY_ID = {$propId} AND XML_ID = '" . ENUM_XML_ID . "'"
)->fetch();
$enumId = $enum ? (int)$enum['ID'] : 0;
if ($enumId) {
    echo "значение уже есть: #{$enumId} «{$enum['VALUE']}»\n";
} elseif ($dry) {
    echo "[dry-run] значение «" . ENUM_VALUE . "» будет добавлено\n";
} else {
    $newId = CIBlockPropertyEnum::Add([
        'PROPERTY_ID' => $propId,
        'VALUE'       => ENUM_VALUE,
        'XML_ID'      => ENUM_XML_ID,
        'SORT'        => 520,
        'DEF'         => 'N',
    ]);
    if (!$newId) {
        fwrite(STDERR, "не добавить значение «" . ENUM_VALUE . "»\n");
        exit(1);
    }
    $enumId = (int)$newId;
    echo "добавлено значение #{$enumId} «" . ENUM_VALUE . "»\n";
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
printf("товаров %s: %d\n", $markAll ? 'во всём активном каталоге' : 'EasyDecking + LATITUDO', count($ids));

if ($dry) {
    $rs = CIBlockElement::GetList([], ['ID' => array_slice($ids, 0, 3)], false, false, ['ID', 'NAME']);
    while ($row = $rs->GetNext()) {
        echo "  пример: #{$row['ID']} {$row['NAME']}\n";
    }
    exit(0);
}

// Текущие значения свойства у этих товаров — чтобы дописать, а не затереть.
$current = [];
foreach (array_chunk($ids, 500) as $chunk) {
    $in = implode(',', $chunk);
    $rows = $conn->query(
        "SELECT IBLOCK_ELEMENT_ID id, VALUE v FROM b_iblock_element_property
          WHERE IBLOCK_PROPERTY_ID = {$propId} AND IBLOCK_ELEMENT_ID IN ({$in})"
    );
    while ($row = $rows->fetch()) {
        $current[(int)$row['id']][] = (int)$row['v'];
    }
}

$done = 0;
$skipped = 0;
foreach ($ids as $id) {
    $have = $current[$id] ?? [];
    if ($unmark) {
        if (!in_array($enumId, $have, true)) {
            $skipped++;
            continue;
        }
        $values = array_values(array_diff($have, [$enumId]));
        CIBlockElement::SetPropertyValuesEx($id, IB_PRODUCTS, [PROP_CODE => $values]);
        $done++;
        continue;
    }
    if (in_array($enumId, $have, true)) {
        $skipped++;
        continue;
    }
    $values = array_values(array_unique(array_merge($have, [$enumId])));
    CIBlockElement::SetPropertyValuesEx($id, IB_PRODUCTS, [PROP_CODE => $values]);
    $done++;
    if ($done % 100 === 0) {
        echo "  … {$done}\n";
    }
}

printf("%s: изменено %d, без изменений %d, всего %d\n",
    $unmark ? 'Снято' : 'Готово', $done, $skipped, count($ids));

// --- Старое отдельное свойство -------------------------------------------
if ($removeOld) {
    $old = $conn->query(
        "SELECT ID FROM b_iblock_property WHERE IBLOCK_ID = " . IB_PRODUCTS . " AND CODE = '" . OLD_PROP_CODE . "'"
    )->fetch();
    if (!$old) {
        echo "прежнее свойство " . OLD_PROP_CODE . " уже отсутствует\n";
    } else {
        CIBlockProperty::Delete((int)$old['ID']);
        echo "удалено прежнее свойство " . OLD_PROP_CODE . " #{$old['ID']}\n";
    }
}

// --- Контроль -------------------------------------------------------------
$rows = $conn->query(
    "SELECT e.VALUE, COUNT(*) c
       FROM b_iblock_element_property p
       JOIN b_iblock_property_enum e ON e.ID = p.VALUE
      WHERE p.IBLOCK_PROPERTY_ID = {$propId}
      GROUP BY e.ID, e.VALUE"
);
while ($row = $rows->fetch()) {
    echo "  «{$row['VALUE']}»: {$row['c']}\n";
}
