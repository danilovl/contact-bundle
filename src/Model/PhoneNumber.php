<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Model;

final class PhoneNumber
{
    private const int MIN_DIGITS = 3;

    private const int NATIONAL_MAX_DIGITS = 10;

    private const int INTERNATIONAL_MIN_DIGITS = 11;

    public static function normalize(string $value, ?string $defaultCountryCode = null): ?string
    {
        $matches = [];
        preg_match('~^[^a-z;,#*]*~i', $value, $matches);

        $main = mb_trim($matches[0] ?? '');
        $hasPlus = str_starts_with($main, '+');
        $digits = preg_replace('~\D~', '', $main) ?? '';

        if (mb_strlen($digits) < self::MIN_DIGITS) {
            return null;
        }

        if ($hasPlus) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+' . mb_substr($digits, 2);
        }

        if ($defaultCountryCode !== null) {
            return self::applyCountryCode($digits, $defaultCountryCode);
        }

        $isInternational = mb_strlen($digits) >= self::INTERNATIONAL_MIN_DIGITS && $digits[0] !== '0';

        return $isInternational ? '+' . $digits : $digits;
    }

    private static function applyCountryCode(string $digits, string $countryCode): string
    {
        if (str_starts_with($digits, '0')) {
            $national = mb_substr($digits, 1);

            return '+' . $countryCode . $national;
        }

        if (mb_strlen($digits) <= self::NATIONAL_MAX_DIGITS) {
            return '+' . $countryCode . $digits;
        }

        return '+' . $digits;
    }
}
