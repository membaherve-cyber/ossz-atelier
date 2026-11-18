<?php

namespace App\Support;

/**
 * OSSZ ATELIER — money formatting for the accounting module.
 *
 * The workshop currency is the Central African CFA franc (XAF/FCFA) —
 * no minor units, amounts are whole francs. The formatting is
 * locale-aware (thin/narrow space in French, comma in English) and the
 * currency code stays configurable per record; the symbol shown is XAF
 * (the ISO code) to stay unambiguous in printouts.
 */
final class Money
{
    /** Default workshop currency (kept extensible via orders.currency etc.). */
    public const DEFAULT_CURRENCY = 'XAF';

    /**
     * Format an amount for display, e.g. 150000 → "150,000 XAF" (en) /
     * "150 000 XAF" (fr). Negative amounts keep the leading minus.
     */
    public static function format(float|int|string|null $amount, ?string $currency = null): string
    {
        $value = (float) ($amount ?? 0);
        $negative = $value < -0.004;
        $rounded = round(abs($value));

        $grouped = app()->getLocale() === 'fr'
            ? number_format($rounded, 0, ',', ' ')
            : number_format($rounded, 0, '.', ',');

        $code = $currency ?? self::DEFAULT_CURRENCY;

        return ($negative ? '−' : '').$grouped.' '.$code;
    }

    /**
     * Format without the currency code (for compact table cells).
     */
    public static function amount(float|int|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);
        $negative = $value < -0.004;
        $rounded = round(abs($value));

        $grouped = app()->getLocale() === 'fr'
            ? number_format($rounded, 0, ',', ' ')
            : number_format($rounded, 0, '.', ',');

        return ($negative ? '−' : '').$grouped;
    }

    /**
     * Parse a user-submitted amount leniently: accepts "150000",
     * "150 000", "150,000.50", "150 000,50" → float.
     */
    public static function parse(string $value): float
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return 0.0;
        }

        // Strip spaces and an eventual currency suffix.
        $trimmed = preg_replace('/\s+/', '', $trimmed);
        $trimmed = preg_replace('/(xaf|fcfa|cfa)$/i', '', $trimmed) ?? $trimmed;

        // Both "150,000.50" and "150.000,50" → decimal point form.
        if (str_contains($trimmed, ',') && str_contains($trimmed, '.')) {
            if (strrpos($trimmed, ',') > strrpos($trimmed, '.')) {
                // 150.000,50 — French style
                $trimmed = str_replace('.', '', $trimmed);
                $trimmed = str_replace(',', '.', $trimmed);
            } else {
                // 150,000.50 — English style
                $trimmed = str_replace(',', '', $trimmed);
            }
        } elseif (str_contains($trimmed, ',')) {
            $parts = explode(',', $trimmed);
            $last = end($parts);
            // "150,000" is thousands; "150,5" is a decimal
            $trimmed = (count($parts) === 2 && strlen($last) > 0 && strlen($last) <= 2)
                ? str_replace(',', '.', $trimmed)
                : str_replace(',', '', $trimmed);
        }

        return (float) $trimmed;
    }
}
