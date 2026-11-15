<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Entity\{
    Contact,
    ContactAddress,
    ContactDate,
    ContactValue
};
use Danilovl\ContactBundle\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ContactMerger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContactRepository $contactRepository,
        private ContactTagResolver $tagResolver
    ) {}

    /**
     * @param list<Contact> $others
     */
    public function merge(Contact $primary, array $others): void
    {
        foreach ($others as $other) {
            $this->mergeScalars($primary, $other);
            $this->mergeTags($primary, $other);
            $this->mergeValues($primary, $other);
            $this->mergeAddresses($primary, $other);
            $this->mergeDates($primary, $other);
            $this->mergeWorkHistory($primary, $other);
            $this->mergeCustomFields($primary, $other);
            $this->repointRelations($primary, $other);
        }

        $primary->touch();

        foreach ($others as $other) {
            $this->entityManager->remove($other);
        }

        $this->entityManager->flush();
    }

    private function mergeScalars(Contact $primary, Contact $other): void
    {
        $primary->setPrefix($primary->getPrefix() ?? $other->getPrefix());
        $primary->setFirstName($primary->getFirstName() ?? $other->getFirstName());
        $primary->setMiddleName($primary->getMiddleName() ?? $other->getMiddleName());
        $primary->setLastName($primary->getLastName() ?? $other->getLastName());
        $primary->setSuffix($primary->getSuffix() ?? $other->getSuffix());
        $primary->setNickname($primary->getNickname() ?? $other->getNickname());
        $primary->setCompany($primary->getCompany() ?? $other->getCompany());
        $primary->setDepartment($primary->getDepartment() ?? $other->getDepartment());
        $primary->setJobTitle($primary->getJobTitle() ?? $other->getJobTitle());
        $primary->setBirthday($primary->getBirthday() ?? $other->getBirthday());
        $primary->setGender($primary->getGender() ?? $other->getGender());
        $primary->setPronouns($primary->getPronouns() ?? $other->getPronouns());
        $primary->setLanguage($primary->getLanguage() ?? $other->getLanguage());
        $primary->setTimezone($primary->getTimezone() ?? $other->getTimezone());
        $primary->setFavorite($primary->isFavorite() || $other->isFavorite());

        if (!$primary->hasPhoto()) {
            $primary->setPhoto($other->getPhoto());
        }

        $this->mergeNotes($primary, $other);
    }

    private function mergeNotes(Contact $primary, Contact $other): void
    {
        $primaryNotes = $primary->getNotes();
        $otherNotes = $other->getNotes();

        if ($otherNotes === null || $otherNotes === $primaryNotes) {
            return;
        }

        if ($primaryNotes === null) {
            $primary->setNotes($otherNotes);

            return;
        }

        $primary->setNotes($primaryNotes . "\n\n" . $otherNotes);
    }

    private function mergeTags(Contact $primary, Contact $other): void
    {
        $names = array_merge($primary->getTags(), $other->getTags());
        $ownerIdentifier = $primary->getOwnerIdentifier();
        $tags = $this->tagResolver->resolve($ownerIdentifier, $names);

        $primary->setTagEntities($tags);
    }

    private function mergeValues(Contact $primary, Contact $other): void
    {
        $knownKeys = [];
        foreach ($primary->getValues() as $value) {
            $knownKeys[$this->createValueKey($value)] = true;
        }

        foreach ($other->getValues() as $value) {
            $relatedContact = $value->getRelatedContact();
            $isLinkToMerged = $relatedContact !== null
                && ($this->isSame($relatedContact, $primary) || $this->isSame($relatedContact, $other));

            if ($isLinkToMerged) {
                continue;
            }

            $key = $this->createValueKey($value);
            if (isset($knownKeys[$key])) {
                continue;
            }

            $knownKeys[$key] = true;
            $position = $primary->getValues()->count();

            $copy = new ContactValue(
                kind: $value->getKind(),
                value: $value->getValue(),
                label: $value->getLabel(),
                primary: false,
                position: $position,
                normalizedValue: $value->getNormalizedValue(),
                relatedContact: $relatedContact
            );

            $primary->addValue($copy);
        }
    }

    private function createValueKey(ContactValue $value): string
    {
        $relatedContact = $value->getRelatedContact();
        if ($relatedContact !== null) {
            return $value->getKind()->value . '|contact|' . $relatedContact->getId();
        }

        $normalizedValue = $value->getNormalizedValue() ?? mb_strtolower($value->getValue());

        return $value->getKind()->value . '|' . $normalizedValue;
    }

    private function isSame(Contact $first, Contact $second): bool
    {
        return $first->getId() === $second->getId();
    }

    private function mergeAddresses(Contact $primary, Contact $other): void
    {
        $knownKeys = [];
        foreach ($primary->getAddresses() as $address) {
            $knownKeys[$this->createAddressKey($address)] = true;
        }

        foreach ($other->getAddresses() as $address) {
            $key = $this->createAddressKey($address);
            if (isset($knownKeys[$key])) {
                continue;
            }

            $knownKeys[$key] = true;

            $copy = new ContactAddress;
            $copy->setLabel($address->getLabel());
            $copy->setStreet($address->getStreet());
            $copy->setExtended($address->getExtended());
            $copy->setCity($address->getCity());
            $copy->setRegion($address->getRegion());
            $copy->setPostalCode($address->getPostalCode());
            $copy->setCountry($address->getCountry());
            $copy->setPosition($primary->getAddresses()->count());

            $primary->addAddress($copy);
        }
    }

    private function createAddressKey(ContactAddress $address): string
    {
        $parts = [
            $address->getStreet(),
            $address->getExtended(),
            $address->getCity(),
            $address->getRegion(),
            $address->getPostalCode(),
            $address->getCountry()
        ];

        return mb_strtolower(implode('|', $parts));
    }

    private function mergeDates(Contact $primary, Contact $other): void
    {
        $knownKeys = [];
        foreach ($primary->getDates() as $date) {
            $knownKeys[$this->createDateKey($date)] = true;
        }

        foreach ($other->getDates() as $date) {
            $key = $this->createDateKey($date);
            if (isset($knownKeys[$key])) {
                continue;
            }

            $knownKeys[$key] = true;
            $position = $primary->getDates()->count();
            $copy = new ContactDate($date->getDate(), $date->getLabel(), $position);

            $primary->addDate($copy);
        }
    }

    private function createDateKey(ContactDate $date): string
    {
        return $date->getDate() . '|' . mb_strtolower($date->getLabel() ?? '');
    }

    private function mergeWorkHistory(Contact $primary, Contact $other): void
    {
        $history = $primary->getWorkHistory();
        $knownKeys = [];

        foreach ($history as $item) {
            $knownKeys[serialize($item)] = true;
        }

        foreach ($other->getWorkHistory() as $item) {
            $key = serialize($item);
            if (isset($knownKeys[$key])) {
                continue;
            }

            $knownKeys[$key] = true;
            $history[] = $item;
        }

        $primary->setWorkHistory($history);
    }

    private function mergeCustomFields(Contact $primary, Contact $other): void
    {
        $fields = $primary->getCustomFields();
        $knownKeys = [];

        foreach ($fields as $field) {
            $knownKeys[$field['key']] = true;
        }

        foreach ($other->getCustomFields() as $field) {
            if (isset($knownKeys[$field['key']])) {
                continue;
            }

            $knownKeys[$field['key']] = true;
            $fields[] = $field;
        }

        $primary->setCustomFields($fields);
    }

    private function repointRelations(Contact $primary, Contact $other): void
    {
        $relations = $this->contactRepository->findRelationsTo($other);

        foreach ($relations as $relation) {
            $owner = $relation->getContact();

            if ($this->isSame($owner, $other)) {
                continue;
            }

            if ($this->isSame($owner, $primary)) {
                $primary->removeValue($relation);

                continue;
            }

            $relation->setRelatedContact($primary);
        }
    }
}
