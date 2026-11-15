<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Dto;

use DateTimeImmutable;

final readonly class ContactData
{
    /**
     * @param list<string> $tags
     * @param list<ContactValueData> $values
     * @param list<ContactAddressData> $addresses
     * @param list<ContactDateData> $dates
     * @param list<ContactWorkData> $workHistory
     * @param list<ContactCustomFieldData> $customFields
     */
    public function __construct(
        public ?string $prefix,
        public ?string $firstName,
        public ?string $middleName,
        public ?string $lastName,
        public ?string $suffix,
        public ?string $nickname,
        public ?string $company,
        public ?string $department,
        public ?string $jobTitle,
        public ?string $birthday,
        public ?string $photo,
        public ?string $notes,
        public bool $favorite,
        public array $tags,
        public array $values,
        public array $addresses,
        public array $dates,
        public array $workHistory = [],
        public ?string $gender = null,
        public ?string $pronouns = null,
        public ?string $language = null,
        public ?string $timezone = null,
        public array $customFields = [],
        public ?string $uid = null,
        public ?DateTimeImmutable $revisionAt = null
    ) {}

    public function isEmpty(): bool
    {
        $names = [
            $this->prefix,
            $this->firstName,
            $this->middleName,
            $this->lastName,
            $this->suffix,
            $this->nickname,
            $this->company
        ];

        foreach ($names as $name) {
            if ($name !== null) {
                return false;
            }
        }

        return count($this->values) === 0;
    }
}
