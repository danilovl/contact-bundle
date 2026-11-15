<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Model;

final class UidGenerator
{
    public static function generate(): string
    {
        $bytes = random_bytes(16);
        $versionByte = (ord($bytes[6]) & 0x0f) | 0x40;
        $variantByte = (ord($bytes[8]) & 0x3f) | 0x80;
        $bytes[6] = chr($versionByte);
        $bytes[8] = chr($variantByte);

        $hex = bin2hex($bytes);
        $parts = [
            mb_substr($hex, 0, 8),
            mb_substr($hex, 8, 4),
            mb_substr($hex, 12, 4),
            mb_substr($hex, 16, 4),
            mb_substr($hex, 20, 12)
        ];

        return implode('-', $parts);
    }
}
