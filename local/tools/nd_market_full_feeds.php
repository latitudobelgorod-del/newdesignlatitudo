<?php
/**
 * Создаёт в модуле «Маркет для продавцов» (yandex.market) прайс-листы
 * «Полный товарный фид <город>» — весь каталог по активным элементам
 * (Ирина, 10 сентября 2026).
 *
 * Каждый — копия городского прайс-листа «<город> (для вебмастера_…)» тем же
 * механизмом, что кнопка «Копировать» в админке (loadExternalReference с
 * флагом копии → addExtended): домен, HTTPS, данные магазина, поля оффера
 * остаются как настроены. Меняется:
 *   - название — «Полный товарный фид <город>»;
 *   - файл — <город>_latitudo_full.xml (рядом с *_webmaster.xml в
 *     /bitrix/catalog_export/);
 *   - отбор — никакого: «Выгружать все элементы», условия фильтра убраны
 *     (у прежних стоял отбор «Назначение фида = фид для дилеров»);
 *   - обновление — раз в сутки (86400), а не каждый час: 66 полных фидов по
 *     ~2,6 тыс. позиций ежечасно — лишняя нагрузка; автообновление по
 *     изменению товаров выключено по той же причине;
 *   - группа — «Полный товарный фид», чтобы в списке они лежали отдельно.
 * Цену в основную единицу пересчитывает обработчик
 * local/php_interface/include/latitudo_market_feed.php (по префиксу названия).
 *
 * Повторный запуск безопасен: прайс-лист с таким названием уже есть — пропуск.
 *
 * Запуск (на проде):
 *   /opt/php/8.2/bin/php local/tools/nd_market_full_feeds.php --dry-run
 *   /opt/php/8.2/bin/php local/tools/nd_market_full_feeds.php --only=107
 *   /opt/php/8.2/bin/php local/tools/nd_market_full_feeds.php
 *   ... --start   — после создания поставить первую сборку в очередь агентов
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

$dry = in_array('--dry-run', $argv, true);
$start = in_array('--start', $argv, true);
$only = null;
foreach ($argv as $a) {
    if (preg_match('/^--only=(\d+)$/', $a, $m)) {
        $only = (int)$m[1];
    }
}

const ND_FULL_PREFIX = 'Полный товарный фид';
const ND_GROUP_NAME  = 'Полный товарный фид';

$Setup = \Yandex\Market\Export\Setup\Table::class;
$Group = \Yandex\Market\Export\Setup\Internals\GroupTable::class;

// --- группа -----------------------------------------------------------------
$groupId = 0;
$g = $Group::getList(['filter' => ['=NAME' => ND_GROUP_NAME], 'select' => ['ID']])->fetch();
if ($g) {
    $groupId = (int)$g['ID'];
} elseif (!$dry) {
    $res = $Group::add(['NAME' => ND_GROUP_NAME, 'PARENT_ID' => 0]);
    if (!$res->isSuccess()) {
        fwrite(STDERR, 'Группа не создана: ' . implode('; ', $res->getErrorMessages()) . "\n");
        exit(1);
    }
    $groupId = (int)$res->getId();
    echo "Создана группа «" . ND_GROUP_NAME . "» #{$groupId}\n";
}

// --- уже созданные полные -------------------------------------------------------
$existing = [];
$rs = $Setup::getList(['filter' => ['%=NAME' => ND_FULL_PREFIX . '%'], 'select' => ['ID', 'NAME']]);
while ($row = $rs->fetch()) {
    $existing[$row['NAME']] = (int)$row['ID'];
}

// --- источники: городские «(для вебмастера…» ---------------------------------------
$filter = ['%NAME' => 'для вебмастера'];
if ($only) {
    $filter = ['=ID' => $only];
}
$sources = $Setup::getList(['filter' => $filter, 'select' => ['ID', 'NAME', 'DOMAIN', 'FILE_NAME'], 'order' => ['ID' => 'ASC']])->fetchAll();

$created = [];
foreach ($sources as $src) {
    // «Пермь (для вебмастера_181225» — у одного закрывающей скобки нет
    $city = trim(preg_replace('/\s*\(.*$/u', '', $src['NAME']));
    $name = ND_FULL_PREFIX . ' ' . $city;
    if (isset($existing[$name])) {
        echo "пропуск (уже есть #{$existing[$name]}): {$name}\n";
        continue;
    }
    $file = preg_replace('/_webmaster\.xml$/', '_full.xml', $src['FILE_NAME']);
    if ($file === $src['FILE_NAME']) {
        $file = preg_replace('/\.xml$/', '', $src['FILE_NAME']) . '_full.xml';
    }

    $data = $Setup::getRowById($src['ID']);
    $ext  = $Setup::loadExternalReference([$src['ID']], null, true);
    unset($data['ID']);
    $data += ($ext[$src['ID']] ?? []);
    unset($data['IBLOCK']); // вычисляемое, не сохраняется

    $data['NAME']           = $name;
    $data['FILE_NAME']      = $file;
    $data['REFRESH_PERIOD'] = 86400;
    $data['REFRESH_TIME']   = '04:00';
    $data['AUTOUPDATE']     = 0;
    if ($groupId) {
        $data['GROUP'] = [$groupId];
    }
    foreach ($data['IBLOCK_LINK'] as &$link) {
        $link['EXPORT_ALL'] = 1;
        $link['FILTER'] = [];
    }
    unset($link);

    if ($dry) {
        echo "[dry-run] {$name} | {$src['DOMAIN']} | {$file}\n";
        continue;
    }

    $res = $Setup::addExtended($data);
    if (!$res->isSuccess()) {
        echo "ОШИБКА {$name}: " . implode('; ', $res->getErrorMessages()) . "\n";
        continue;
    }
    $id = (int)$res->getId();
    $created[] = $id;
    echo "создан #{$id}: {$name} | {$src['DOMAIN']} | {$file}\n";
}

if ($start && $created) {
    foreach ($created as $id) {
        \Yandex\Market\Export\Run\Agent::refreshStart($id, true);
    }
    echo 'Первая сборка поставлена в очередь агентов: ' . count($created) . "\n";
}

echo 'Итого создано: ' . count($created) . ', источников: ' . count($sources) . "\n";
