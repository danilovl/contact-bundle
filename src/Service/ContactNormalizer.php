<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use DateTimeInterface;
use Danilovl\ContactBundle\Entity\{
    Contact,
    ContactAddress,
    ContactDate,
    ContactValue
};
use Danilovl\ContactBundle\Model\ContactValueKind;

readonly class ContactNormalizer
{
    public function __construct(private ContactNameFormatter $nameFormatter) {}

    /**
     * @return array<string, mixed>
     */
    public function normalizeSummary(Contact $contact): array
    {
        $displayName = $this->nameFormatter->getDisplayName($contact);
        $sortName = $this->nameFormatter->getSortName($contact);
        $emails = $this->normalizeValueStrings($contact, ContactValueKind::EMAIL);
        $phones = $this->normalizeValueStrings($contact, ContactValueKind::PHONE);
        $photoVersion = $contact->getUpdatedAt()->getTimestamp();
        $createdAt = $contact->getCreatedAt()->format(DateTimeInterface::ATOM);
        $updatedAt = $contact->getUpdatedAt()->format(DateTimeInterface::ATOM);

        return [
            'id' => $contact->getId(),
            'owner' => $contact->getOwnerIdentifier(),
            'uid' => $contact->getUid(),
            'revision' => $contact->getRevision(),
            'displayName' => $displayName,
            'sortName' => $sortName,
            'firstName' => $contact->getFirstName(),
            'lastName' => $contact->getLastName(),
            'nickname' => $contact->getNickname(),
            'company' => $contact->getCompany(),
            'jobTitle' => $contact->getJobTitle(),
            'birthday' => $contact->getBirthday(),
            'favorite' => $contact->isFavorite(),
            'tags' => $contact->getTags(),
            'emails' => $emails,
            'phones' => $phones,
            'hasPhoto' => $contact->hasPhoto(),
            'photoVersion' => $photoVersion,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeFull(Contact $contact): array
    {
        $result = $this->normalizeSummary($contact);
        $result['prefix'] = $contact->getPrefix();
        $result['middleName'] = $contact->getMiddleName();
        $result['suffix'] = $contact->getSuffix();
        $result['department'] = $contact->getDepartment();
        $result['notes'] = $contact->getNotes();
        $result['photo'] = $contact->getPhoto();
        $result['gender'] = $contact->getGender();
        $result['pronouns'] = $contact->getPronouns();
        $result['language'] = $contact->getLanguage();
        $result['timezone'] = $contact->getTimezone();
        $result['customFields'] = $contact->getCustomFields();

        $kinds = ContactValueKind::cases();
        foreach ($kinds as $kind) {
            $key = $kind->collectionKey();
            $result[$key] = $this->normalizeValues($contact, $kind);
        }

        $addresses = $contact->getAddresses()->getValues();
        $result['addresses'] = array_map($this->normalizeAddress(...), $addresses);

        $dates = $contact->getDates()->getValues();
        $result['dates'] = array_map($this->normalizeDate(...), $dates);
        $result['workHistory'] = $contact->getWorkHistory();

        return $result;
    }

    /**
     * @return list<array{label: string|null, value: string, primary: bool, relatedContactId: int|null}>
     */
    private function normalizeValues(Contact $contact, ContactValueKind $kind): array
    {
        $result = [];
        $values = $contact->getValuesByKind($kind);

        foreach ($values as $value) {
            $result[] = $this->normalizeValue($value);
        }

        return $result;
    }

    /**
     * @return array{label: string|null, value: string, primary: bool, relatedContactId: int|null}
     */
    private function normalizeValue(ContactValue $value): array
    {
        $relatedContact = $value->getRelatedContact();
        $relatedContactId = $relatedContact?->getId();
        $text = $this->nameFormatter->getValueText($value);

        return [
            'label' => $value->getLabel(),
            'value' => $text,
            'primary' => $value->isPrimary(),
            'relatedContactId' => $relatedContactId
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeValueStrings(Contact $contact, ContactValueKind $kind): array
    {
        $result = [];
        $values = $contact->getValuesByKind($kind);

        foreach ($values as $value) {
            $result[] = $this->nameFormatter->getValueText($value);
        }

        return $result;
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizeAddress(ContactAddress $address): array
    {
        return [
            'label' => $address->getLabel(),
            'street' => $address->getStreet(),
            'extended' => $address->getExtended(),
            'city' => $address->getCity(),
            'region' => $address->getRegion(),
            'postalCode' => $address->getPostalCode(),
            'country' => $address->getCountry()
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizeDate(ContactDate $date): array
    {
        return [
            'label' => $date->getLabel(),
            'date' => $date->getDate()
        ];
    }
}
