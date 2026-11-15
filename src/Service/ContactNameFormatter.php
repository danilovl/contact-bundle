<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Entity\{
    Contact,
    ContactValue
};
use Danilovl\ContactBundle\Model\ContactValueKind;

class ContactNameFormatter
{
    public function getDisplayName(Contact $contact): string
    {
        $fullName = $this->getFullName($contact);
        if ($fullName !== '') {
            return $fullName;
        }

        $fallbacks = [$contact->getNickname(), $contact->getCompany()];
        foreach ($fallbacks as $fallback) {
            if ($fallback !== null) {
                return $fallback;
            }
        }

        $values = $contact->getValues();
        $firstValue = $values->first();

        return $firstValue === false ? '' : $firstValue->getValue();
    }

    public function getValueText(ContactValue $value): string
    {
        $relatedContact = $value->getRelatedContact();
        if ($relatedContact === null) {
            return $value->getValue();
        }

        $relatedName = $this->getDisplayName($relatedContact);

        return $relatedName === '' ? $value->getValue() : $relatedName;
    }

    public function getFullName(Contact $contact): string
    {
        $parts = [
            $contact->getPrefix(),
            $contact->getFirstName(),
            $contact->getMiddleName(),
            $contact->getLastName(),
            $contact->getSuffix()
        ];

        return $this->joinParts($parts);
    }

    public function getSortName(Contact $contact): string
    {
        $parts = [$contact->getFirstName(), $contact->getLastName()];
        $sortName = $this->joinParts($parts);
        if ($sortName === '') {
            $sortName = $this->getDisplayName($contact);
        }

        return mb_strtolower($sortName);
    }

    public function getFingerprint(Contact $contact): string
    {
        $fullName = $this->getFullName($contact);
        $parts = [mb_strtolower($fullName)];

        $kinds = [ContactValueKind::EMAIL, ContactValueKind::PHONE];
        foreach ($kinds as $kind) {
            $values = $contact->getValuesByKind($kind);
            foreach ($values as $value) {
                $rawValue = $value->getValue();
                $normalizedValue = $value->getNormalizedValue();
                $parts[] = $normalizedValue ?? $this->normalizeValue($kind, $rawValue);
            }
        }

        sort($parts);

        return implode('|', $parts);
    }

    public function normalizeValue(ContactValueKind $kind, string $value): string
    {
        if ($kind === ContactValueKind::PHONE) {
            return preg_replace('~[^0-9+]~', '', $value) ?? $value;
        }

        return mb_strtolower($value);
    }

    /**
     * @param array<int, string|null> $parts
     */
    private function joinParts(array $parts): string
    {
        $filledParts = array_filter($parts, static function (?string $part): bool {
            return $part !== null && $part !== '';
        });

        return implode(' ', $filledParts);
    }
}
