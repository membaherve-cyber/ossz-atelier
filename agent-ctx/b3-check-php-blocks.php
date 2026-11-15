<?php

declare(strict_types=1);

/**
 * Extracts every @php ... @endphp block from the B3 views and syntax-checks
 * each one with `php -l` (dev tool, not shipped).
 */

$views = [
    '/home/z/my-project/laravel-atelier/resources/views/orders/index.blade.php',
    '/home/z/my-project/laravel-atelier/resources/views/orders/show.blade.php',
    '/home/z/my-project/laravel-atelier/resources/views/customers/index.blade.php',
    '/home/z/my-project/laravel-atelier/resources/views/users/index.blade.php',
    '/home/z/my-project/laravel-atelier/resources/views/stages/index.blade.php',
];

$failures = 0;
foreach ($views as $view) {
    $src = (string) file_get_contents($view);
    preg_match_all('/@php(.*?)@endphp/s', $src, $matches, PREG_OFFSET_CAPTURE);
    $index = 0;
    foreach ($matches[1] as [$body, $pos]) {
        $index++;
        $line = substr_count(substr($src, 0, $pos), "\n") + 1;
        $tmp = tempnam(sys_get_temp_dir(), 'b3php') . '.php';
        file_put_contents($tmp, "<?php\n\n" . $body);
        exec('/home/z/php-toolchain/php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
        $output = implode("\n", $out);
        $out = [];
        if ($code !== 0) {
            $failures++;
            echo 'FAIL ' . $view . " block #{$index} (line {$line})\n{$output}\n";
        }
        unlink($tmp);
    }
    echo basename(dirname($view)) . '/' . basename($view) . ': ' . $index . " @php blocks OK\n";
}

exit($failures === 0 ? 0 : 1);
