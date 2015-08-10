<?php

$ignore = '%&\'"+?@#:/`';

$new_key = '';
for($i = 0; $i < 40; $i++) {
	$char = chr(rand(33, 126));
	if (strpbrk($char, $ignore) !== false)
		continue;
	$new_key .= $char;
}

$code = file_get_contents('index.php');
$code = str_replace('VUmD[uDP[.\//xbAJCF3MN{xAU8T\.d', $new_key, $code);

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="index.php"');
echo($code);

?>