<?php

/**
 * OSSZ ATELIER — multibyte string polyfills.
 *
 * The app is bilingual (English / French) and calls mb_* helpers for
 * validation lengths and case-insensitive duplicate detection of garment
 * piece names that may contain accented characters.
 *
 * Namecheap's shared stack (LiteSpeed / PHP 8.2+) ships the mbstring
 * extension, in which case every polyfill below is skipped automatically
 * (each is guarded by function_exists). These fallbacks exist so the app
 * keeps working on hosts where mbstring is disabled.
 *
 * Referenced from composer.json ("files" autoload) — do not remove.
 */

if (!function_exists('mb_strlen')) {
    /**
     * UTF-8 aware string length. Falls back to iconv, then to preg.
     */
    function mb_strlen($string, $encoding = null)
    {
        $string = (string) $string;

        if (function_exists('iconv_strlen')) {
            $count = @iconv_strlen($string, 'UTF-8');

            if ($count !== false) {
                return $count;
            }
        }

        // Last resort: count UTF-8 code points via preg.
        if (function_exists('preg_match_all')) {
            $count = @preg_match_all('/./us', $string, $matches);

            if ($count !== false) {
                return $count;
            }
        }

        return strlen($string);
    }
}

if (!function_exists('mb_strtolower')) {
    /**
     * UTF-8 aware lowercase. Covers Latin-1 Supplement letters used by
     * the app's EN/FR dictionaries; everything else falls back to strtolower.
     */
    function mb_strtolower($string, $encoding = null)
    {
        $string = (string) $string;

        $map = [
            'À' => 'à', 'Á' => 'á', 'Â' => 'â', 'Ã' => 'ã', 'Ä' => 'ä', 'Å' => 'å',
            'Æ' => 'æ', 'Ç' => 'ç',
            'È' => 'è', 'É' => 'é', 'Ê' => 'ê', 'Ë' => 'ë',
            'Ì' => 'ì', 'Í' => 'í', 'Î' => 'î', 'Ï' => 'ï',
            'Ñ' => 'ñ',
            'Ò' => 'ò', 'Ó' => 'ó', 'Ô' => 'ô', 'Õ' => 'õ', 'Ö' => 'ö',
            'Ù' => 'ù', 'Ú' => 'ú', 'Û' => 'û', 'Ü' => 'ü', 'Ý' => 'ý', 'Ÿ' => 'ÿ',
        ];

        return strtolower(strtr($string, $map));
    }
}

if (!function_exists('mb_substr')) {
    /**
     * UTF-8 aware substring (used by some validation paths).
     */
    function mb_substr($string, $start, $length = null, $encoding = null)
    {
        $string = (string) $string;

        if (function_exists('iconv_substr')) {
            $result = ($length === null)
                ? @iconv_substr($string, $start, PHP_INT_MAX, 'UTF-8')
                : @iconv_substr($string, $start, $length, 'UTF-8');

            if ($result !== false) {
                return $result;
            }
        }

        return ($length === null) ? substr($string, $start) : substr($string, $start, $length);
    }
}
