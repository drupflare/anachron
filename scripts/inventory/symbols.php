<?php

// usage: php symbols.php <tree>; prints {"functions":[],"classes":[],"aliases":[]} as json
$root = $argv[1] ?? '';
if ($root === '' || !is_dir($root)) {
	fwrite(STDERR, "usage: symbols.php <tree>\n");
	exit(2);
}

$skip = '#(^|/)(vendor|node_modules|\.git|tests?|Tests?|fixtures?)(/|$)#';
$ext = '#\.(php|inc|module|install|theme|engine|profile|test)$#';
$functions = [];
$classes = [];
$aliases = [];

function symbols_of(string $src, array &$functions, array &$classes, array &$aliases): void
{
	$tokens = token_get_all($src);
	$ns = '';
	$stack = [];
	$pendingClass = false;
	$count = count($tokens);

	$next = function (int $i) use ($tokens, $count) {
		for ($j = $i + 1; $j < $count; $j++) {
			$t = $tokens[$j];
			if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				continue;
			}
			return [$j, $t];
		}
		return [$count, null];
	};

	for ($i = 0; $i < $count; $i++) {
		$t = $tokens[$i];
		if (!is_array($t)) {
			if ($t === '{') {
				$stack[] = $pendingClass ? 'class' : 'other';
				$pendingClass = false;
			} elseif ($t === '}') {
				array_pop($stack);
			}
			continue;
		}
		switch ($t[0]) {
			case T_CURLY_OPEN:
			case T_DOLLAR_OPEN_CURLY_BRACES:
				$stack[] = 'other';
				break;
			case T_NAMESPACE:
				[$j, $n] = $next($i);
				if (is_array($n) && in_array($n[0], [T_STRING, T_NAME_QUALIFIED], true)) {
					$ns = $n[1];
				} elseif ($n === '{') {
					$ns = '';
				}
				break;
			case T_CLASS:
			case T_INTERFACE:
			case T_TRAIT:
			case T_ENUM:
				$prev = null;
				for ($j = $i - 1; $j >= 0; $j--) {
					if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
						continue;
					}
					$prev = $tokens[$j];
					break;
				}
				if (is_array($prev) && $prev[0] === T_DOUBLE_COLON) {
					break;
				}
				$pendingClass = true;
				[$j, $n] = $next($i);
				if (is_array($n) && $n[0] === T_STRING) {
					$classes[] = ($ns !== '' ? $ns . '\\' : '') . $n[1];
				}
				break;
			case T_FUNCTION:
				if (in_array('class', $stack, true)) {
					break;
				}
				[$j, $n] = $next($i);
				$amp = [
					T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,
					T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,
				];
				if ($n === '&' || (is_array($n) && in_array($n[0], $amp, true))) {
					[$j, $n] = $next($j);
				}
				if (is_array($n) && $n[0] === T_STRING) {
					$functions[] = ($ns !== '' ? $ns . '\\' : '') . $n[1];
				}
				break;
		}
	}

	if (preg_match_all('/class_alias\(\s*[^,]+,\s*[\'"]([^\'"]+)[\'"]/', $src, $m)) {
		foreach ($m[1] as $a) {
			$aliases[] = ltrim(str_replace('\\\\', '\\', $a), '\\');
		}
	}
	if (preg_match_all('/registerAlias\(\s*[\'"]([^\'"]+)[\'"]/', $src, $m)) {
		foreach ($m[1] as $a) {
			$aliases[] = ltrim($a, '\\');
		}
	}
}

$it = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);
foreach ($it as $file) {
	$rel = substr($file->getPathname(), strlen($root) + 1);
	if (!$file->isFile() || preg_match($skip, $rel) || !preg_match($ext, $file->getFilename())) {
		continue;
	}
	$src = @file_get_contents($file->getPathname());
	if ($src === false) {
		continue;
	}
	symbols_of($src, $functions, $classes, $aliases);
}

foreach ([&$functions, &$classes, &$aliases] as &$list) {
	$list = array_values(array_unique($list));
	sort($list);
}
echo json_encode(['functions' => $functions, 'classes' => $classes, 'aliases' => $aliases]);
