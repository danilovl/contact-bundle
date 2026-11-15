<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service\VCard;

final readonly class VCardProperty
{
    /**
     * @param array<string, list<string>> $parameters
     */
    public function __construct(
        public ?string $group,
        public string $name,
        public array $parameters,
        public string $value
    ) {}

    /**
     * @return list<string>
     */
    public function getTypes(): array
    {
        return $this->parameters['TYPE'] ?? [];
    }

    public function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]);
    }
}
