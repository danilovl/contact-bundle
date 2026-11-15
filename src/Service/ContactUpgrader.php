<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Repository\ContactRepository;
use Danilovl\ContactBundle\Model\UidGenerator;
use Doctrine\ORM\EntityManagerInterface;

readonly class ContactUpgrader
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContactRepository $contactRepository,
        private ContactTagResolver $tagResolver,
        private ContactValueNormalizer $valueNormalizer
    ) {}

    /**
     * @return array{contacts: int, uids: int, photos: int, tags: int, values: int}
     */
    public function upgrade(): array
    {
        $contacts = $this->contactRepository->findByOwnerWithValues(null);
        $result = [
            'contacts' => count($contacts),
            'uids' => 0,
            'photos' => 0,
            'tags' => 0,
            'values' => 0
        ];

        foreach ($contacts as $contact) {
            if ($contact->getUid() === null) {
                $contact->setUid(UidGenerator::generate());
                $result['uids']++;
            }

            if ($contact->hasLegacyPhoto()) {
                $contact->setPhoto($contact->getPhoto());
                $result['photos']++;
            }

            $legacyTags = $contact->getLegacyTags();
            if (count($legacyTags) > 0) {
                $tags = $this->tagResolver->resolve($contact->getOwnerIdentifier(), $legacyTags);
                $contact->setTagEntities($tags);
                $contact->clearLegacyTags();
                $result['tags']++;
            }

            foreach ($contact->getValues() as $value) {
                $kind = $value->getKind();
                $normalizedValue = $this->valueNormalizer->normalize($kind, $value->getValue());
                if ($normalizedValue === $value->getNormalizedValue()) {
                    continue;
                }

                $value->setNormalizedValue($normalizedValue);
                $result['values']++;
            }
        }

        $this->entityManager->flush();

        return $result;
    }
}
