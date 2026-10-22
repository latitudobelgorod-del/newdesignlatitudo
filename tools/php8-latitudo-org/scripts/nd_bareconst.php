<?php
// nd_bareconst.php [--fix] <корень> <папки...> — ключи массива голыми константами вне строк: $a[VALUE] → $a['VALUE']
// (PHP 7 превращал неизвестную константу в строку с предупреждением, PHP 8 — Error)
$fix = false;
$args = array_slice($argv, 1);
if ($args && $args[0] === '--fix') { $fix = true; array_shift($args); }
$root = rtrim(array_shift($args), '/') . '/';
$known = array_change_key_case(get_defined_constants(), CASE_UPPER);
foreach (['SITE_ID', 'SITE_DIR', 'LANGUAGE_ID', 'LANG', 'SITE_TEMPLATE_ID', 'SITE_TEMPLATE_PATH', 'SITE_CHARSET', 'LANG_CHARSET', 'BX_ROOT', 'SITE_SERVER_NAME', 'FORMAT_DATE', 'FORMAT_DATETIME', 'LANG_DIR'] as $c) $known[$c] = 1;
foreach ($args as $dir) {
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . $dir, FilesystemIterator::SKIP_DOTS));
	foreach ($it as $f) {
		$p = $f->getPathname();
		if (substr($p, -4) !== '.php' || strpos($p, '/install/') !== false || strpos($p, '/lang/') !== false) continue;
		$src = file_get_contents($p);
		if (!preg_match('/\[[A-Z][A-Z0-9_]*\]/', $src)) continue;
		$t = @token_get_all($src);
		$inStr = false; $hits = [];
		for ($i = 0, $n = count($t); $i < $n; $i++) {
			$x = $t[$i];
			if ($x === '"') { $inStr = !$inStr; continue; }
			if (is_array($x) && $x[0] === T_START_HEREDOC) { $inStr = true; continue; }
			if (is_array($x) && $x[0] === T_END_HEREDOC) { $inStr = false; continue; }
			if ($inStr) continue;
			if ($x === '[' && isset($t[$i + 2]) && $t[$i + 2] === ']' && is_array($t[$i + 1]) && $t[$i + 1][0] === T_STRING) {
				$name = $t[$i + 1][1];
				$prev = $t[$i - 1];
				$prevOk = (is_array($prev) && $prev[0] === T_VARIABLE) || $prev === ']';
				if ($prevOk && preg_match('/^[A-Za-z_]\w*$/', $name) && !isset($known[strtoupper($name)]) && !defined($name)) {
					$hits[] = [$t[$i + 1][2], $name, $i + 1];
				}
			}
		}
		if (!$hits) continue;
		foreach ($hits as $h) echo substr($p, strlen($root)), ':', $h[0], "\t", $h[1], "\n";
		if ($fix) {
			foreach ($hits as $h) $t[$h[2]] = [T_CONSTANT_ENCAPSED_STRING, "'" . $h[1] . "'", $h[0]];
			$out = '';
			foreach ($t as $x) $out .= is_array($x) ? $x[1] : $x;
			file_put_contents($p, $out);
		}
	}
}
