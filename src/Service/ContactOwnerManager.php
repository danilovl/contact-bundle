<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Repository\{
    ContactRepository,
    ContactTagRepository
};
use Doctrine\ORM\EntityManagerInterface;

readonly class ContactOwnerManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContactRepository $contactRepository,
        private ContactTagRepository $contactTagRepository
    ) {}

    public function reassign(string $from, string $to): int
    {
        $this->reassignTags($from, $to);

        $contacts = $this->contactRepository->findByOwnerWithValues($from);
        foreach ($contacts as $contact) {
            $contact->setOwnerIdentifier($to);
        }

        $this->entityManager->flush();

        return count($contacts);
    }

    public function purge(string $owner): int
    {
        $contacts = $this->contactRepository->findByOwnerWithValues($owner);
        foreach ($contacts as $contact) {
            $this->entityManager->remove($contact);
        }

        $this->entityManager->flush();

        $tags = $this->contactTagRepository->findByOwner($owner);
        foreach ($tags as $tag) {
            $this->entityManager->remove($tag);
        }

        $this->entityManager->flush();

        return count($contacts);
    }

    private function reassignTags(string $from, string $to): void
    {
        $tags = $this->contactTagRepository->findByOwner($from);

        foreach ($tags as $tag) {
            $targets = $this->contactTagRepository->findByOwnerAndKeys($to, [$tag->getNameKey()]);
            $target = $targets[0] ?? null;

            if ($target === null) {
                $tag->setOwnerIdentifier($to);

                continue;
            }

            $contacts = $tag->getContacts()->toArray();
            foreach ($contacts as $contact) {
                $contact->removeTagEntity($tag);
                $contact->addTagEntity($target);
            }

            $this->entityManager->remove($tag);
        }
    }
}
