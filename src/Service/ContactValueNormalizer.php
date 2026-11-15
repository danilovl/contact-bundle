<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\DependencyInjection\ContactConfig;
use Danilovl\ContactBundle\Model\{
    ContactValueKind,
    PhoneNumber
};

readonly class ContactValueNormalizer
{
    public function __construct(private ContactConfig $config) {}

    public function normalize(ContactValueKind $kind, string $value): ?string
    {
        if ($kind === ContactValueKind::EMAIL) {
            return mb_strtolower(mb_trim($value));
        }

        if ($kind === ContactValueKind::PHONE) {
            return PhoneNumber::normalize($value, $this->config->phoneDefaultCountryCode);
        }

        return null;
    }
}
