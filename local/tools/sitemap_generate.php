<?php
/**
 * Пересборка карты сайта из командной строки — для крона.
 *
 *     php local/tools/sitemap_generate.php
 *     php local/tools/sitemap_generate.php --id=5     # только одна настройка
 *     php local/tools/sitemap_generate.php --quiet    # без вывода, только ошибки
 *
 * Зачем. Файлы sitemap*.xml в корне пересобирает модуль seo, и до 4 сентября
 * 2026 запускать это можно было только руками из админки. За полгода карта
 * устарела: в ней остались адреса удалённых товаров (в выборке из 25 — два
 * ответили 404), из-за чего Яндекс.Вебмастер писал об ошибках в файлах Sitemap.
 *
 * Как работает. Генерация у модуля пошаговая (Bitrix\Seo\Sitemap\Job): в админке
 * шаги крутит ajax, в фоне — агент. Здесь тот же цикл, только в лоб: дергаем
 * doJobAgent(), пока он возвращает имя агента (значит, работа не закончена).
 *
 * Региональные копии (sitemap_handler1.php) пересобирать не нужно: его кеш
 * теперь сверяется с датой исходного файла и обновляется сам при первом
 * запросе после генерации.
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only');
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$opts = getopt('', ['id::', 'quiet']);
$quiet = isset($opts['quiet']);
$onlyId = isset($opts['id']) ? (int) $opts['id'] : 0;

/** Шагов на одну карту с запасом: 2400 товаров укладываются в десятки шагов. */
const ND_SITEMAP_MAX_STEPS = 5000;
/** Дольше этого не крутим — чтобы кроновый процесс не жил вечно при поломке. */
const ND_SITEMAP_MAX_SECONDS = 1800;

function say(string $text): void
{
    if (!$GLOBALS['quiet']) {
        echo $text . PHP_EOL;
    }
}

function fail(string $text): void
{
    fwrite(STDERR, $text . PHP_EOL);
    exit(1);
}

if (!CModule::IncludeModule('seo')) {
    fail('Модуль seo не установлен');
}

use Bitrix\Seo\Sitemap\Job;
use Bitrix\Seo\Sitemap\Internals\SitemapTable;

$rows = SitemapTable::getList([
    'select' => ['ID', 'NAME', 'ACTIVE', 'SITE_ID', 'DATE_RUN'],
    'filter' => $onlyId > 0 ? ['=ID' => $onlyId] : ['=ACTIVE' => 'Y'],
])->fetchAll();

if (!$rows) {
    fail($onlyId > 0 ? "Настройка карты сайта #{$onlyId} не найдена" : 'Активных настроек карты сайта нет');
}

$startedAll = microtime(true);
$hadError = false;

foreach ($rows as $row) {
    $id = (int) $row['ID'];
    say("Карта #{$id} «{$row['NAME']}» (сайт {$row['SITE_ID']})");

    /* Незавершённая работа с прошлого раза (например, кроновый процесс убили)
       мешает начать заново — снимаем её и заводим свежую. */
    Job::clearBySitemap($id);

    $job = Job::addJob($id);
    if (!$job) {
        fwrite(STDERR, "  не удалось создать задание для карты #{$id}\n");
        $hadError = true;
        continue;
    }

    $started = microtime(true);
    $steps = 0;

    /* doJobAgent() возвращает имя агента, пока есть что делать, и пустую строку,
       когда работа закончена (или сорвалась) — тот же признак, по которому
       Битрикс решает, перезапускать агента или нет. */
    while (Job::doJobAgent($id) !== '') {
        $steps++;

        if ($steps >= ND_SITEMAP_MAX_STEPS) {
            fwrite(STDERR, "  карта #{$id}: превышен предел в " . ND_SITEMAP_MAX_STEPS . " шагов\n");
            $hadError = true;
            break;
        }
        if (microtime(true) - $started > ND_SITEMAP_MAX_SECONDS) {
            fwrite(STDERR, "  карта #{$id}: превышен предел в " . ND_SITEMAP_MAX_SECONDS . " секунд\n");
            $hadError = true;
            break;
        }
    }

    say(sprintf('  шагов: %d, время: %.1f с', $steps, microtime(true) - $started));
}

/* Короткая сводка по файлам: по ней в логе крона сразу видно, что карта
   действительно переписалась и не опустела. */
$root = $_SERVER['DOCUMENT_ROOT'];
foreach (glob($root . '/sitemap*.xml') ?: [] as $file) {
    $size = filesize($file);
    $urls = substr_count((string) file_get_contents($file), '<loc>');
    say(sprintf('  %-28s %8d байт  %5d адресов  %s', basename($file), $size, $urls, date('d.m.Y H:i', filemtime($file))));
}

say(sprintf('Готово за %.1f с', microtime(true) - $startedAll));

exit($hadError ? 1 : 0);
