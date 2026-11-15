<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Dto\ContactData;
use Danilovl\ContactBundle\Service\VCard\VCardParser;

readonly class ContactImporter
{
    public function __construct(
        private VCardParser $vCardParser,
        private ContactDataFactory $contactDataFactory,
        private ContactManager $contactManager
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int, invalid: int}
     */
    public function import(string $content, ?string $ownerIdentifier = null): array
    {
        $payloads = $this->vCardParser->parse($content);

        $items = [];
        $invalid = 0;

        foreach ($payloads as $payload) {
            $data = $this->contactDataFactory->create($payload, false);
            if ($data->isEmpty()) {
                $invalid++;

                continue;
            }

            $items[] = $data;
        }

        $result = $this->importItems($items, $ownerIdentifier);
        $result['invalid'] = $invalid;

        return $result;
    }

    /**
     * @param list<ContactData> $items
     * @return array{created: int, updated: int, skipped: int}
     */
    private function importItems(array $items, ?string $ownerIdentifier): array
    {
        return $this->contactManager->import($items, $ownerIdentifier);
    }
}
