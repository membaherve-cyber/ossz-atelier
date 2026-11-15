<?php

declare(strict_types=1);

/**
 * Directive-balance checker for the B3 Blade views (dev tool, not shipped).
 * Usage: php check-blade.php <file.blade.php>
 */

$file = $argv[1] ?? '';
if ($file === '' || ! is_file($file)) {
    fwrite(STDERR, "usage: php check-blade.php <file>\n");
    exit(1);
}

$src = (string) file_get_contents($file);

// Remove Blade comments {{-- --}} and @php blocks before counting structural
// directives (comments cannot contain directives; @php blocks hold raw PHP).
$src = preg_replace('/\{\{--(?s).*?--\}\}/', '', (string) $src) ?? '';

$stack = [];
$errors = [];
$line = 1;
$offset = 0;

$pattern = '/@(php|endphp|if|endif|unless|endunless|foreach|endforeach|forelse|empty|endforelse|for|endfor|while|endwhile|section|endsection|push|endpush|error|enderror|checked|selected|csrf|each|include|extends|yield|props)\b[^(\n]*(?:\(([^)\n]*)\))?/';
preg_match_all($pattern, $src, $matches, PREG_OFFSET_CAPTURE);

foreach ($matches[1] as $index => [$directive, $pos]) {
    $lineNo = substr_count(substr($src, 0, $pos), "\n") + 1;
    $args = $matches[2][$index][0] ?? '';
    switch ($directive) {
        case 'php':
        case 'if':
        case 'unless':
        case 'foreach':
        case 'forelse':
        case 'for':
        case 'while':
        case 'push':
        case 'error':
            $stack[] = [$directive, $lineNo];
            break;
        case 'section':
            // Inline two-argument form @section('title', 'value') needs no @endsection.
            if (substr_count((string) $args, ',') === 0) {
                $stack[] = ['section', $lineNo];
            }
            break;
        case 'endphp':
        case 'endif':
        case 'endunless':
        case 'endforeach':
        case 'endfor':
        case 'endwhile':
        case 'endsection':
        case 'endpush':
        case 'enderror':
            $expected = [
                'endphp' => 'php', 'endif' => 'if', 'endunless' => 'unless',
                'endforeach' => 'foreach', 'endfor' => 'for', 'endwhile' => 'while',
                'endsection' => 'section', 'endpush' => 'push', 'enderror' => 'error',
            ][$directive];
            if ($stack === []) {
                $errors[] = "line {$lineNo}: @{$directive} with empty stack";
            } else {
                [$top, $topLine] = array_pop($stack);
                if ($top !== $expected) {
                    $errors[] = "line {$lineNo}: @{$directive} closes @{$top} (opened line {$topLine})";
                }
            }
            break;
        case 'empty':
            // Only a closer inside @forelse.
            if ($stack !== [] && end($stack)[0] === 'forelse') {
                // valid: @forelse ... @empty ... @endforelse — keep forelse on the stack.
            } else {
                $errors[] = "line {$lineNo}: @empty outside @forelse";
            }
            break;
        default:
            // Leaf directives (@checked, @selected, @csrf, ...) — nothing to do.
            break;
    }
}

foreach ($stack as [$open, $openLine]) {
    $errors[] = "unclosed @{$open} opened line {$openLine}";
}

echo $errors === []
    ? "OK  " . basename($file) . " — all directives balanced\n"
    : "FAIL " . basename($file) . "\n  " . implode("\n  ", $errors) . "\n";

exit($errors === [] ? 0 : 1);
