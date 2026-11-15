<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Dto;

final readonly class ContactDateData
{
    public function __construct(
        public string $date,
        public ?string $label
    ) {}
}
