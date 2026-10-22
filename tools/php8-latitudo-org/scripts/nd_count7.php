<?php
// nd_count7.php [--fix] <файлы/папки...> — count(...) → nd_count7(...) в PHP-коде (не методы, не JS/HTML).
// nd_count7 повторяет PHP 7.4: count(null) = 0, count(строки/числа) = 1, а PHP 8 на них падает TypeError.
// Запускать PHP 7.4 (токенизатор).
$fix = false;
$args = array_slice($argv, 1);
if ($args && $args[0] === '--fix') { $fix = true; array_shift($args); }
$files = [];
foreach ($args as $a) {
	if (is_file($a)) { $files[] = $a; continue; }
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($a, FilesystemIterator::SKIP_DOTS));
	foreach ($it as $f) if (substr($f->getPathname(), -4) === '.php' && strpos($f->getPathname(), '/install/') === false) $files[] = $f->getPathname();
}
$total = 0;
foreach ($files as $p) {
	$src = file_get_contents($p);
	if (stripos($src, 'count') === false) continue;
	$t = @token_get_all($src);
	$n = count($t); $hits = 0;
	for ($i = 0; $i < $n; $i++) {
		$x = $t[$i];
		if (!is_array($x) || $x[0] !== T_STRING || strtolower($x[1]) !== 'count') continue;
		$j = $i + 1; while ($j < $n && is_array($t[$j]) && $t[$j][0] === T_WHITESPACE) $j++;
		if (!isset($t[$j]) || $t[$j] !== '(') continue;
		$k = $i - 1; while ($k >= 0 && is_array($t[$k]) && in_array($t[$k][0], [T_WHITESPACE, T_COMMENT])) $k--;
		$pr = $t[$k];
		if (is_array($pr) && in_array($pr[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_NS_SEPARATOR])) continue;
		if (is_array($pr) && defined('T_NULLSAFE_OBJECT_OPERATOR') && $pr[0] === T_NULLSAFE_OBJECT_OPERATOR) continue;
		$t[$i][1] = 'nd_count7';
		$hits++;
	}
	if (!$hits) continue;
	$total += $hits;
	echo "$hits\t$p\n";
	if ($fix) {
		$out = '';
		foreach ($t as $x) $out .= is_array($x) ? $x[1] : $x;
		file_put_contents($p, $out);
	}
}
echo "total\t$total\n";
