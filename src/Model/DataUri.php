<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Model;

final readonly class DataUri
{
    private const string PATTERN = '~^data:(image/[a-z0-9.+-]+);base64,([a-z0-9+/=\s]+)$~i';

    private function __construct(
        public string $mimeType,
        public string $base64
    ) {}

    public static function parse(string $value): ?self
    {
        $matches = [];
        $isMatched = preg_match(self::PATTERN, $value, $matches) === 1;
        if (!$isMatched) {
            return null;
        }

        $mimeType = mb_strtolower($matches[1]);
        $base64 = preg_replace('~\s+~', '', $matches[2]) ?? '';

        return new self($mimeType, $base64);
    }

    public static function create(string $mimeType, string $base64): string
    {
        $cleanBase64 = preg_replace('~\s+~', '', $base64) ?? '';

        return sprintf('data:%s;base64,%s', $mimeType, $cleanBase64);
    }

    public function getBinary(): string
    {
        $binary = base64_decode($this->base64, true);

        return $binary === false ? '' : $binary;
    }

    public function getVCardType(): string
    {
        $type = mb_substr($this->mimeType, 6);

        return mb_strtoupper($type);
    }
}
