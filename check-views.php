<?php

/**
 * Verifies that every __('file.key') used in the B1 blade views exists in BOTH
 * lang/en and lang/fr. Also extracts @php blocks and syntax-checks them.
 * Usage: php check-views.php (part 2 appended)
 */

$root = __DIR__ . '/laravel-atelier';
$views = [
    $root . '/resources/views/layouts/app.blade.php',
    $root . '/resources/views/auth/login.blade.php',
    $root . '/resources/views/dashboard.blade.php',
    $root . '/resources/views/notifications/index.blade.php',
    $root . '/resources/views/audit/index.blade.php',
    $root . '/resources/views/components/badges/status.blade.php',
    $root . '/resources/views/components/badges/priority.blade.php',
    $root . '/resources/views/components/badges/category.blade.php',
    $root . '/resources/views/components/badges/role.blade.php',
    $root . '/resources/views/components/badges/stage-state.blade.php',
];

// ---- 1. lang key existence --------------------------------------------------

function langHas(string $file, string $key, string $locale, array $cache): bool
{
    $path = $file; // filled by caller closure below
    return true;
}

$langCache = [];
$loadLang = function (string $locale) use ($root, &$langCache): array {
    if (! isset($langCache[$locale])) {
        $langCache[$locale] = [];
        foreach (glob($root . "/lang/{$locale}/*.php") as $f) {
            $name = basename($f, '.php');
            $langCache[$locale][$name] = include $f;
        }
    }

    return $langCache[$locale];
};

$lookup = function (string $key, string $locale) use ($loadLang): bool {
    $parts = explode('.', $key, 2);
    if (count($parts) !== 2) {
        return false;
    }
    [$file, $path] = $parts;
    $lang = $loadLang($locale);
    if (! isset($lang[$file])) {
        return false;
    }
    $node = $lang[$file];
    foreach (explode('.', $path) as $seg) {
        if (! is_array($node) || ! array_key_exists($seg, $node)) {
            return false;
        }
        $node = $node[$seg];
    }

    return is_string($node) || is_numeric($node);
};

$errors = 0;
$usedKeys = [];

foreach ($views as $view) {
    $src = file_get_contents($view);
    // __('<key>') or __("<key>") — capture the first arg.
    preg_match_all("/__\(\s*'([^']+)'/", $src, $m1);
    preg_match_all('/__\(\s*"([^"]+)"/', $src, $m2);
    foreach (array_merge($m1[1], $m2[1]) as $key) {
        // Skip keys containing ' . (dynamic concatenations).
        $usedKeys[$key][] = basename($view);
    }
}

// Dynamic keys used (documented, must be verified manually/known-complete):
$dynamicPrefixes = [
    'labels.status.', 'labels.priority.', 'labels.category.', 'labels.role.',
    'labels.stageState.', 'labels.notifTitle.', 'audit.action.',
];

echo "== Static lang key check ==\n";
foreach (array_keys($usedKeys) as $key) {
    $dynamic = false;
    foreach ($dynamicPrefixes as $p) {
        if (str_starts_with($key, $p)) {
            $dynamic = true;
            break;
        }
    }
    if ($dynamic) {
        continue; // built via concatenation inside the views; checked below
    }
    foreach (['en', 'fr'] as $locale) {
        if (! $lookup($key, $locale)) {
            echo "MISSING [{$locale}] {$key}\n";
            $errors++;
        }
    }
}
echo $errors === 0 ? "All static keys present in en+fr.\n" : "{$errors} missing keys!\n";

// ---- 2. dynamic key families: verify every possible dynamic value exists ----

echo "\n== Dynamic key families ==\n";
$families = [
    'labels.status' => ['UNASSIGNED', 'ASSIGNED', 'ONGOING', 'AWAITING_INSPECTION', 'CHANGES_REQUESTED', 'APPROVED', 'READY_FOR_PICKUP', 'DELIVERED', 'BLOCKED', 'CANCELLED'],
    'labels.priority' => ['LOW', 'NORMAL', 'HIGH', 'URGENT'],
    'labels.category' => ['FULL', 'CUTTING', 'SEWING', 'FINISHING', 'IRONING', 'OTHER'],
    'labels.role' => ['MANAGEMENT', 'ADMIN', 'STAFF'],
    'labels.stageState' => ['NOT_STARTED', 'IN_PROGRESS', 'COMPLETED', 'NOT_APPLICABLE'],
];
$auditActions = include $root . '/lang/en/audit.php';
$families['audit.action'] = array_keys($auditActions['action']);
$notifTypes = include $root . '/lang/en/labels.php';
$families['labels.notifTitle'] = array_keys($notifTypes['notifTitle']);

$famErrors = 0;
foreach ($families as $prefix => $values) {
    foreach ($values as $v) {
        foreach (['en', 'fr'] as $locale) {
            if (! $lookup($prefix . '.' . $v, $locale)) {
                echo "MISSING [{$locale}] {$prefix}.{$v}\n";
                $famErrors++;
            }
        }
    }
}
echo $famErrors === 0 ? "All dynamic key values present in en+fr.\n" : "{$famErrors} missing dynamic keys!\n";

// ---- 3. PHP syntax check of @php blocks --------------------------------------

echo "\n== @php block syntax ==\n";
$phpBin = '/home/z/php-toolchain/php';
$tmpDir = sys_get_temp_dir();
$blockErrors = 0;
foreach ($views as $view) {
    $src = file_get_contents($view);
    preg_match_all('/@php(.*?)@endphp/s', $src, $blocks);
    $i = 0;
    foreach ($blocks[1] as $block) {
        $i++;
        $tmp = $tmpDir . '/blk_' . md5($view . $i) . '.php';
        file_put_contents($tmp, "<?php\n" . $block);
        $out = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($tmp) . ' 2>&1');
        if (! str_contains((string) $out, 'No syntax errors')) {
            echo "PHP ERROR in " . basename($view) . " block #{$i}:\n{$out}\n";
            $blockErrors++;
        }
        @unlink($tmp);
    }
}
echo $blockErrors === 0 ? "All @php blocks pass php -l.\n" : "{$blockErrors} blocks with errors!\n";

echo "\nDONE\n";
