<?php

namespace Lsr\Form;

final class Validator
{
    public static function isEmail(string $value): bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public static function isPhone(string $value): bool
    {
        $digits = self::normalizePhone($value);
        $len = strlen($digits);
        return $len >= 10 && $len <= 15;
    }

    public static function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value);
    }
}