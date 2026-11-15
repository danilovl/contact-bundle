<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Dto;

final readonly class ContactWorkData
{
    public function __construct(
        public ?string $company,
        public ?string $jobTitle,
        public ?string $startDate,
        public ?string $endDate
    ) {}
}
