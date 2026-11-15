<?php

declare(strict_types=1);

/** Rough HTML tag-balance check for the B3 views (dev tool, not shipped). */

$views = [
    '/home/z/my-project/laravel-atelier/resources/views/orders/index.blade.php',
    '/home/z/my-project/laravel-atelier/resources/views/orders/show.blade.php',
    '/home/z/my-project/laravel-atelier/resources/views/customers/index.blade.php',
    '/home/z/my-project/laravel-atelier/resources/views/users/index.blade.php',
    '/home/z/my-project/laravel-atelier/resources/views/stages/index.blade.php',
];

$voidElements = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

$failures = 0;
foreach ($views as $view) {
    $src = (string) file_get_contents($view);
    // Strip Blade {{ }} and @php blocks so their contents are not counted.
    $src = preg_replace('/\{\{(?!\{).*?\}\}/s', '', (string) preg_replace('/@php.*?@endphp/s', '', $src)) ?? '';

    preg_match_all('/<\/?([a-zA-Z][a-zA-Z0-9-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/s', $src, $tags, PREG_SET_ORDER);

    $stack = [];
    $errors = [];
    foreach ($tags as $tag) {
        $name = strtolower($tag[1]);
        $isClose = $tag[0][1] === '/';
        $selfClosing = str_ends_with(rtrim($tag[0]), '/>') || in_array($name, $voidElements, true);

        if ($isClose) {
            if ($stack === []) {
                $errors[] = "stray </{$name}>";
            } else {
                $top = array_pop($stack);
                if ($top !== $name) {
                    $errors[] = "mismatch: </{$name}> closes <{$top}>";
                    // Try to resync: pop until match found.
                    while ($stack !== [] && end($stack) !== $name) {
                        array_pop($stack);
                    }
                    if ($stack !== []) {
                        array_pop($stack);
                    }
                }
            }
        } elseif (! $selfClosing) {
            $stack[] = $name;
        }
    }

    foreach ($stack as $open) {
        $errors[] = "unclosed <{$open}>";
    }

    if ($errors !== []) {
        $failures++;
        echo 'FAIL ' . basename(dirname($view)) . '/' . basename($view) . "\n  " . implode("\n  ", $errors) . "\n";
    } else {
        echo 'OK   ' . basename(dirname($view)) . '/' . basename($view) . "\n";
    }
}

exit($failures === 0 ? 0 : 1);
