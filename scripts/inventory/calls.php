<?php

// usage: php calls.php <dir> <functions.json>; prints {"calls":{name:count},"files":n}
// counts plain function calls whose name is in the given list (a json array of names)
$root = $argv[1] ?? '';
$list = json_decode((string) file_get_contents($argv[2] ?? ''), true);
if (!is_dir($root) || !is_array($list)) {
	fwrite(STDERR, "usage: calls.php <dir> <functions.json>\n");
	exit(2);
}
$known = array_flip($list);
$skip = '#(^|/)(vendor|node_modules|\.git|tests?|Tests?|fixtures?|translations)(/|$)#';
$ext = '#\.(php|inc|module|install|theme|engine|profile)$#';
$calls = [];
$files = 0;
$blocked = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW];

$it = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);
foreach ($it as $file) {
	$rel = substr($file->getPathname(), strlen($root) + 1);
	if (!$file->isFile() || preg_match($skip, $rel) || !preg_match($ext, $file->getFilename())) {
		continue;
	}
	$files++;
	$tokens = token_get_all((string) file_get_contents($file->getPathname()));
	$sig = [];
	foreach ($tokens as $t) {
		if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
			continue;
		}
		$sig[] = $t;
	}
	for ($i = 0, $n = count($sig); $i < $n - 1; $i++) {
		$t = $sig[$i];
		if (!is_array($t) || $t[0] !== T_STRING || $sig[$i + 1] !== '(') {
			continue;
		}
		$prev = $sig[$i - 1] ?? null;
		if (is_array($prev) && in_array($prev[0], $blocked, true)) {
			continue;
		}
		if (isset($known[$t[1]])) {
			$calls[$t[1]] = ($calls[$t[1]] ?? 0) + 1;
		}
	}
}
arsort($calls);
echo json_encode(['calls' => $calls, 'files' => $files]);
