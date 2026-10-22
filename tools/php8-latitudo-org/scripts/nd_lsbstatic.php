<?php
// nd_lsbstatic.php <корень модулей> <список> [--apply]
// Ядро Битрикса 22.100 под PHP 8.1+: в методах из списка (вывод nd_staticvar.php, «путь:строка<TAB>метод»,
// пути от <корня модулей>) каждую `static $x = ИНИЦИАЛИЗАТОР;` переводим на хранение по классу вызова:
//   static $__nd_x = []; if (!array_key_exists(static::class, $__nd_x)) { $__nd_x[static::class] = ИНИЦИАЛИЗАТОР; } $x = &$__nd_x[static::class];
// До PHP 8.1 у каждого класса-наследника была своя копия static-переменной унаследованного метода, с 8.1 —
// одна на всех. Ключ по static::class возвращает «своя у каждого класса» (так же сделано в новых версиях sale).
// На PHP 7.4 поведение то же. Замена занимает столько же строк, сколько было, — номера строк не сдвигаются;
// инициализатор переносится как есть (многострочные строки и регулярки не склеиваем).
// Static-переменные вложенных функций и замыканий не трогаем. Без --apply — только отчёт.
$root = rtrim($argv[1], '/');
$apply = in_array('--apply', $argv, true);
$byFile = [];
foreach (file($argv[2], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
	[$loc, $name] = explode("\t", $l);
	[$path, $line] = explode(':', $loc);
	$byFile[$path][(int)$line] = $name;
}
$total = 0; $methods = 0; $skipped = [];
foreach ($byFile as $path => $lines) {
	$file = "$root/$path";
	$src = file_get_contents($file);
	$t = token_get_all($src);
	$n = count($t);
	$edits = []; // [начальный индекс токена, конечный индекс, замена]
	for ($i = 0; $i < $n; $i++) {
		if (!is_array($t[$i]) || $t[$i][0] !== T_FUNCTION || !isset($lines[$t[$i][2]])) continue;
		// тело метода
		for ($j = $i; $j < $n && $t[$j] !== '{' && $t[$j] !== ';'; $j++);
		if ($t[$j] !== '{') continue;
		$methods++;
		$found = 0;
		$d = 0; $skipTo = -1;
		for ($k = $j; $k < $n; $k++) {
			$y = $t[$k];
			if ($y === '{' || (is_array($y) && in_array($y[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]))) $d++;
			if ($y === '}') { $d--; if (!$d) break; }
			// вложенная функция/замыкание — пропускаем её тело целиком
			if ($k > $j && is_array($y) && ($y[0] === T_FUNCTION || (defined('T_FN') && $y[0] === T_FN))) {
				if ($y[0] === T_FUNCTION) {
					for ($m = $k; $m < $n && $t[$m] !== '{'; $m++);
					$dd = 0;
					for (; $m < $n; $m++) {
						if ($t[$m] === '{' || (is_array($t[$m]) && in_array($t[$m][0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]))) $dd++;
						if ($t[$m] === '}') { $dd--; if (!$dd) break; }
					}
					$k = $m;
				}
				continue;
			}
			if (!is_array($y) || $y[0] !== T_STATIC) continue;
			$nx = $k + 1; while (is_array($t[$nx]) && $t[$nx][0] === T_WHITESPACE) $nx++;
			if (!is_array($t[$nx]) || $t[$nx][0] !== T_VARIABLE) continue;
			// объявление до «;»: переменные через запятую на верхнем уровне скобок
			$decls = []; $cur = ''; $p = 0;
			for ($m = $nx; $m < $n; $m++) {
				$z = $t[$m]; $s = is_array($z) ? $z[1] : $z;
				if (is_array($z) && in_array($z[0], [T_COMMENT, T_DOC_COMMENT])) continue; // комментарий внутри объявления
				if ($p === 0 && $z === ';') break;
				if ($p === 0 && $z === ',') { $decls[] = $cur; $cur = ''; continue; }
				if (in_array($z, ['(', '['], true)) $p++;
				if (in_array($z, [')', ']'], true)) $p--;
				$cur .= $s;
			}
			$decls[] = $cur;
			$out = [];
			foreach ($decls as $dcl) {
				if (!preg_match('/^\s*\$(\w+)\s*(?:=\s*(.*?))?\s*$/s', $dcl, $mm)) { $skipped[] = "$path:{$t[$k][2]} не разобрано: $dcl"; continue 2; }
				$var = $mm[1]; $init = isset($mm[2]) && $mm[2] !== '' ? $mm[2] : 'null';
				// переменную потом перепривязывают ссылкой ($x = &...) — связь с хранилищем порвётся, не трогаем
				$body = ''; for ($q = $j; $q < $n; $q++) { $body .= is_array($t[$q]) ? $t[$q][1] : $t[$q]; if ($q > $k + 2000) break; }
				if (preg_match('/\$' . $var . '\s*=\s*&/', $body)) { $skipped[] = "$path:{$t[$k][2]} \$$var перепривязывается ссылкой"; continue 2; }
				// инициализатор переносим как есть: многострочную строку (регулярка с /x и комментариями) склейка сломала бы
				$out[] = "static \$__nd_$var = []; if (!array_key_exists(static::class, \$__nd_$var)) { \$__nd_{$var}[static::class] = $init; } \$$var = &\$__nd_{$var}[static::class];";
			}
			$edits[] = [$k, $m, implode(' ', $out)];
			$found++;
			$k = $m;
		}
		if (!$found) $skipped[] = "$path:{$t[$i][2]} нет static-переменной на уровне метода";
		$total += $found;
	}
	if (!$edits) continue;
	echo "$path: " . count($edits) . "\n";
	if (!$apply) continue;
	// собираем файл заново: заменённые диапазоны токенов (включая «;») — одной строкой
	$res = ''; $e = 0;
	for ($i = 0; $i < $n; $i++) {
		if ($e < count($edits) && $i === $edits[$e][0]) {
			$res .= $edits[$e][2];
			// переводы строк из заменённого куска сохраняем, чтобы строки не сдвинулись
			$lost = -substr_count($edits[$e][2], "\n");
			for ($q = $edits[$e][0]; $q <= $edits[$e][1]; $q++) $lost += substr_count(is_array($t[$q]) ? $t[$q][1] : $t[$q], "\n");
			$res .= str_repeat("\n", max(0, $lost));
			$i = $edits[$e][1];
			$e++;
			continue;
		}
		$res .= is_array($t[$i]) ? $t[$i][1] : $t[$i];
	}
	file_put_contents($file, $res);
}
echo "методов: $methods, переменных переведено: $total\n";
foreach ($skipped as $s) echo "ПРОПУСК $s\n";
