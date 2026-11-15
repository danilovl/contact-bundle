<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use DateTimeImmutable;
use Danilovl\ContactBundle\Dto\{
    ContactAddressData,
    ContactCustomFieldData,
    ContactData,
    ContactDateData,
    ContactValueData,
    ContactWorkData
};
use Danilovl\ContactBundle\Exception\ContactValidationException;
use Danilovl\ContactBundle\Model\{
    ContactValueKind,
    DataUri,
    PartialDate
};
use Danilovl\ContactBundle\Service\VCard\VCardLabelMap;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Exception;

class ContactDataFactory
{
    private const int NAME_LENGTH = 128;

    private const int SHORT_LENGTH = 32;

    private const int LONG_LENGTH = 255;

    private const int LABEL_LENGTH = 64;

    private const int NOTES_LENGTH = 65_000;

    private const int PHOTO_LENGTH = 2_000_000;

    private const int TAG_LENGTH = 64;

    private const int MAX_ITEMS = 50;

    private const int LANGUAGE_LENGTH = 35;

    private const int CUSTOM_KEY_LENGTH = 64;

    private const int CUSTOM_VALUE_LENGTH = 1_000;

    private const string MONTH_PATTERN = '~^\d{4}-(0[1-9]|1[0-2])$~';

    private const string LANGUAGE_PATTERN = '~^[a-z]{2,3}(-[a-z0-9]{2,8})*$~i';

    private const string OFFSET_PATTERN = '~^[+-](0\d|1\d|2[0-3]):?[0-5]\d$~';

    private const string CUSTOM_KEY_PATTERN = '~^X-[A-Z0-9-]+$~';

    private const string UID_PATTERN = '~^[\x21-\x7e]+$~';

    private const array GENDERS = ['M', 'F', 'O', 'N', 'U'];

    private const array RESERVED_CUSTOM_KEYS = [
        'X-DANILOVL-FAVORITE',
        'X-GENDER',
        'X-PRONOUNS',
        'X-LANG',
        'X-TIMEZONE'
    ];

    /**
     * @var array<string, string>
     */
    private array $errors = [];

    private bool $strict = true;

    public function __construct(private readonly ValidatorInterface $validator) {}

    /**
     * @param array<mixed> $payload
     */
    public function create(array $payload, bool $strict = true): ContactData
    {
        $this->errors = [];
        $this->strict = $strict;

        $values = $this->createValues($payload);
        $addresses = $this->createAddresses($payload);
        $dates = $this->createDates($payload);
        $workHistory = $this->createWorkHistory($payload);
        $tags = $this->createTags($payload);
        $birthday = $this->createPartialDate($payload, 'birthday');
        $photo = $this->createPhoto($payload);
        $favorite = ($payload['favorite'] ?? false) === true;
        $prefix = $this->createString($payload, 'prefix', self::SHORT_LENGTH);
        $gender = $this->createGender($payload);
        $pronouns = $this->createString($payload, 'pronouns', self::LABEL_LENGTH);
        $language = $this->createLanguage($payload);
        $timezone = $this->createTimezone($payload);
        $customFields = $this->createCustomFields($payload);
        $uid = $this->createUid($payload);
        $revisionAt = $this->createRevision($payload);
        $firstName = $this->createString($payload, 'firstName', self::NAME_LENGTH);
        $middleName = $this->createString($payload, 'middleName', self::NAME_LENGTH);
        $lastName = $this->createString($payload, 'lastName', self::NAME_LENGTH);
        $suffix = $this->createString($payload, 'suffix', self::SHORT_LENGTH);
        $nickname = $this->createString($payload, 'nickname', self::NAME_LENGTH);
        $company = $this->createString($payload, 'company', self::LONG_LENGTH);
        $department = $this->createString($payload, 'department', self::LONG_LENGTH);
        $jobTitle = $this->createString($payload, 'jobTitle', self::LONG_LENGTH);
        $notes = $this->createString($payload, 'notes', self::NOTES_LENGTH);

        $data = new ContactData(
            prefix: $prefix,
            firstName: $firstName,
            middleName: $middleName,
            lastName: $lastName,
            suffix: $suffix,
            nickname: $nickname,
            company: $company,
            department: $department,
            jobTitle: $jobTitle,
            birthday: $birthday,
            photo: $photo,
            notes: $notes,
            favorite: $favorite,
            tags: $tags,
            values: $values,
            addresses: $addresses,
            dates: $dates,
            workHistory: $workHistory,
            gender: $gender,
            pronouns: $pronouns,
            language: $language,
            timezone: $timezone,
            customFields: $customFields,
            uid: $uid,
            revisionAt: $revisionAt
        );

        $isEmpty = $data->isEmpty();
        if ($isEmpty) {
            $this->addError('firstName', 'Fill in a name, company, email or phone.');
        }

        $hasErrors = count($this->errors) > 0;
        if ($hasErrors && $this->strict) {
            throw new ContactValidationException($this->errors);
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array<mixed> $payload
     * @return list<ContactValueData>
     */
    private function createValues(array $payload): array
    {
        $result = [];
        $kinds = ContactValueKind::cases();

        foreach ($kinds as $kind) {
            $key = $kind->collectionKey();
            $rows = $this->getRows($payload, $key);
            $hasPrimary = false;

            foreach ($rows as $index => $row) {
                $path = $key . '.' . $index . '.value';
                $value = $this->createString($row, 'value', self::LONG_LENGTH, $path);
                if ($value === null) {
                    continue;
                }

                $isValid = $this->isValidValue($kind, $value);
                if (!$isValid) {
                    $this->addError($path, 'Invalid value.');

                    continue;
                }

                $label = $this->createString($row, 'label', self::LABEL_LENGTH);
                $isPrimary = ($row['primary'] ?? false) === true && !$hasPrimary;
                $hasPrimary = $hasPrimary || $isPrimary;

                $relatedContactId = $this->createRelatedContactId($kind, $row);

                $result[] = new ContactValueData($kind, $value, $label, $isPrimary, $relatedContactId);
            }
        }

        return $result;
    }

    /**
     * @param array<mixed> $row
     */
    private function createRelatedContactId(ContactValueKind $kind, array $row): ?int
    {
        if ($kind !== ContactValueKind::RELATION) {
            return null;
        }

        $relatedContactId = $row['relatedContactId'] ?? null;
        if (!is_int($relatedContactId) || $relatedContactId < 1) {
            return null;
        }

        return $relatedContactId;
    }

    /**
     * @param array<mixed> $payload
     */
    private function createGender(array $payload): ?string
    {
        $gender = $this->createString($payload, 'gender', 1);
        if ($gender === null) {
            return null;
        }

        $gender = mb_strtoupper($gender);
        if (!in_array($gender, self::GENDERS, true)) {
            $this->addError('gender', 'Invalid value.');

            return null;
        }

        return $gender;
    }

    /**
     * @param array<mixed> $payload
     */
    private function createLanguage(array $payload): ?string
    {
        $language = $this->createString($payload, 'language', self::LANGUAGE_LENGTH);
        if ($language === null) {
            return null;
        }

        $isValid = preg_match(self::LANGUAGE_PATTERN, $language) === 1;
        if (!$isValid) {
            $this->addError('language', 'Invalid value.');

            return null;
        }

        return $language;
    }

    /**
     * @param array<mixed> $payload
     */
    private function createTimezone(array $payload): ?string
    {
        $timezone = $this->createString($payload, 'timezone', self::LABEL_LENGTH);
        if ($timezone === null) {
            return null;
        }

        $identifiers = timezone_identifiers_list();
        $isIdentifier = in_array($timezone, $identifiers, true);
        $isOffset = preg_match(self::OFFSET_PATTERN, $timezone) === 1;

        if (!$isIdentifier && !$isOffset) {
            $this->addError('timezone', 'Invalid value.');

            return null;
        }

        return $timezone;
    }

    /**
     * @param array<mixed> $payload
     * @return list<ContactCustomFieldData>
     */
    private function createCustomFields(array $payload): array
    {
        $result = [];
        $usedKeys = [];
        $rows = $this->getRows($payload, 'customFields');

        foreach ($rows as $index => $row) {
            $keyPath = 'customFields.' . $index . '.key';
            $valuePath = 'customFields.' . $index . '.value';
            $rawKey = $this->createString($row, 'key', self::CUSTOM_KEY_LENGTH, $keyPath);
            $value = $this->createString($row, 'value', self::CUSTOM_VALUE_LENGTH, $valuePath);

            if ($rawKey === null && $value === null) {
                continue;
            }

            $key = $this->createCustomKey($rawKey);
            if ($key === null) {
                $this->addError($keyPath, 'Invalid key.');

                continue;
            }

            if ($value === null) {
                $this->addError($valuePath, 'Invalid value.');

                continue;
            }

            if (isset($usedKeys[$key])) {
                $this->addError($keyPath, 'Duplicate key.');

                continue;
            }

            $usedKeys[$key] = true;
            $result[] = new ContactCustomFieldData($key, $value);
        }

        return $result;
    }

    private function createCustomKey(?string $rawKey): ?string
    {
        if ($rawKey === null) {
            return null;
        }

        $key = mb_strtoupper(str_replace(' ', '-', $rawKey));
        if (!str_starts_with($key, 'X-')) {
            $key = 'X-' . $key;
        }

        $isValid = preg_match(self::CUSTOM_KEY_PATTERN, $key) === 1;
        $isReserved = in_array($key, self::RESERVED_CUSTOM_KEYS, true)
            || str_starts_with($key, 'X-AB')
            || str_starts_with($key, 'X-APPLE')
            || isset(VCardLabelMap::MESSENGER_PROPERTIES[$key]);

        if (!$isValid || $isReserved || mb_strlen($key) > self::CUSTOM_KEY_LENGTH) {
            return null;
        }

        return $key;
    }

    /**
     * @param array<mixed> $payload
     */
    private function createUid(array $payload): ?string
    {
        $uid = $this->createString($payload, 'uid', self::LONG_LENGTH);
        if ($uid === null) {
            return null;
        }

        $isValid = preg_match(self::UID_PATTERN, $uid) === 1;

        return $isValid ? $uid : null;
    }

    /**
     * @param array<mixed> $payload
     */
    private function createRevision(array $payload): ?DateTimeImmutable
    {
        $revision = $this->createString($payload, 'revision', self::SHORT_LENGTH);
        if ($revision === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($revision);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param array<mixed> $payload
     * @return list<ContactAddressData>
     */
    private function createAddresses(array $payload): array
    {
        $result = [];
        $rows = $this->getRows($payload, 'addresses');

        foreach ($rows as $row) {
            $label = $this->createString($row, 'label', self::LABEL_LENGTH);
            $street = $this->createString($row, 'street', self::LONG_LENGTH);
            $extended = $this->createString($row, 'extended', self::LONG_LENGTH);
            $city = $this->createString($row, 'city', self::NAME_LENGTH);
            $region = $this->createString($row, 'region', self::NAME_LENGTH);
            $postalCode = $this->createString($row, 'postalCode', self::SHORT_LENGTH);
            $country = $this->createString($row, 'country', self::NAME_LENGTH);

            $parts = [$street, $extended, $city, $region, $postalCode, $country];
            $filledParts = array_filter($parts, static function (?string $part): bool {
                return $part !== null;
            });

            $isFilled = count($filledParts) > 0;
            if (!$isFilled) {
                continue;
            }

            $result[] = new ContactAddressData(
                label: $label,
                street: $street,
                extended: $extended,
                city: $city,
                region: $region,
                postalCode: $postalCode,
                country: $country
            );
        }

        return $result;
    }

    /**
     * @param array<mixed> $payload
     * @return list<ContactDateData>
     */
    private function createDates(array $payload): array
    {
        $result = [];
        $rows = $this->getRows($payload, 'dates');

        foreach ($rows as $index => $row) {
            $path = 'dates.' . $index . '.date';
            $date = $this->createPartialDate($row, 'date', $path);
            if ($date === null) {
                continue;
            }

            $label = $this->createString($row, 'label', self::LABEL_LENGTH);
            $result[] = new ContactDateData($date, $label);
        }

        return $result;
    }

    /**
     * @param array<mixed> $payload
     * @return list<ContactWorkData>
     */
    private function createWorkHistory(array $payload): array
    {
        $result = [];
        $rows = $this->getRows($payload, 'workHistory');

        foreach ($rows as $index => $row) {
            $company = $this->createString($row, 'company', self::LONG_LENGTH, 'workHistory.' . $index . '.company');
            $jobTitle = $this->createString($row, 'jobTitle', self::LONG_LENGTH, 'workHistory.' . $index . '.jobTitle');
            $startDate = $this->createMonth($row, 'startDate', 'workHistory.' . $index . '.startDate');
            $endDate = $this->createMonth($row, 'endDate', 'workHistory.' . $index . '.endDate');

            if ($company === null && $jobTitle === null) {
                continue;
            }

            $isRangeInvalid = $startDate !== null && $endDate !== null && $endDate < $startDate;
            if ($isRangeInvalid) {
                $this->addError('workHistory.' . $index . '.endDate', 'End date must not be before start date.');
            }

            $result[] = new ContactWorkData($company, $jobTitle, $startDate, $endDate);
        }

        return $result;
    }

    /**
     * @param array<mixed> $payload
     */
    private function createMonth(array $payload, string $key, string $path): ?string
    {
        $value = $this->createString($payload, $key, self::SHORT_LENGTH, $path);
        if ($value === null) {
            return null;
        }

        $month = mb_substr($value, 0, 7);
        $isValid = preg_match(self::MONTH_PATTERN, $month) === 1;
        if (!$isValid) {
            $this->addError($path, 'Invalid date.');

            return null;
        }

        return $month;
    }

    /**
     * @param array<mixed> $payload
     * @return list<string>
     */
    private function createTags(array $payload): array
    {
        $rawTags = $payload['tags'] ?? [];
        if (!is_array($rawTags)) {
            return [];
        }

        $result = [];
        foreach ($rawTags as $rawTag) {
            if (!is_string($rawTag)) {
                continue;
            }

            $tag = mb_trim($rawTag);
            $tag = mb_substr($tag, 0, self::TAG_LENGTH);
            $tagKey = mb_strtolower($tag);

            if ($tag === '' || isset($result[$tagKey])) {
                continue;
            }

            $result[$tagKey] = $tag;
        }

        return array_values($result);
    }

    /**
     * @param array<mixed> $payload
     */
    private function createPartialDate(array $payload, string $key, ?string $path = null): ?string
    {
        $value = $this->createString($payload, $key, self::SHORT_LENGTH, $path);
        if ($value === null) {
            return null;
        }

        $date = PartialDate::normalize($value);
        if ($date === null) {
            $errorPath = $path ?? $key;
            $this->addError($errorPath, 'Invalid date.');
        }

        return $date;
    }

    /**
     * @param array<mixed> $payload
     */
    private function createPhoto(array $payload): ?string
    {
        $photo = $payload['photo'] ?? null;
        if (!is_string($photo) || $photo === '') {
            return null;
        }

        $length = mb_strlen($photo);
        $dataUri = DataUri::parse($photo);

        if ($dataUri === null || $length > self::PHOTO_LENGTH) {
            $this->addError('photo', 'Invalid photo.');

            return null;
        }

        return DataUri::create($dataUri->mimeType, $dataUri->base64);
    }

    /**
     * @param array<mixed> $payload
     */
    private function createString(array $payload, string $key, int $maxLength, ?string $path = null): ?string
    {
        $value = $payload[$key] ?? null;
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = mb_trim($value);
        if ($value === '') {
            return null;
        }

        $length = mb_strlen($value);
        if ($length <= $maxLength) {
            return $value;
        }

        if ($this->strict) {
            $errorPath = $path ?? $key;
            $message = sprintf('Maximum length is %d characters.', $maxLength);
            $this->addError($errorPath, $message);
        }

        return mb_substr($value, 0, $maxLength);
    }

    /**
     * @param array<mixed> $payload
     * @return array<int, array<mixed>>
     */
    private function getRows(array $payload, string $key): array
    {
        $rows = $payload[$key] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $rows = array_values($rows);
        $rows = array_slice($rows, 0, self::MAX_ITEMS);

        $result = [];
        foreach ($rows as $index => $row) {
            if (is_array($row)) {
                $result[$index] = $row;
            }
        }

        return $result;
    }

    private function isValidValue(ContactValueKind $kind, string $value): bool
    {
        if ($kind === ContactValueKind::EMAIL) {
            $constraint = new Email;
            $violations = $this->validator->validate($value, $constraint);

            return count($violations) === 0;
        }

        if ($kind === ContactValueKind::PHONE) {
            return preg_match('/^[0-9+()\-.\s\/*#pwx,;]+$/i', $value) === 1;
        }

        return true;
    }

    private function addError(string $path, string $message): void
    {
        $this->errors[$path] ??= $message;
    }
}
