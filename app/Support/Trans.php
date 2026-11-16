<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Parameterized translation helper.
 *
 * The lang files use ICU-style {param} placeholders ("{name} was saved"),
 * which core Laravel's __() does NOT substitute (it only handles :param).
 * Trans::fill() translates the key and then substitutes the {param}
 * placeholders, so every parameterized flash/history/notification message
 * renders correctly in both locales:
 *
 *     Trans::fill('cust.registeredToastDesc', ['name' => 'Aisha', 'code' => 'CUST-0007']);
 *
 * Non-parameterized strings keep using plain __('file.key') calls.
 */
final class Trans
{
    /**
     * Translate $key and substitute {param} placeholders from $params.
     */
    public static function fill(string $key, array $params = []): string
    {
        $line = __($key);

        foreach ($params as $name => $value) {
            $line = str_replace('{'.$name.'}', (string) $value, $line);
        }

        return $line;
    }
}
