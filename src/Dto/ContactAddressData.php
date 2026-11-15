<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Dto;

final readonly class ContactAddressData
{
    public function __construct(
        public ?string $label,
        public ?string $street,
        public ?string $extended,
        public ?string $city,
        public ?string $region,
        public ?string $postalCode,
        public ?string $country
    ) {}
}
