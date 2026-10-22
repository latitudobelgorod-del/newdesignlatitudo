<?php
// nd_staticvar.php <папки...> — методы классов со static-переменной, значение которой зависит от класса
// (внутри есть static::, $this, get_called_class()). С PHP 8.1 такая переменная одна на родителя и всех
// наследников — первый вызвавший класс «отравляет» результат для остальных.
foreach (array_slice($argv, 1) as $dir) {
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
	foreach ($it as $f) {
		$p = $f->getPathname();
		if (substr($p, -4) !== '.php' || strpos($p, '/install/') !== false || strpos($p, '/lang/') !== false) continue;
		$src = file_get_contents($p);
		if (strpos($src, 'static $') === false) continue;
		$t = @token_get_all($src);
		$n = count($t); $depth = 0; $classDepth = [];
		for ($i = 0; $i < $n; $i++) {
			$x = $t[$i];
			if (is_array($x) && in_array($x[0], [T_CLASS, T_TRAIT]) && !(is_array($t[$i - 1] ?? null) && $t[$i - 1][0] === T_DOUBLE_COLON)) {
				// следующий «{» — тело класса
				for ($j = $i; $j < $n && $t[$j] !== '{'; $j++);
				$classDepth[] = $depth + 1;
			}
			if ($x === '{' || (is_array($x) && in_array($x[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]))) $depth++;
			if ($x === '}') { $depth--; if ($classDepth && $depth < end($classDepth)) array_pop($classDepth); }
			if (!$classDepth || $depth !== end($classDepth) || !is_array($x) || $x[0] !== T_FUNCTION) continue;
			for ($j = $i + 1; $j < $n && !(is_array($t[$j]) && $t[$j][0] === T_STRING); $j++);
			$name = $t[$j][1]; $line = $x[2];
			for (; $j < $n && $t[$j] !== '{' && $t[$j] !== ';'; $j++);
			if ($t[$j] !== '{') continue;
			$d = 0; $hasStaticVar = false; $dep = false;
			for ($k = $j; $k < $n; $k++) {
				$y = $t[$k];
				if ($y === '{' || (is_array($y) && in_array($y[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]))) $d++;
				if ($y === '}') { $d--; if (!$d) break; }
				if (is_array($y) && $y[0] === T_STATIC) {
					$nx = $k + 1; while (is_array($t[$nx]) && $t[$nx][0] === T_WHITESPACE) $nx++;
					if (is_array($t[$nx]) && $t[$nx][0] === T_VARIABLE) $hasStaticVar = true;
					if (is_array($t[$nx]) && $t[$nx][0] === T_DOUBLE_COLON) $dep = true;
				}
				if (is_array($y) && $y[0] === T_VARIABLE && $y[1] === '$this') $dep = true;
				if (is_array($y) && $y[0] === T_STRING && strtolower($y[1]) === 'get_called_class') $dep = true;
			}
			if ($hasStaticVar && $dep) echo "$p:$line\t$name\n";
		}
	}
}
