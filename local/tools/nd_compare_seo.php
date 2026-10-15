<?php
/**
 * Сравнение товаров /compare/: закрыть от поисковиков на время скрытого теста
 * (ТЗ Ирины 15.10.2026) и после открытия.
 *   - robots.txt (основной и все aspro_regions/robots/robots_<город>.txt):
 *     строка «Disallow: /compare/» после «Disallow: /*ADD_TO_COMPARE_LIST»;
 *   - карта сайта (модуль seo): папка /compare исключена — иначе ночная
 *     пересборка (крон 02:30) взяла бы её в sitemap-files.xml, корень там
 *     включён целиком.
 * Параметры добавления и удаления — POST в /local/ajax/compare.php, в адресах их нет.
 *
 *   php nd_compare_seo.php <DOCROOT> [--apply]
 *   php nd_compare_seo.php <DOCROOT> rollback <файл бэкапа>
 * Без --apply — только показывает. Бэкап — в домашнюю папку (вне www).
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix')) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('seo');

const ND_LINE = 'Disallow: /compare/';
const ND_AFTER = 'Disallow: /*ADD_TO_COMPARE_LIST';
$root = $_SERVER['DOCUMENT_ROOT'];
$home = getenv('HOME') ?: dirname($root);

if (($argv[2] ?? '') === 'rollback') {
	$bk = json_decode(file_get_contents($argv[3]), true);
	foreach ($bk['robots'] as $file => $content) {
		file_put_contents($root . $file, base64_decode($content));
	}
	foreach ($bk['sitemaps'] as $id => $settings) {
		\Bitrix\Seo\SitemapTable::update((int)$id, ['SETTINGS' => $settings]);
	}
	exit('откат: robots ' . count($bk['robots']) . ', карт сайта ' . count($bk['sitemaps']) . "\n");
}

$apply = in_array('--apply', $argv, true);
$backup = ['robots' => [], 'sitemaps' => []];

$files = array_merge(['/robots.txt'], array_map(static function ($f) use ($root) {
	return substr($f, strlen($root));
}, glob($root . '/aspro_regions/robots/robots_*.txt') ?: []));

$todo = 0;
foreach ($files as $file) {
	$content = (string)file_get_contents($root . $file);
	if (preg_match('~^' . preg_quote(ND_LINE, '~') . '\s*$~m', $content)) {
		continue;
	}
	$eol = strpos($content, "\r\n") !== false ? "\r\n" : "\n";
	if (strpos($content, ND_AFTER) !== false) {
		$new = preg_replace('~^(' . preg_quote(ND_AFTER, '~') . ')\s*$~m', '$1' . $eol . ND_LINE, $content, 1);
	} else {
		echo "  нет строки-ориентира, пропуск: $file\n";
		continue;
	}
	$todo++;
	if ($apply) {
		$backup['robots'][$file] = base64_encode($content);
		file_put_contents($root . $file, $new);
	}
}
echo "robots: к правке $todo из " . count($files) . "\n";

$rs = \Bitrix\Seo\SitemapTable::getList(['select' => ['ID', 'NAME', 'SETTINGS']]);
while ($row = $rs->fetch()) {
	$s = unserialize($row['SETTINGS'], ['allowed_classes' => false]);
	if (!is_array($s) || (($s['DIR']['/compare'] ?? '') === 'N')) {
		continue;
	}
	echo "карта сайта {$row['ID']} «{$row['NAME']}»: исключить /compare\n";
	if ($apply) {
		$backup['sitemaps'][$row['ID']] = $row['SETTINGS'];
		$s['DIR']['/compare'] = 'N';
		\Bitrix\Seo\SitemapTable::update((int)$row['ID'], ['SETTINGS' => serialize($s)]);
	}
}

if (!$apply) {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}
$bkFile = "$home/nd_compare_seo_backup_" . date('Ymd-His') . '.json';
file_put_contents($bkFile, json_encode($backup));
echo "бэкап: $bkFile\n";
