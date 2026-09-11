<?php

namespace App\Support;

class Phone
{
    /** "0554 888 1183" / "5548881183" / "+90 554 ..." -> "+90 (554) 888 11 83"; tanınmazsa olduğu gibi döner. */
    public static function format(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $raw);
        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) !== 10) {
            return trim($raw);
        }

        return sprintf('+90 (%s) %s %s %s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 2), substr($digits, 8, 2));
    }
}
