<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Dto\ContactData;
use Danilovl\ContactBundle\Entity\Contact;
use Danilovl\ContactBundle\Exception\{
    ContactNotFoundException,
    ContactValidationException
};
use Danilovl\ContactBundle\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ContactManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContactRepository $contactRepository,
        private ContactWriter $contactWriter,
        private ContactNameFormatter $nameFormatter,
        private OwnerResolver $ownerResolver,
        private ContactDuplicateFinder $duplicateFinder,
        private ContactMerger $contactMerger
    ) {}

    /**
     * @return list<Contact>
     */
    public function list(?string $ownerIdentifier = null, ?string $tag = null): array
    {
        $scope = $ownerIdentifier ?? $this->ownerResolver->resolveScope();

        return $this->contactRepository->findByOwnerWithValues($scope, $tag);
    }

    /**
     * @return list<Contact>
     */
    public function listFull(?string $ownerIdentifier = null): array
    {
        $scope = $ownerIdentifier ?? $this->ownerResolver->resolveScope();

        return $this->contactRepository->findByOwnerFull($scope);
    }

    public function get(int $id): Contact
    {
        $scope = $this->ownerResolver->resolveScope();
        $contact = $this->contactRepository->findOneByOwner($id, $scope);

        return $contact ?? throw ContactNotFoundException::create($id);
    }

    public function create(ContactData $data, ?string $ownerIdentifier = null): Contact
    {
        $ownerIdentifier ??= $this->ownerResolver->resolve();

        $contact = new Contact($ownerIdentifier);
        $this->contactWriter->write($contact, $data);

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $contact;
    }

    public function update(int $id, ContactData $data): Contact
    {
        $contact = $this->get($id);

        $this->contactWriter->write($contact, $data);
        $this->entityManager->flush();

        return $contact;
    }

    public function setFavorite(int $id, bool $favorite): Contact
    {
        $contact = $this->get($id);

        $contact->setFavorite($favorite);
        $contact->touch();
        $this->entityManager->flush();

        return $contact;
    }

    public function delete(int $id): void
    {
        $contact = $this->get($id);

        $this->entityManager->remove($contact);
        $this->entityManager->flush();
    }

    /**
     * @return list<list<Contact>>
     */
    public function findDuplicates(): array
    {
        $contacts = $this->list();

        return $this->duplicateFinder->find($contacts);
    }

    /**
     * @param list<int> $ids
     */
    public function merge(int $primaryId, array $ids): Contact
    {
        $primary = $this->get($primaryId);
        $others = [];

        foreach ($ids as $id) {
            if ($id === $primaryId || isset($others[$id])) {
                continue;
            }

            $other = $this->get($id);
            $isSameOwner = $other->getOwnerIdentifier() === $primary->getOwnerIdentifier();
            if (!$isSameOwner) {
                throw new ContactValidationException(['ids' => 'Contacts of different owners cannot be merged.']);
            }

            $others[$id] = $other;
        }

        if (count($others) === 0) {
            throw new ContactValidationException(['ids' => 'Select at least one more contact to merge.']);
        }

        $otherContacts = array_values($others);
        $this->contactMerger->merge($primary, $otherContacts);

        return $primary;
    }

    /**
     * @param list<ContactData> $items
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(array $items, ?string $ownerIdentifier = null): array
    {
        $ownerIdentifier ??= $this->ownerResolver->resolve();

        $existingContacts = $this->contactRepository->findByOwnerWithValues($ownerIdentifier);
        $fingerprints = [];
        $contactsByUid = [];

        foreach ($existingContacts as $existingContact) {
            $fingerprint = $this->nameFormatter->getFingerprint($existingContact);
            $fingerprints[$fingerprint] = true;

            $existingUid = $existingContact->getUid();
            if ($existingUid !== null) {
                $contactsByUid[$existingUid] = $existingContact;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($items as $data) {
            $uid = $data->uid;
            $existingContact = $uid === null ? null : $contactsByUid[$uid] ?? null;

            if ($existingContact !== null) {
                $isNewer = $data->revisionAt === null || $data->revisionAt > $existingContact->getUpdatedAt();
                if (!$isNewer) {
                    $skipped++;

                    continue;
                }

                $this->contactWriter->write($existingContact, $data);
                $updated++;

                continue;
            }

            $contact = new Contact($ownerIdentifier, $uid);
            $this->contactWriter->write($contact, $data);

            $fingerprint = $this->nameFormatter->getFingerprint($contact);
            if (isset($fingerprints[$fingerprint])) {
                $skipped++;

                continue;
            }

            $fingerprints[$fingerprint] = true;
            $contactUid = $contact->getUid();
            if ($contactUid !== null) {
                $contactsByUid[$contactUid] = $contact;
            }

            $this->entityManager->persist($contact);
            $created++;
        }

        $this->entityManager->flush();

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped
        ];
    }
}
