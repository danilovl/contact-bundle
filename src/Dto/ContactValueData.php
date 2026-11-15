<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Dto;

use Danilovl\ContactBundle\Model\ContactValueKind;

final readonly class ContactValueData
{
    public function __construct(
        public ContactValueKind $kind,
        public string $value,
        public ?string $label,
        public bool $primary,
        public ?int $relatedContactId = null
    ) {}
}
