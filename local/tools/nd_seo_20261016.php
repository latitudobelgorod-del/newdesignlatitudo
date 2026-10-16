<?php
/**
 * Карта сайта после аудита дублей (16.10.2026):
 *   - /company/reviews/ (отзывы) в карту дописывает обработчик (ndSitemapAddHomepage в
 *     local/php_interface/include/sitemap_handler1.php): у /company/ нет .section.php, логический обход
 *     генератора туда не заходит, галочка SETTINGS['FILE'] не помогает;
 *   - /info/ — из карты: адрес теперь 301 на /info/company/ (info/index.php), а /info/company/ в карте
 *     и так есть из инфоблока 25.
 *
 *   php local/tools/nd_seo_20261016.php            — показать, что поменяется
 *   php local/tools/nd_seo_20261016.php --apply    — записать (бэкап ~/nd_seo_20261016_backup_<время>.json)
 *   php local/tools/nd_seo_20261016.php rollback <бэкап>
 *
 * После записи: php local/tools/sitemap_generate.php, очистить bitrix/cache/sitemap_regions/,
 * php local/php_interface/include/sitemap_handler1.php ALL.
 */
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('seo');

$home = getenv('HOME') ?: sys_get_temp_dir();

if (($argv[1] ?? '') === 'rollback') {
	$bk = json_decode(file_get_contents($argv[2]), true);
	foreach ($bk as $id => $settings) {
		\Bitrix\Seo\SitemapTable::update((int)$id, ['SETTINGS' => $settings]);
	}
	exit('откат: карт сайта ' . count($bk) . "\n");
}

$apply = in_array('--apply', $argv, true);
$want = [
	'/info/index.php' => 'N',
];

$backup = [];
$rs = \Bitrix\Seo\SitemapTable::getList(['select' => ['ID', 'NAME', 'SITE_ID', 'SETTINGS'], 'filter' => ['=SITE_ID' => 's1']]);
while ($row = $rs->fetch()) {
	$s = unserialize($row['SETTINGS'], ['allowed_classes' => false]);
	if (!is_array($s)) {
		continue;
	}
	$changed = false;
	foreach ($want as $file => $val) {
		$cur = $s['FILE'][$file] ?? '(нет)';
		echo "#{$row['ID']} {$row['NAME']}: FILE[$file] $cur → $val\n";
		if ($cur !== $val) {
			$s['FILE'][$file] = $val;
			$changed = true;
		}
	}
	if ($changed && $apply) {
		$backup[$row['ID']] = $row['SETTINGS'];
		\Bitrix\Seo\SitemapTable::update((int)$row['ID'], ['SETTINGS' => serialize($s)]);
	}
}
if ($apply && $backup) {
	$f = "$home/nd_seo_20261016_backup_" . date('Ymd-His') . '.json';
	file_put_contents($f, json_encode($backup, JSON_UNESCAPED_UNICODE));
	echo "записано, бэкап: $f\n";
} elseif (!$apply) {
	echo "прогон без записи, для записи --apply\n";
}
