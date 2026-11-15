<?php

/**
 * Static verification for resources/views/incentives/index.blade.php:
 * 1. Blade directive balance (@if/@endif, @foreach/@endforeach, sections).
 * 2. All __('file.key') keys exist in BOTH lang/en and lang/fr.
 * 3. @php block syntax (php -l).
 * 4. Alpine attribute sanity (x-data / x-on:click / template x-if quotes).
 */

$root = '/home/z/my-project/laravel-atelier';
$view = $root . '/resources/views/incentives/index.blade.php';
$src = file_get_contents($view);
$errors = 0;

// ---- 1. directive balance ----------------------------------------------------
echo "== Directive balance ==\n";
$directives = [];
foreach (['@if', '@endif', '@foreach', '@endforeach', '@php', '@endphp', '@csrf', '@extends', '@else'] as $d) {
    $directives[$d] = preg_match_all('/' . preg_quote($d, '/') . '\b/', $src);
}
$directives['@section'] = preg_match_all('/@section\b/', $src);
$directives['@endsection'] = preg_match_all('/@endsection\b/', $src);
echo "@if={$directives['@if']} @endif={$directives['@endif']} @else={$directives['@else']} @foreach={$directives['@foreach']} @endforeach={$directives['@endforeach']} @php={$directives['@php']} @endphp={$directives['@endphp']} @section={$directives['@section']} @endsection={$directives['@endsection']} @csrf={$directives['@csrf']}\n";
if ($directives['@if'] !== $directives['@endif']) { echo "MISMATCH @if/@endif\n"; $errors++; }
if ($directives['@foreach'] !== $directives['@endforeach']) { echo "MISMATCH @foreach/@endforeach\n"; $errors++; }
if ($directives['@php'] !== $directives['@endphp']) { echo "MISMATCH @php/@endphp\n"; $errors++; }
if ($directives['@csrf'] !== 6) { echo "UNEXPECTED @csrf count (expected 6 forms: create, edit, toggle, generate, review, payment)\n"; $errors++; }

// simulate stack walk for proper nesting
$stack = [];
preg_match_all('/@(if|foreach|php|endif|endforeach|endphp)\b/', $src, $m, PREG_SET_ORDER);
foreach ($m as $tok) {
    $t = $tok[1];
    if (in_array($t, ['if', 'foreach', 'php'], true)) { $stack[] = $t; continue; }
    $open = array_pop($stack);
    $expect = ['endif' => 'if', 'endforeach' => 'foreach', 'endphp' => 'php'][$t];
    if ($open !== $expect) { echo "NESTING ERROR: @{$t} closes @{$open}\n"; $errors++; }
}
if ($stack !== []) { echo "UNCLOSED directives: " . implode(',', $stack) . "\n"; $errors++; }
echo $errors === 0 ? "Balanced.\n" : "Errors above!\n";

// ---- 2. lang keys ------------------------------------------------------------
echo "\n== Lang key existence (en+fr) ==\n";
$langCache = [];
$loadLang = function (string $locale) use ($root, &$langCache): array {
    if (! isset($langCache[$locale])) {
        $langCache[$locale] = [];
        foreach (glob($root . "/lang/{$locale}/*.php") as $f) {
            $langCache[$locale][basename($f, '.php')] = include $f;
        }
    }
    return $langCache[$locale];
};
$lookup = function (string $key, string $locale) use ($loadLang): bool {
    $parts = explode('.', $key, 2);
    if (count($parts) !== 2) return false;
    [$file, $path] = $parts;
    $lang = $loadLang($locale);
    if (! isset($lang[$file])) return false;
    $node = $lang[$file];
    foreach (explode('.', $path) as $seg) {
        if (! is_array($node) || ! array_key_exists($seg, $node)) return false;
        $node = $node[$seg];
    }
    return is_string($node) || is_numeric($node);
};
preg_match_all("/__\(\s*'([^']+)'/", $src, $m1);
preg_match_all('/__\(\s*"([^"]+)"/', $src, $m2);
$keys = array_unique(array_merge($m1[1], $m2[1]));
$keyErrors = 0;
foreach ($keys as $key) {
    if (str_contains($key, "' .") || str_contains($key, '" .')) continue; // dynamic concat
    foreach (['en', 'fr'] as $locale) {
        if (! $lookup($key, $locale)) { echo "MISSING [{$locale}] {$key}\n"; $keyErrors++; }
    }
}
foreach (['PENDING', 'APPROVED', 'REJECTED'] as $s) {
    foreach (['en', 'fr'] as $locale) {
        if (! $lookup('labels.incStatus.' . $s, $locale)) { echo "MISSING [{$locale}] labels.incStatus.{$s}\n"; $keyErrors++; }
    }
}
foreach (['MONTHLY', 'QUARTERLY', 'ANNUAL', 'QUALITY', 'ON_TIME', 'CUSTOM'] as $s) {
    foreach (['en', 'fr'] as $locale) {
        if (! $lookup('labels.incentivePeriod.' . $s, $locale)) { echo "MISSING [{$locale}] labels.incentivePeriod.{$s}\n"; $keyErrors++; }
    }
}
foreach (['PAID', 'UNPAID'] as $s) {
    foreach (['en', 'fr'] as $locale) {
        if (! $lookup('labels.paymentStatus.' . $s, $locale)) { echo "MISSING [{$locale}] labels.paymentStatus.{$s}\n"; $keyErrors++; }
    }
}
echo $keyErrors === 0 ? "All keys present in en+fr (" . count($keys) . " static keys).\n" : "{$keyErrors} missing keys!\n";
$errors += $keyErrors;

// ---- 3. @php block syntax ----------------------------------------------------
echo "\n== @php block syntax ==\n";
$phpBin = '/home/z/php-toolchain/php';
preg_match_all('/@php(.*?)@endphp/s', $src, $blocks);
$i = 0; $blkErrors = 0;
foreach ($blocks[1] as $block) {
    $i++;
    $tmp = sys_get_temp_dir() . '/inc_blk_' . $i . '.php';
    file_put_contents($tmp, "<?php\n" . $block);
    $out = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($tmp) . ' 2>&1');
    if (! str_contains((string) $out, 'No syntax errors')) { echo "PHP ERROR block #{$i}:\n{$out}\n"; $blkErrors++; }
    @unlink($tmp);
}
echo $blkErrors === 0 ? "All @php blocks pass php -l.\n" : "{$blkErrors} blocks with errors!\n";
$errors += $blkErrors;

// ---- 4. Alpine sanity --------------------------------------------------------
echo "\n== Alpine attribute sanity ==\n";
$alpineErrors = 0;
preg_match_all('/<template x-if="([^"]*)"/', $src, $tpl);
echo "template x-if count: " . count($tpl[1]) . "\n";
foreach ($tpl[1] as $expr) {
    echo "  x-if: {$expr}\n";
}
preg_match_all('/x-on:click="([^"]*)"/', $src, $clicks);
foreach ($clicks[1] as $c) {
    if (substr_count($c, "'") % 2 !== 0) { echo "UNBALANCED single quotes in x-on:click: {$c}\n"; $alpineErrors++; }
}
preg_match_all('/:placeholder="([^"]*)"/', $src, $ph);
foreach ($ph[1] as $c) {
    if (substr_count($c, "'") % 2 !== 0) { echo "UNBALANCED single quotes in :placeholder: {$c}\n"; $alpineErrors++; }
}
preg_match_all('/x-data="([^"]*)"/s', $src, $xd);
foreach ($xd[1] as $c) {
    if (substr_count($c, "'") % 2 !== 0) { echo "UNBALANCED single quotes in x-data\n"; $alpineErrors++; }
}
echo "x-data blocks: " . count($xd[1]) . "\n";
echo $alpineErrors === 0 ? "Alpine quoting OK.\n" : "{$alpineErrors} alpine issues!\n";
$errors += $alpineErrors;

// ---- 5. variables used -------------------------------------------------------
echo "\n== PHP variables referenced ==\n";
preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $src, $vars);
$used = array_values(array_unique($vars[1]));
$allowed = ['schemes', 'records', 'filters', 'staffList', 'canApprove', 'canPrepare',
            'canManageSchemes', 'canPay', 'me', 'unread', 'scheme', 'record', 'staff',
            'type', 'statusBadge', 'loop', 'errors'];
foreach ($used as $v) {
    if (! in_array($v, $allowed, true)) echo "  UNKNOWN/verify var: \${$v}\n";
}
echo "vars: " . implode(', ', $used) . "\n";

echo "\nTOTAL ERRORS: {$errors}\n";
