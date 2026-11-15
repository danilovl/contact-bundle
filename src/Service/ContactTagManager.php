<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Entity\ContactTag;
use Danilovl\ContactBundle\Exception\{
    ContactNotFoundException,
    ContactValidationException
};
use Danilovl\ContactBundle\Repository\ContactTagRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ContactTagManager
{
    private const int NAME_LENGTH = 64;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContactTagRepository $contactTagRepository,
        private OwnerResolver $ownerResolver
    ) {}

    /**
     * @return list<ContactTag>
     */
    public function list(): array
    {
        $scope = $this->ownerResolver->resolveScope();

        return $this->contactTagRepository->findByOwner($scope);
    }

    public function rename(int $id, string $name): ContactTag
    {
        $tag = $this->get($id);
        $newName = mb_substr(mb_trim($name), 0, self::NAME_LENGTH);

        if ($newName === '') {
            throw new ContactValidationException(['name' => 'Fill in a tag name.']);
        }

        $newKey = ContactTag::createKey($newName);
        $ownerIdentifier = $tag->getOwnerIdentifier();
        $sameTags = $this->contactTagRepository->findByOwnerAndKeys($ownerIdentifier, [$newKey]);
        $target = $this->findTarget($sameTags, $tag);

        if ($target === null) {
            $tag->rename($newName);
            $this->touchContacts($tag);
            $this->entityManager->flush();

            return $tag;
        }

        $this->moveContacts($tag, $target);
        $target->rename($newName);
        $this->entityManager->remove($tag);
        $this->entityManager->flush();

        return $target;
    }

    public function delete(int $id): void
    {
        $tag = $this->get($id);

        $this->touchContacts($tag);
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }

    private function get(int $id): ContactTag
    {
        $scope = $this->ownerResolver->resolveScope();
        $tag = $this->contactTagRepository->findOneByOwner($id, $scope);

        return $tag ?? throw ContactNotFoundException::create($id);
    }

    /**
     * @param list<ContactTag> $sameTags
     */
    private function findTarget(array $sameTags, ContactTag $source): ?ContactTag
    {
        foreach ($sameTags as $sameTag) {
            if ($sameTag->getId() !== $source->getId()) {
                return $sameTag;
            }
        }

        return null;
    }

    private function moveContacts(ContactTag $source, ContactTag $target): void
    {
        $contacts = $source->getContacts()->toArray();

        foreach ($contacts as $contact) {
            $contact->removeTagEntity($source);
            $contact->addTagEntity($target);
            $contact->touch();
        }
    }

    private function touchContacts(ContactTag $tag): void
    {
        $contacts = $tag->getContacts();

        foreach ($contacts as $contact) {
            $contact->touch();
        }
    }
}
