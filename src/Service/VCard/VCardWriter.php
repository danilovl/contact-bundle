<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service\VCard;

use DateTimeZone;
use Danilovl\ContactBundle\Entity\{
    Contact,
    ContactAddress,
    ContactDate,
    ContactValue
};
use Danilovl\ContactBundle\Model\{
    ContactValueKind,
    DataUri
};
use Danilovl\ContactBundle\Service\ContactNameFormatter;

class VCardWriter
{
    private const string LINE_BREAK = "\r\n";

    private const string FAVORITE_PROPERTY = 'X-DANILOVL-FAVORITE';

    private int $itemIndex = 0;

    /**
     * @var list<string>
     */
    private array $lines = [];

    public function __construct(private readonly ContactNameFormatter $nameFormatter) {}

    /**
     * @param iterable<Contact> $contacts
     */
    public function writeAll(iterable $contacts): string
    {
        $result = '';
        foreach ($contacts as $contact) {
            $result .= $this->write($contact);
        }

        return $result;
    }

    public function write(Contact $contact): string
    {
        $this->itemIndex = 0;
        $this->lines = [];

        $this->addLine('BEGIN:VCARD');
        $this->addLine('VERSION:3.0');
        $this->addLine('PRODID:-//danilovl//contact-bundle//EN');

        $this->writeIdentity($contact);
        $this->writeName($contact);
        $this->writeOrganization($contact);
        $this->writeValues($contact);
        $this->writeAddresses($contact);
        $this->writeDates($contact);
        $this->writeOther($contact);

        $this->addLine('END:VCARD');

        $foldedLines = array_map(VCardText::fold(...), $this->lines);

        return implode(self::LINE_BREAK, $foldedLines) . self::LINE_BREAK;
    }

    private function writeIdentity(Contact $contact): void
    {
        $uid = $contact->getUid();
        if ($uid !== null) {
            $this->addLine('UID:' . VCardText::escape($uid));
        }

        $updatedAt = $contact->getUpdatedAt();
        $utcUpdatedAt = $updatedAt->setTimezone(new DateTimeZone('UTC'));
        $this->addLine('REV:' . $utcUpdatedAt->format('Ymd\THis\Z'));
    }

    private function writeName(Contact $contact): void
    {
        $nameParts = [
            $contact->getLastName(),
            $contact->getFirstName(),
            $contact->getMiddleName(),
            $contact->getPrefix(),
            $contact->getSuffix()
        ];
        $escapedNameParts = array_map(VCardText::escape(...), $nameParts);
        $this->addLine('N:' . implode(';', $escapedNameParts));

        $displayName = $this->nameFormatter->getDisplayName($contact);
        $this->addLine('FN:' . VCardText::escape($displayName));

        $nickname = $contact->getNickname();
        if ($nickname !== null) {
            $this->addLine('NICKNAME:' . VCardText::escape($nickname));
        }

        $birthday = $contact->getBirthday();
        if ($birthday !== null) {
            $this->addLine('BDAY:' . $birthday);
        }
    }

    private function writeOrganization(Contact $contact): void
    {
        $company = $contact->getCompany();
        $department = $contact->getDepartment();

        if ($company !== null || $department !== null) {
            $company = VCardText::escape($company);
            $department = VCardText::escape($department);
            $organization = $department === '' ? $company : $company . ';' . $department;

            $this->addLine('ORG:' . $organization);
        }

        $jobTitle = $contact->getJobTitle();
        if ($jobTitle !== null) {
            $this->addLine('TITLE:' . VCardText::escape($jobTitle));
        }
    }

    private function writeValues(Contact $contact): void
    {
        $values = $contact->getValues();

        foreach ($values as $value) {
            match ($value->getKind()) {
                ContactValueKind::EMAIL => $this->writeTypedValue('EMAIL', ['INTERNET'], $value),
                ContactValueKind::PHONE => $this->writeTypedValue('TEL', [], $value),
                ContactValueKind::URL => $this->writeLabeledValue('URL', $value->getValue(), $value->getLabel()),
                ContactValueKind::MESSENGER => $this->writeMessenger($value),
                ContactValueKind::RELATION => $this->writeLabeledValue('X-ABRELATEDNAMES', $this->nameFormatter->getValueText($value), $value->getLabel())
            };
        }
    }

    /**
     * @param list<string> $baseTypes
     */
    private function writeTypedValue(string $property, array $baseTypes, ContactValue $value): void
    {
        $types = $baseTypes;
        $label = $value->getLabel();
        $standardType = $this->findStandardType($label);

        if ($standardType !== null) {
            $types[] = $standardType;
        }

        if ($value->isPrimary()) {
            $types[] = 'PREF';
        }

        $typeParameter = count($types) > 0 ? ';TYPE=' . implode(',', $types) : '';
        $escapedValue = VCardText::escape($value->getValue());

        if ($label === null || $standardType !== null) {
            $this->addLine($property . $typeParameter . ':' . $escapedValue);

            return;
        }

        $group = $this->nextGroup();
        $this->addLine($group . $property . $typeParameter . ':' . $escapedValue);
        $this->addLine($group . 'X-ABLabel:' . VCardText::escape($label));
    }

    private function writeMessenger(ContactValue $value): void
    {
        $label = $value->getLabel();
        $serviceParameter = $label === null ? '' : ';X-SERVICE-TYPE=' . $this->escapeParameter($label);
        $escapedValue = VCardText::escape($value->getValue());

        $this->addLine('IMPP' . $serviceParameter . ':' . $escapedValue);
    }

    private function writeLabeledValue(string $property, string $value, ?string $label): void
    {
        $escapedValue = VCardText::escape($value);
        if ($label === null) {
            $this->addLine($property . ':' . $escapedValue);

            return;
        }

        $group = $this->nextGroup();
        $this->addLine($group . $property . ':' . $escapedValue);
        $this->addLine($group . 'X-ABLabel:' . VCardText::escape($label));
    }

    private function writeAddresses(Contact $contact): void
    {
        $addresses = $contact->getAddresses();

        foreach ($addresses as $address) {
            $this->writeAddress($address);
        }
    }

    private function writeAddress(ContactAddress $address): void
    {
        $parts = [
            '',
            $address->getExtended(),
            $address->getStreet(),
            $address->getCity(),
            $address->getRegion(),
            $address->getPostalCode(),
            $address->getCountry()
        ];
        $escapedParts = array_map(VCardText::escape(...), $parts);
        $addressValue = implode(';', $escapedParts);

        $label = $address->getLabel();
        $standardType = $this->findStandardType($label);

        if ($label === null || $standardType !== null) {
            $typeParameter = $standardType === null ? '' : ';TYPE=' . $standardType;
            $this->addLine('ADR' . $typeParameter . ':' . $addressValue);

            return;
        }

        $group = $this->nextGroup();
        $this->addLine($group . 'ADR:' . $addressValue);
        $this->addLine($group . 'X-ABLabel:' . VCardText::escape($label));
    }

    private function writeDates(Contact $contact): void
    {
        $dates = $contact->getDates();

        foreach ($dates as $date) {
            $this->writeDate($date);
        }
    }

    private function writeDate(ContactDate $date): void
    {
        $label = $date->getLabel() ?? 'other';
        $group = $this->nextGroup();

        $this->addLine($group . 'X-ABDATE:' . $date->getDate());
        $this->addLine($group . 'X-ABLabel:' . VCardText::escape($label));
    }

    private function writeOther(Contact $contact): void
    {
        $notes = $contact->getNotes();
        if ($notes !== null) {
            $this->addLine('NOTE:' . VCardText::escape($notes));
        }

        $tags = $contact->getTags();
        if (count($tags) > 0) {
            $escapedTags = array_map(VCardText::escape(...), $tags);
            $this->addLine('CATEGORIES:' . implode(',', $escapedTags));
        }

        if ($contact->isFavorite()) {
            $this->addLine(self::FAVORITE_PROPERTY . ':1');
        }

        $this->writeProfile($contact);

        $photo = $contact->getPhoto();
        $dataUri = $photo === null ? null : DataUri::parse($photo);
        if ($dataUri !== null) {
            $photoType = $dataUri->getVCardType();
            $this->addLine('PHOTO;ENCODING=b;TYPE=' . $photoType . ':' . $dataUri->base64);
        }
    }

    private function writeProfile(Contact $contact): void
    {
        $gender = $contact->getGender();
        if ($gender !== null) {
            $this->addLine('X-GENDER:' . $gender);
        }

        $pronouns = $contact->getPronouns();
        if ($pronouns !== null) {
            $this->addLine('X-PRONOUNS:' . VCardText::escape($pronouns));
        }

        $language = $contact->getLanguage();
        if ($language !== null) {
            $this->addLine('X-LANG:' . VCardText::escape($language));
        }

        $timezone = $contact->getTimezone();
        if ($timezone !== null) {
            $this->addLine('TZ;VALUE=text:' . VCardText::escape($timezone));
        }

        $customFields = $contact->getCustomFields();
        foreach ($customFields as $field) {
            $this->addLine($field['key'] . ':' . VCardText::escape($field['value']));
        }
    }

    private function nextGroup(): string
    {
        $this->itemIndex++;

        return 'item' . $this->itemIndex . '.';
    }

    private function escapeParameter(string $value): string
    {
        $cleanValue = str_replace(['"', ';', ':', ','], ' ', $value);

        return mb_trim($cleanValue);
    }

    private function addLine(string $line): void
    {
        $this->lines[] = $line;
    }

    private function findStandardType(?string $label): ?string
    {
        if ($label === null) {
            return null;
        }

        $labelKey = mb_strtolower($label);

        return VCardLabelMap::LABEL_TO_TYPE[$labelKey] ?? null;
    }
}
