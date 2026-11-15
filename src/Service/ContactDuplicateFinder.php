<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Entity\{
    Contact,
    ContactValue
};
use Danilovl\ContactBundle\Model\ContactValueKind;

readonly class ContactDuplicateFinder
{
    public function __construct(private ContactValueNormalizer $valueNormalizer) {}

    /**
     * @param list<Contact> $contacts
     * @return list<list<Contact>>
     */
    public function find(array $contacts): array
    {
        $parents = [];
        $keyOwners = [];

        foreach ($contacts as $contact) {
            $contactId = $contact->getId();
            $parents[$contactId] = $contactId;
        }

        foreach ($contacts as $contact) {
            $contactId = $contact->getId();
            $keys = $this->collectKeys($contact);

            foreach ($keys as $key) {
                if (!isset($keyOwners[$key])) {
                    $keyOwners[$key] = $contactId;

                    continue;
                }

                $parents = $this->union($parents, $keyOwners[$key], $contactId);
            }
        }

        return $this->createGroups($contacts, $parents);
    }

    /**
     * @return list<string>
     */
    private function collectKeys(Contact $contact): array
    {
        $keys = [];
        $kinds = [ContactValueKind::EMAIL, ContactValueKind::PHONE];

        foreach ($kinds as $kind) {
            $values = $contact->getValuesByKind($kind);

            foreach ($values as $value) {
                $key = $this->createKey($kind, $value);
                if ($key !== null) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    private function createKey(ContactValueKind $kind, ContactValue $value): ?string
    {
        $normalizedValue = $value->getNormalizedValue();
        $normalizedValue ??= $this->valueNormalizer->normalize($kind, $value->getValue());

        if ($normalizedValue === null || $normalizedValue === '') {
            return null;
        }

        return $kind->value . '|' . $normalizedValue;
    }

    /**
     * @param array<int, int> $parents
     * @return array<int, int>
     */
    private function union(array $parents, int $first, int $second): array
    {
        $firstRoot = $this->findRoot($parents, $first);
        $secondRoot = $this->findRoot($parents, $second);

        if ($firstRoot !== $secondRoot) {
            $parents[$secondRoot] = $firstRoot;
        }

        return $parents;
    }

    /**
     * @param array<int, int> $parents
     */
    private function findRoot(array $parents, int $id): int
    {
        while ($parents[$id] !== $id) {
            $id = $parents[$id];
        }

        return $id;
    }

    /**
     * @param list<Contact> $contacts
     * @param array<int, int> $parents
     * @return list<list<Contact>>
     */
    private function createGroups(array $contacts, array $parents): array
    {
        $groups = [];

        foreach ($contacts as $contact) {
            $root = $this->findRoot($parents, $contact->getId());
            $groups[$root][] = $contact;
        }

        $result = [];
        foreach ($groups as $group) {
            if (count($group) > 1) {
                $result[] = $group;
            }
        }

        return $result;
    }
}
