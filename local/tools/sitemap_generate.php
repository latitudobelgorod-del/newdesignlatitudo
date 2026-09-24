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

/* ------------------------------------------------------------------------
   Посадочные страницы каталога — sitemap-landings.xml (24 сентября 2026).

   Модуль seo про них не знает: посадочная — это красивый адрес из ЧПУ Сотбита
   (b_sotbit_seometa_chpu), а не раздел и не элемент. В карте не было ни одной
   из 74 («Полнотелые ступени», «Штакетник из ДПК», цвета садовой мебели…) —
   Яндекс находил их только по ссылкам.

   В карту берём лишь то, что робот должен проиндексировать: каждую страницу
   спрашиваем у самого сайта и оставляем ответ 200 без noindex и с canonical на
   себя (или без canonical). Так в карту не попадут посадочные, которые
   уходят 301 (бренд → раздел бренда) или отвечают 404.

   Индекс sitemap.xml модуль seo переписывает при каждой генерации, поэтому
   строку о нашем файле дописываем в него сразу после генерации, здесь же.
   Региональные домены получают файл через sitemap_handler1.php с заменой
   адреса, как и остальные карты.
   ------------------------------------------------------------------------ */
const ND_LANDINGS_FILE = 'sitemap-landings.xml';
const ND_LANDINGS_HOST = 'https://latitudo.ru';

/**
 * Годится ли посадочная для карты: 200, нет noindex, canonical на себя.
 *
 * @return string '' — годится, иначе причина
 */
function ndLandingCheck(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'LatitudoSitemap/1.0',
    ]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        return 'ответ ' . $code;
    }
    if (preg_match('~<meta\s+name="robots"\s+content="([^"]*)"~i', $body, $m) && stripos($m[1], 'noindex') !== false) {
        return 'noindex';
    }
    if (preg_match('~<link\s+rel="canonical"\s+href="([^"]*)"~i', $body, $m)) {
        $canonical = rtrim((string) parse_url($m[1], PHP_URL_PATH), '/') . '/';
        $self = rtrim((string) parse_url($url, PHP_URL_PATH), '/') . '/';
        if ($canonical !== $self) {
            return 'canonical на ' . $m[1];
        }
    }
    return '';
}

function ndBuildLandingsSitemap(string $root): bool
{
    $conn = \Bitrix\Main\Application::getConnection();
    try {
        $rows = $conn->query(
            "SELECT NEW_URL, DATE_CHANGE FROM b_sotbit_seometa_chpu WHERE ACTIVE = 'Y' AND NEW_URL <> '' ORDER BY NEW_URL"
        )->fetchAll();
    } catch (\Exception $e) {
        fwrite(STDERR, '  посадочные: не прочитана таблица Сотбита — ' . $e->getMessage() . "\n");
        return false;
    }

    $urls = [];
    $skipped = [];
    foreach ($rows as $row) {
        $path = '/' . trim((string) $row['NEW_URL'], '/') . '/';
        $url = ND_LANDINGS_HOST . $path;
        if (isset($urls[$url])) {
            continue;
        }
        $reason = ndLandingCheck($url);
        if ($reason !== '') {
            $skipped[] = $path . ' — ' . $reason;
            continue;
        }
        $date = $row['DATE_CHANGE'] instanceof \Bitrix\Main\Type\DateTime
            ? $row['DATE_CHANGE']->getTimestamp()
            : time();
        $urls[$url] = date('c', $date);
    }

    foreach ($skipped as $line) {
        say('  посадочная не в карте: ' . $line);
    }

    /* Сайт не ответил ни разу (ночной сбой) — вчерашний файл лучше пустого. */
    $file = $root . '/' . ND_LANDINGS_FILE;
    if (!$urls && is_file($file)) {
        fwrite(STDERR, "  посадочные: ни одна не ответила 200, оставляю прежний " . ND_LANDINGS_FILE . "\n");
        return false;
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $url => $lastmod) {
        $xml .= '<url><loc>' . htmlspecialchars($url, ENT_XML1) . '</loc><lastmod>' . $lastmod
            . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>';
    }
    $xml .= '</urlset>';

    /* Через временный файл: робот не должен застать карту наполовину записанной. */
    $tmp = $file . '.tmp';
    if (file_put_contents($tmp, $xml) === false || !rename($tmp, $file)) {
        fwrite(STDERR, '  посадочные: не удалось записать ' . ND_LANDINGS_FILE . "\n");
        return false;
    }

    /* Строка о файле в индексе. Индекс только что переписан модулем seo. */
    $index = $root . '/sitemap.xml';
    $content = is_file($index) ? (string) file_get_contents($index) : '';
    $loc = ND_LANDINGS_HOST . '/' . ND_LANDINGS_FILE;
    if ($content !== '' && strpos($content, $loc) === false && strpos($content, '</sitemapindex>') !== false) {
        $entry = '<sitemap><loc>' . $loc . '</loc><lastmod>' . date('c') . '</lastmod></sitemap>';
        $content = str_replace('</sitemapindex>', $entry . '</sitemapindex>', $content);
        $tmp = $index . '.tmp';
        if (file_put_contents($tmp, $content) === false || !rename($tmp, $index)) {
            fwrite(STDERR, "  посадочные: не удалось дописать sitemap.xml\n");
            return false;
        }
    }

    say(sprintf('  посадочные: %d в карте, %d пропущено', count($urls), count($skipped)));
    return true;
}

if (!$onlyId && !ndBuildLandingsSitemap($_SERVER['DOCUMENT_ROOT'])) {
    $hadError = true;
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
