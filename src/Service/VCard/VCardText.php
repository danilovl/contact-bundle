<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service\VCard;

final class VCardText
{
    private const int LINE_LENGTH = 75;

    public static function escape(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $search = ['\\', "\r\n", "\n", "\r", ',', ';'];
        $replace = ['\\\\', '\n', '\n', '\n', '\,', '\;'];

        return str_replace($search, $replace, $value);
    }

    public static function unescape(string $value): string
    {
        $result = '';
        $length = mb_strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $char = mb_substr($value, $index, 1);
            if ($char !== '\\' || $index === $length - 1) {
                $result .= $char;

                continue;
            }

            $index++;
            $next = mb_substr($value, $index, 1);
            $result .= match ($next) {
                'n', 'N' => "\n",
                default => $next
            };
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    public static function splitUnescaped(string $value, string $separator): array
    {
        $parts = [];
        $current = '';
        $length = mb_strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $char = mb_substr($value, $index, 1);
            if ($char === '\\' && $index < $length - 1) {
                $index++;
                $current .= $char . mb_substr($value, $index, 1);

                continue;
            }

            if ($char === $separator) {
                $parts[] = $current;
                $current = '';

                continue;
            }

            $current .= $char;
        }

        $parts[] = $current;

        return $parts;
    }

    public static function fold(string $line): string
    {
        $bytes = mb_strlen($line);
        if ($bytes <= self::LINE_LENGTH) {
            return $line;
        }

        $result = '';
        $chunk = '';
        $limit = self::LINE_LENGTH;
        $characters = mb_str_split($line);

        foreach ($characters as $character) {
            $chunkBytes = mb_strlen($chunk);
            $characterBytes = mb_strlen($character);

            if ($chunkBytes + $characterBytes > $limit) {
                $result .= $chunk . "\r\n ";
                $chunk = '';
                $limit = self::LINE_LENGTH - 1;
            }

            $chunk .= $character;
        }

        return $result . $chunk;
    }
}
