<?php
// nd_curly.php <файлы...> — $s{0} → $s[0] (PHP 8 убрал фигурные скобки для смещений; на 7.4 запись равносильна)
// запускать PHP 7.4: его токенизатор ещё понимает старую запись
foreach (array_slice($argv, 1) as $p) {
	$t = token_get_all(file_get_contents($p));
	$n = count($t); $inStr = false; $fixed = 0;
	for ($i = 0; $i < $n; $i++) {
		$x = $t[$i];
		if ($x === '"' || (is_array($x) && in_array($x[0], [T_START_HEREDOC, T_END_HEREDOC]))) { $inStr = $x === '"' ? !$inStr : $x[0] === T_START_HEREDOC; continue; }
		if ($inStr || $x !== '{') continue;
		// что стоит перед «{» (пробелы пропускаем): переменная, «]», «)» после имени свойства, «}» от предыдущего смещения
		$j = $i - 1;
		while ($j >= 0 && is_array($t[$j]) && $t[$j][0] === T_WHITESPACE) $j--;
		$pr = $t[$j];
		$ok = (is_array($pr) && in_array($pr[0], [T_VARIABLE, T_STRING_VARNAME])) || $pr === ']'
			|| (is_array($pr) && $pr[0] === T_STRING && is_array($t[$j - 1]) && $t[$j - 1][0] === T_OBJECT_OPERATOR)
			|| (is_array($pr) && isset($pr['nd']));
		if (!$ok) continue;
		// парная «}»
		$d = 0;
		for ($k = $i; $k < $n; $k++) {
			$y = $t[$k];
			if ($y === '{' || (is_array($y) && in_array($y[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]))) $d++;
			if ($y === '}') { $d--; if (!$d) break; }
		}
		if ($k >= $n) continue;
		$t[$i] = '['; $t[$k] = ['nd' => 1, ']'];
		$fixed++;
	}
	if (!$fixed) { echo "0\t$p\n"; continue; }
	$out = '';
	foreach ($t as $x) $out .= is_array($x) ? (isset($x['nd']) ? $x[0] : $x[1]) : $x;
	file_put_contents($p, $out);
	echo "$fixed\t$p\n";
}
