<?php
// Повторные объявления static $x внутри одной функции (PHP 8.3: Fatal error). Выводит: файл:строка:имя (первое объявление — строка)
foreach (array_slice($argv, 1) as $file) {
    $t = token_get_all(file_get_contents($file));
    $stack = []; $depth = 0; $pendingFunc = false;
    for ($i = 0, $n = count($t); $i < $n; $i++) {
        $tok = $t[$i];
        if (is_array($tok)) {
            if ($tok[0] === T_FUNCTION) { $pendingFunc = true; continue; }
            if ($tok[0] === T_CURLY_OPEN || $tok[0] === T_DOLLAR_OPEN_CURLY_BRACES) { $depth++; continue; }
            if ($tok[0] === T_STATIC && $stack) {
                // static $a, $b = 1; — не static::, не static function
                $j = $i + 1; while (isset($t[$j]) && is_array($t[$j]) && $t[$j][0] === T_WHITESPACE) $j++;
                if (!isset($t[$j]) || !is_array($t[$j]) || $t[$j][0] !== T_VARIABLE) continue;
                for (; $j < $n && $t[$j] !== ';'; $j++) {
                    if (is_array($t[$j]) && $t[$j][0] === T_VARIABLE) {
                        $prev = $j - 1; while (is_array($t[$prev]) && $t[$prev][0] === T_WHITESPACE) $prev--;
                        if ($t[$prev] === '=' ) continue;
                        $f = &$stack[count($stack) - 1];
                        $name = $t[$j][1];
                        if (isset($f['vars'][$name])) echo "$file:{$t[$j][2]}:$name (первое на {$f['vars'][$name]})\n";
                        else $f['vars'][$name] = $t[$j][2];
                        unset($f);
                    }
                }
            }
            continue;
        }
        if ($tok === ';' && $pendingFunc) { $pendingFunc = false; continue; } // abstract/interface
        if ($tok === '{') { $depth++; if ($pendingFunc) { $stack[] = ['depth' => $depth, 'vars' => []]; $pendingFunc = false; } continue; }
        if ($tok === '}') { if ($stack && $stack[count($stack) - 1]['depth'] === $depth) array_pop($stack); $depth--; }
    }
}
