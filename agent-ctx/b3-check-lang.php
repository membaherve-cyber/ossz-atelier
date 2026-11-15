<?php

declare(strict_types=1);

/**
 * Verifies that every __('file.key') reference used in the B3 Blade views
 * resolves in BOTH lang/en and lang/fr (dev tool, not shipped).
 * Nested keys use dot paths (users.col.userId style are supported).
 */

$views = [
    'resources/views/orders/index.blade.php',
    'resources/views/orders/show.blade.php',
    'resources/views/customers/index.blade.php',
    'resources/views/users/index.blade.php',
    'resources/views/stages/index.blade.php',
];

$base = '/home/z/my-project/laravel-atelier';

function load(string $locale, string $file): ?array
{
    $path = "/home/z/my-project/laravel-atelier/lang/{$locale}/{$file}.php";
    if (! is_file($path)) {
        return null;
    }

    return include $path;
}

function resolve(array $dict, string $key): bool
{
    $node = $dict;
    foreach (explode('.', $key) as $segment) {
        if (! is_array($node) || ! array_key_exists($segment, $node)) {
            return false;
        }
        $node = $node[$segment];
    }

    return true;
}

$failures = 0;
$allKeys = [];

foreach ($views as $view) {
    $src = (string) file_get_contents("{$base}/{$view}");

    // Extract every __( ... ) invocation (paren-balanced) and collect the
    // dot-path string literals inside it (handles ternaries like
    // __($a ? 'x.y' : 'z.w') and skips concatenation fragments).
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

    $keys = [];
    foreach ($invocations as $body) {
        preg_match_all("/'([a-zA-Z0-9_.]+)'/", $body, $m);
        foreach ($m[1] as $literal) {
            if (str_contains($literal, '.') && ! str_ends_with($literal, '.')) {
                $keys[] = $literal;
            }
        }
    }

    foreach (array_unique($keys) as $key) {
        $allKeys[$key][] = $view;
    }
    echo basename(dirname($view)) . '/' . basename($view) . ': ' . count(array_unique($keys)) . " keys across " . count($invocations) . " __() calls\n";
}

// Validate all static keys against both locales.
foreach ($allKeys as $key => $usedIn) {
    $segments = explode('.', $key, 2);
    $file = $segments[0];
    $inner = $segments[1] ?? '';
    foreach (['en', 'fr'] as $locale) {
        $dict = load($locale, $file);
        if ($dict === null) {
            echo "MISSING lang file: lang/{$locale}/{$file}.php (key {$key})\n";
            $failures++;
            continue;
        }
        if ($inner === '' || ! resolve($dict, $inner)) {
            echo "MISSING [{$locale}] {$key}  (used in " . implode(', ', array_map('basename', $usedIn)) . ")\n";
            $failures++;
        }
    }
}

// Dynamic key families used with concatenation — verify the full label sets.
$dynamicFamilies = [
    'labels.priority' => ['LOW', 'NORMAL', 'HIGH', 'URGENT'],
    'labels.role' => ['MANAGEMENT', 'ADMIN', 'STAFF'],
    'labels.category' => ['FULL', 'CUTTING', 'SEWING', 'FINISHING', 'IRONING', 'OTHER'],
];
foreach ($dynamicFamilies as $prefix => $suffixes) {
    foreach ($suffixes as $suffix) {
        $key = "{$prefix}.{$suffix}";
        $segments = explode('.', $key, 2);
        $file = $segments[0];
        $inner = $segments[1];
        foreach (['en', 'fr'] as $locale) {
            $dict = load($locale, $file);
            if ($dict === null || ! resolve($dict, $inner)) {
                echo "MISSING [{$locale}] {$key} (dynamic family)\n";
                $failures++;
            }
        }
    }
}

echo $failures === 0 ? "\nALL KEYS RESOLVE in en + fr\n" : "\n{$failures} key failure(s)\n";
exit($failures === 0 ? 0 : 1);
