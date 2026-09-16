<?php
/**
 * Создаёт в модуле «Маркет для продавцов» (yandex.market) брендовые
 * прайс-листы по всем городам (Ирина, 15 сентября 2026):
 *   «Фид бренд EasyDecking <город>» — товары с отметкой «Назначение фида»
 *     (LIST_PRISE, свойство 430) = 990 «фид бренд EasyDecking»;
 *   «Фид бренд LATITUDO <город>»    — отметка 985 «фид бренд LATITUDO».
 *
 * Каждый — копия «Полный товарный фид <город>» (local/tools/nd_market_full_feeds.php)
 * тем же механизмом, что кнопка «Копировать» в админке: домен, HTTPS, данные
 * магазина, поля оффера, обновление раз в сутки — как у полного. Меняется:
 *   - название и группа (своя папка на бренд);
 *   - файл — <город>_latitudo_full.xml → <город>_latitudo_brand_<бренд>.xml;
 *   - отбор — в условии «Назначение фида in [999]» значение 999 заменяется
 *     на отметку бренда. Активность модуль проверяет сам.
 * Цену в основной единице и наполнение карточки даёт тот же обработчик
 * local/php_interface/include/latitudo_market_feed.php (по префиксу названия).
 *
 * Повторный запуск безопасен: прайс-лист с таким названием уже есть — пропуск.
 *
 * Запуск (на проде):
 *   php local/tools/nd_market_brand_feeds.php --brand=easydecking --dry-run
 *   php local/tools/nd_market_brand_feeds.php --brand=easydecking
 *   php local/tools/nd_market_brand_feeds.php --brand=latitudo
 * Первая сборка и регистрация агента — ~/nd_run_full.php <id> (как «Запустить»).
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

if (!Loader::includeModule('yandex.market')) {
    fwrite(STDERR, "Модуль yandex.market не подключён\n");
    exit(1);
}

const ND_SOURCE_PREFIX = 'Полный товарный фид';
const ND_PROP_FIELD    = 'iblock_element_property.430';
const ND_SOURCE_VALUE  = '999';

$brands = [
    'easydecking' => ['NAME' => 'Фид бренд EasyDecking', 'VALUE' => '990', 'FILE' => 'brand_easydecking'],
    'latitudo'    => ['NAME' => 'Фид бренд LATITUDO',    'VALUE' => '985', 'FILE' => 'brand_latitudo'],
];

$dry = in_array('--dry-run', $argv, true);
$brandKey = null;
foreach ($argv as $a) {
    if (preg_match('/^--brand=(\w+)$/', $a, $m)) {
        $brandKey = $m[1];
    }
}
if (!isset($brands[$brandKey])) {
    fwrite(STDERR, "Укажите --brand=easydecking или --brand=latitudo\n");
    exit(1);
}
$brand = $brands[$brandKey];

$Setup = \Yandex\Market\Export\Setup\Table::class;
$Group = \Yandex\Market\Export\Setup\Internals\GroupTable::class;
$db = \Bitrix\Main\Application::getConnection();
$sql = $db->getSqlHelper();

// --- группа (папка в списке прайс-листов) ---------------------------------
$groupId = 0;
$g = $Group::getList(['filter' => ['=NAME' => $brand['NAME']], 'select' => ['ID']])->fetch();
if ($g) {
    $groupId = (int)$g['ID'];
} elseif (!$dry) {
    $res = $Group::add(['NAME' => $brand['NAME'], 'PARENT_ID' => 0]);
    if (!$res->isSuccess()) {
        fwrite(STDERR, 'Группа не создана: ' . implode('; ', $res->getErrorMessages()) . "\n");
        exit(1);
    }
    $groupId = (int)$res->getId();
    echo "Создана группа «{$brand['NAME']}» #{$groupId}\n";
}

// --- уже созданные ----------------------------------------------------------
$existing = [];
$rs = $Setup::getList(['filter' => ['%=NAME' => $brand['NAME'] . '%'], 'select' => ['ID', 'NAME']]);
while ($row = $rs->fetch()) {
    $existing[$row['NAME']] = (int)$row['ID'];
}

// --- источники: полные фиды городов ---------------------------------------------
$sources = $Setup::getList([
    'filter' => ['%=NAME' => ND_SOURCE_PREFIX . '%'],
    'select' => ['ID', 'NAME', 'DOMAIN', 'FILE_NAME'],
    'order'  => ['ID' => 'ASC'],
])->fetchAll();

/** Условие отбора «Назначение фида» у прайс-листа: [ID условия => VALUE]. */
$conditions = function (int $setupId) use ($db): array {
    $out = [];
    $rs = $db->query("SELECT c.ID, c.VALUE FROM yamarket_export_filtercondition c
        JOIN yamarket_export_filter f ON f.ID = c.FILTER_ID AND f.ENTITY_TYPE = 'iblock_link'
        JOIN yamarket_export_iblocklink l ON l.ID = f.ENTITY_ID
        WHERE l.SETUP_ID = {$setupId} AND c.FIELD = '" . ND_PROP_FIELD . "'");
    while ($r = $rs->fetch()) {
        $out[(int)$r['ID']] = $r['VALUE'];
    }
    return $out;
};

$created = [];
foreach ($sources as $src) {
    $city = trim(mb_substr($src['NAME'], mb_strlen(ND_SOURCE_PREFIX)));
    $name = $brand['NAME'] . ' ' . $city;
    if (isset($existing[$name])) {
        echo "пропуск (уже есть #{$existing[$name]}): {$name}\n";
        continue;
    }

    // У источника отбор должен быть ровно «in [999]» — иначе копия отберёт не то.
    $srcCond = $conditions((int)$src['ID']);
    if (count($srcCond) !== 1 || unserialize(reset($srcCond)) !== [ND_SOURCE_VALUE]) {
        echo "ПРОПУСК {$src['NAME']} (#{$src['ID']}): отбор не «in [999]» — " . json_encode(array_values($srcCond)) . "\n";
        continue;
    }

    $file = preg_replace('/_full\.xml$/', '_' . $brand['FILE'] . '.xml', $src['FILE_NAME']);
    if ($file === $src['FILE_NAME']) {
        $file = preg_replace('/\.xml$/', '', $src['FILE_NAME']) . '_' . $brand['FILE'] . '.xml';
    }

    if ($dry) {
        echo "[dry-run] {$name} | {$src['DOMAIN']} | {$file}\n";
        continue;
    }

    $data = $Setup::getRowById($src['ID']);
    $ext  = $Setup::loadExternalReference([$src['ID']], null, true);
    unset($data['ID']);
    $data += ($ext[$src['ID']] ?? []);
    unset($data['IBLOCK']); // вычисляемое, не сохраняется

    $data['NAME']      = $name;
    $data['FILE_NAME'] = $file;
    $data['GROUP']     = $groupId ? [$groupId] : [];

    $res = $Setup::addExtended($data);
    if (!$res->isSuccess()) {
        echo "ОШИБКА {$name}: " . implode('; ', $res->getErrorMessages()) . "\n";
        continue;
    }
    $id = (int)$res->getId();

    // Отбор скопировался вместе с прайс-листом — меняем значение на бренд.
    $cond = $conditions($id);
    if (count($cond) !== 1) {
        echo "ВНИМАНИЕ #{$id} {$name}: условий отбора " . count($cond) . " — проверьте в админке\n";
    }
    foreach ($cond as $cid => $v) {
        $db->query("UPDATE yamarket_export_filtercondition SET VALUE = '"
            . $sql->forSql(serialize([$brand['VALUE']])) . "' WHERE ID = {$cid}");
    }

    $created[] = $id;
    echo "создан #{$id}: {$name} | {$src['DOMAIN']} | {$file}\n";
}

echo 'Итого создано: ' . count($created) . ', источников: ' . count($sources) . "\n";
if ($created) {
    echo 'ID: ' . implode(' ', $created) . "\n";
}
