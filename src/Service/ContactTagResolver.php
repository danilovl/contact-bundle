<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Entity\ContactTag;
use Danilovl\ContactBundle\Repository\ContactTagRepository;
use Doctrine\ORM\EntityManagerInterface;

class ContactTagResolver
{
    /**
     * @var array<string, ContactTag>
     */
    private array $cache = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactTagRepository $contactTagRepository
    ) {}

    /**
     * @param list<string> $names
     * @return list<ContactTag>
     */
    public function resolve(string $ownerIdentifier, array $names): array
    {
        $keys = [];
        foreach ($names as $name) {
            $keys[] = ContactTag::createKey($name);
        }

        $missingKeys = [];
        foreach ($keys as $key) {
            $cacheKey = $ownerIdentifier . '|' . $key;
            if (!isset($this->cache[$cacheKey])) {
                $missingKeys[] = $key;
            }
        }

        $existingTags = $this->contactTagRepository->findByOwnerAndKeys($ownerIdentifier, $missingKeys);
        foreach ($existingTags as $existingTag) {
            $cacheKey = $ownerIdentifier . '|' . $existingTag->getNameKey();
            $this->cache[$cacheKey] = $existingTag;
        }

        $result = [];
        foreach ($names as $name) {
            $result[] = $this->getOrCreate($ownerIdentifier, $name);
        }

        return $result;
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    private function getOrCreate(string $ownerIdentifier, string $name): ContactTag
    {
        $key = ContactTag::createKey($name);
        $cacheKey = $ownerIdentifier . '|' . $key;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $tag = new ContactTag($ownerIdentifier, $name);
        $this->entityManager->persist($tag);
        $this->cache[$cacheKey] = $tag;

        return $tag;
    }
}
