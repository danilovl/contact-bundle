<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Model;

final class PartialDate
{
    private const string FULL_PATTERN = '~^(\d{4})-(\d{2})-(\d{2})$~';

    private const string YEARLESS_PATTERN = '~^--(\d{2})-?(\d{2})$~';

    private const string COMPACT_PATTERN = '~^(\d{4})(\d{2})(\d{2})$~';

    private const int YEARLESS_CHECK_YEAR = 2_000;

    public static function normalize(string $value): ?string
    {
        $value = mb_trim($value);
        $datePart = mb_substr($value, 0, 10);

        $matches = [];
        $isFull = preg_match(self::FULL_PATTERN, $datePart, $matches) === 1;
        if ($isFull) {
            return self::createFull($matches[1], $matches[2], $matches[3]);
        }

        $isCompact = preg_match(self::COMPACT_PATTERN, $value, $matches) === 1;
        if ($isCompact) {
            return self::createFull($matches[1], $matches[2], $matches[3]);
        }

        $isYearless = preg_match(self::YEARLESS_PATTERN, $value, $matches) === 1;
        if ($isYearless) {
            return self::createYearless($matches[1], $matches[2]);
        }

        return null;
    }

    private static function createFull(string $year, string $month, string $day): ?string
    {
        $yearNumber = (int) $year;
        $monthNumber = (int) $month;
        $dayNumber = (int) $day;

        $isValid = checkdate($monthNumber, $dayNumber, $yearNumber);
        if (!$isValid) {
            return null;
        }

        return sprintf('%s-%s-%s', $year, $month, $day);
    }

    private static function createYearless(string $month, string $day): ?string
    {
        $monthNumber = (int) $month;
        $dayNumber = (int) $day;

        $isValid = checkdate($monthNumber, $dayNumber, self::YEARLESS_CHECK_YEAR);
        if (!$isValid) {
            return null;
        }

        return sprintf('--%s-%s', $month, $day);
    }
}
