<?php

declare(strict_types=1);

$src = (string) file_get_contents('/home/z/my-project/laravel-atelier/resources/views/orders/index.blade.php');
$invocations = [];
$cursor = 0;
while (($start = strpos($src, '__(', $cursor)) !== false) {
    $depth = 1;
    $pos = $start + 3;
    while ($depth > 0 && $pos < strlen($src)) {
        $char = $src[$pos];
        if ($char === '(') {
            $depth++;
        } elseif ($char === ')') {
            $depth--;
        }
        $pos++;
    }
    $invocations[] = substr($src, $start + 3, $pos - $start - 4);
    $cursor = $pos;
}

echo 'calls: ' . count($invocations) . "\n";
echo "first 5 bodies:\n";
foreach (array_slice($invocations, 0, 5) as $body) {
    echo '  [' . $body . "]\n";
}

$keys = [];
foreach ($invocations as $body) {
    preg_match_all("/'([a-z0-9_.]+)'/", $body, $m);
    foreach ($m[1] as $literal) {
        if (str_contains($literal, '.') && ! str_ends_with($literal, '.')) {
            $keys[] = $literal;
        }
    }
}
$unique = array_values(array_unique($keys));
sort($unique);
echo 'unique keys: ' . count($unique) . "\n";
echo implode("\n", $unique) . "\n";
