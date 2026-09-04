<?php
/**
 * Разовое: строка Sitemap с картой изображений во все robots регионов.
 *
 *     php local/tools/nd_robots_add_image_sitemap.php --dry-run
 *     php local/tools/nd_robots_add_image_sitemap.php
 *
 * robots.txt на сайте отдаёт не корневой файл: .htaccess переписывает запрос
 * на /robots.php, а тот берёт aspro_regions/robots/robots_<хост>.txt — по
 * файлу на каждый из 82 поддоменов-регионов. Карту изображений
 * (sitemap_images_generate.php) роботам показывает вторая строка Sitemap, и
 * дописать её нужно в каждый файл, каждому со своим хостом.
 *
 * Скрипт идемпотентный: где строка уже есть, файл не трогаем. Поэтому его же
 * можно прогнать снова, когда добавится новый регион.
 *
 * Хвост файлов: они сохранены без завершающего перевода строки, поэтому
 * дописываем с оглядкой — иначе новая строка приклеится к предыдущей.
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only');
}

$root = realpath(__DIR__ . '/../..');
$dir = $root . '/aspro_regions/robots';
$dryRun = in_array('--dry-run', $argv, true);

if (!is_dir($dir)) {
    fwrite(STDERR, 'Нет каталога ' . $dir . PHP_EOL);
    exit(1);
}

$files = glob($dir . '/robots_*.txt');
if (!$files) {
    fwrite(STDERR, 'В ' . $dir . ' нет файлов robots_*.txt' . PHP_EOL);
    exit(1);
}

$added = 0;
$skipped = 0;
$failed = 0;

foreach ($files as $file) {
    $host = preg_replace('/^robots_(.+)\.txt$/', '$1', basename($file));
    if (!$host || $host === basename($file)) {
        echo 'Пропуск, не разобрал хост: ' . basename($file) . PHP_EOL;
        ++$failed;
        continue;
    }

    $line = 'Sitemap: https://' . $host . '/sitemap-images.xml';

    $content = file_get_contents($file);
    if ($content === false) {
        echo 'Не прочитался: ' . basename($file) . PHP_EOL;
        ++$failed;
        continue;
    }

    if (strpos($content, '/sitemap-images.xml') !== false) {
        ++$skipped;
        continue;
    }

    $eol = (strpos($content, "\r\n") !== false) ? "\r\n" : "\n";
    $suffix = (substr($content, -strlen($eol)) === $eol) ? '' : $eol;

    if ($dryRun) {
        echo 'Добавили бы в ' . basename($file) . ': ' . $line . PHP_EOL;
        ++$added;
        continue;
    }

    if (file_put_contents($file, $suffix . $line . $eol, FILE_APPEND) === false) {
        echo 'Не записался: ' . basename($file) . PHP_EOL;
        ++$failed;
        continue;
    }

    ++$added;
}

printf(
    '%s: файлов %d, дописано %d, уже было %d, с ошибкой %d%s',
    $dryRun ? 'Пробный запуск' : 'Готово',
    count($files),
    $added,
    $skipped,
    $failed,
    PHP_EOL
);

exit($failed ? 1 : 0);
