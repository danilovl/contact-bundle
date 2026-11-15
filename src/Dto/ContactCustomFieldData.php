<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Dto;

final readonly class ContactCustomFieldData
{
    public function __construct(
        public string $key,
        public string $value
    ) {}
}
