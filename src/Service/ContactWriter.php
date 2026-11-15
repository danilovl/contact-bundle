<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\Dto\{
    ContactData,
    ContactValueData
};
use Danilovl\ContactBundle\Entity\{
    Contact,
    ContactAddress,
    ContactDate,
    ContactValue
};
use Danilovl\ContactBundle\Model\ContactValueKind;
use Danilovl\ContactBundle\Repository\ContactRepository;

class ContactWriter
{
    public function __construct(
        private readonly ContactValueNormalizer $valueNormalizer,
        private readonly ContactTagResolver $tagResolver,
        private readonly ContactRepository $contactRepository,
        private readonly ContactNameFormatter $nameFormatter
    ) {}

    public function write(Contact $contact, ContactData $data): void
    {
        $contact->setPrefix($data->prefix);
        $contact->setFirstName($data->firstName);
        $contact->setMiddleName($data->middleName);
        $contact->setLastName($data->lastName);
        $contact->setSuffix($data->suffix);
        $contact->setNickname($data->nickname);
        $contact->setCompany($data->company);
        $contact->setDepartment($data->department);
        $contact->setJobTitle($data->jobTitle);
        $contact->setBirthday($data->birthday);
        $contact->setGender($data->gender);
        $contact->setPronouns($data->pronouns);
        $contact->setLanguage($data->language);
        $contact->setTimezone($data->timezone);
        $contact->setPhoto($data->photo);
        $contact->setNotes($data->notes);
        $contact->setFavorite($data->favorite);
        $contact->touch();

        $this->writeTags($contact, $data);
        $this->writeCustomFields($contact, $data);
        $this->writeWorkHistory($contact, $data);
        $this->writeValues($contact, $data);
        $this->writeAddresses($contact, $data);
        $this->writeDates($contact, $data);
    }

    private function writeTags(Contact $contact, ContactData $data): void
    {
        $ownerIdentifier = $contact->getOwnerIdentifier();
        $tags = $this->tagResolver->resolve($ownerIdentifier, $data->tags);

        $contact->setTagEntities($tags);
    }

    private function writeCustomFields(Contact $contact, ContactData $data): void
    {
        $fields = [];

        foreach ($data->customFields as $fieldData) {
            $fields[] = [
                'key' => $fieldData->key,
                'value' => $fieldData->value
            ];
        }

        $contact->setCustomFields($fields);
    }

    private function writeWorkHistory(Contact $contact, ContactData $data): void
    {
        $history = [];

        foreach ($data->workHistory as $workData) {
            $history[] = [
                'company' => $workData->company,
                'jobTitle' => $workData->jobTitle,
                'startDate' => $workData->startDate,
                'endDate' => $workData->endDate
            ];
        }

        $contact->setWorkHistory($history);
    }

    private function writeValues(Contact $contact, ContactData $data): void
    {
        $contact->clearValues();

        foreach ($data->values as $position => $valueData) {
            $relatedContact = $this->findRelatedContact($contact, $valueData);
            $text = $valueData->value;

            if ($relatedContact !== null) {
                $relatedName = $this->nameFormatter->getDisplayName($relatedContact);
                $text = $relatedName === '' ? $text : $relatedName;
            }

            $normalizedValue = $this->valueNormalizer->normalize($valueData->kind, $text);

            $value = new ContactValue(
                kind: $valueData->kind,
                value: $text,
                label: $valueData->label,
                primary: $valueData->primary,
                position: $position,
                normalizedValue: $normalizedValue,
                relatedContact: $relatedContact
            );

            $contact->addValue($value);
        }
    }

    private function findRelatedContact(Contact $contact, ContactValueData $valueData): ?Contact
    {
        $relatedContactId = $valueData->relatedContactId;
        if ($valueData->kind !== ContactValueKind::RELATION || $relatedContactId === null) {
            return null;
        }

        $isSelf = $contact->isPersisted() && $contact->getId() === $relatedContactId;
        if ($isSelf) {
            return null;
        }

        $ownerIdentifier = $contact->getOwnerIdentifier();

        return $this->contactRepository->findOneByOwner($relatedContactId, $ownerIdentifier);
    }

    private function writeAddresses(Contact $contact, ContactData $data): void
    {
        $contact->clearAddresses();

        foreach ($data->addresses as $position => $addressData) {
            $address = new ContactAddress;
            $address->setLabel($addressData->label);
            $address->setStreet($addressData->street);
            $address->setExtended($addressData->extended);
            $address->setCity($addressData->city);
            $address->setRegion($addressData->region);
            $address->setPostalCode($addressData->postalCode);
            $address->setCountry($addressData->country);
            $address->setPosition($position);

            $contact->addAddress($address);
        }
    }

    private function writeDates(Contact $contact, ContactData $data): void
    {
        $contact->clearDates();

        foreach ($data->dates as $position => $dateData) {
            $date = new ContactDate($dateData->date, $dateData->label, $position);
            $contact->addDate($date);
        }
    }
}
