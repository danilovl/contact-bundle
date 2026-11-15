<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service\VCard;

use Danilovl\ContactBundle\Model\{
    ContactValueKind,
    DataUri
};

class VCardParser
{
    private const string FAVORITE_PROPERTY = 'X-DANILOVL-FAVORITE';

    private const array LABEL_PROPERTIES = ['X-ABLABEL'];

    private const array GENDERS = ['M', 'F', 'O', 'N', 'U'];

    private const array IGNORED_CUSTOM_PREFIXES = ['X-AB', 'X-APPLE', 'X-SERVICE'];

    /**
     * @return list<array<string, mixed>>
     */
    public function parse(string $content): array
    {
        $lines = $this->unfold($content);
        $cards = [];
        $properties = null;

        foreach ($lines as $line) {
            $property = $this->parseLine($line);
            if ($property === null) {
                continue;
            }

            $name = $property->name;
            $upperValue = mb_strtoupper($property->value);

            if ($name === 'BEGIN' && $upperValue === 'VCARD') {
                $properties = [];

                continue;
            }

            if ($name === 'END' && $upperValue === 'VCARD') {
                if ($properties !== null) {
                    $cards[] = $this->createPayload($properties);
                }

                $properties = null;

                continue;
            }

            if ($properties !== null) {
                $properties[] = $property;
            }
        }

        return $cards;
    }

    /**
     * @return list<string>
     */
    private function unfold(string $content): array
    {
        $content = $this->convertToUtf8($content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $rawLines = explode("\n", $content);

        $lines = [];
        $isQuotedPrintableContinuation = false;

        foreach ($rawLines as $rawLine) {
            $lastIndex = count($lines) - 1;
            $isFolded = $rawLine !== '' && ($rawLine[0] === ' ' || $rawLine[0] === "\t");

            if ($lastIndex >= 0 && $isQuotedPrintableContinuation) {
                $lines[$lastIndex] = mb_substr($lines[$lastIndex], 0, -1) . $rawLine;
            } elseif ($lastIndex >= 0 && $isFolded) {
                $lines[$lastIndex] .= mb_substr($rawLine, 1);
            } else {
                $lines[] = $rawLine;
            }

            $currentIndex = count($lines) - 1;
            $currentLine = $lines[$currentIndex];
            $isQuotedPrintable = mb_stripos($currentLine, 'QUOTED-PRINTABLE') !== false;
            $isQuotedPrintableContinuation = $isQuotedPrintable && str_ends_with($currentLine, '=');
        }

        return array_values($lines);
    }

    private function convertToUtf8(string $content): string
    {
        $content = preg_replace('~^\xEF\xBB\xBF~', '', $content) ?? $content;

        $isUtf8 = mb_check_encoding($content, 'UTF-8');
        if ($isUtf8) {
            return $content;
        }

        return mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }

    private function parseLine(string $line): ?VCardProperty
    {
        $line = mb_trim($line);
        if ($line === '') {
            return null;
        }

        $separatorPosition = $this->findValueSeparator($line);
        if ($separatorPosition === null) {
            return null;
        }

        $head = mb_substr($line, 0, $separatorPosition);
        $value = mb_substr($line, $separatorPosition + 1);
        $headParts = VCardText::splitUnescaped($head, ';');
        $nameWithGroup = array_shift($headParts) ?? '';

        $group = null;
        $name = $nameWithGroup;
        $dotPosition = mb_strpos($nameWithGroup, '.');
        if ($dotPosition !== false) {
            $rawGroup = mb_substr($nameWithGroup, 0, $dotPosition);
            $group = mb_strtolower($rawGroup);
            $name = mb_substr($nameWithGroup, $dotPosition + 1);
        }

        $parameters = $this->parseParameters($headParts);
        $value = $this->decodeValue($value, $parameters);
        $upperName = mb_strtoupper($name);

        return new VCardProperty($group, $upperName, $parameters, $value);
    }

    private function findValueSeparator(string $line): ?int
    {
        $isQuoted = false;
        $length = mb_strlen($line);

        for ($index = 0; $index < $length; $index++) {
            $char = mb_substr($line, $index, 1);
            if ($char === '"') {
                $isQuoted = !$isQuoted;

                continue;
            }

            if ($char === ':' && !$isQuoted) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param list<string> $parts
     * @return array<string, list<string>>
     */
    private function parseParameters(array $parts): array
    {
        $parameters = [];

        foreach ($parts as $part) {
            $equalPosition = mb_strpos($part, '=');
            if ($equalPosition === false) {
                $parameters['TYPE'][] = mb_strtoupper($part);

                continue;
            }

            $rawKey = mb_substr($part, 0, $equalPosition);
            $key = mb_strtoupper($rawKey);
            $rawValue = mb_substr($part, $equalPosition + 1);
            $rawValue = mb_trim($rawValue, '"');
            $values = explode(',', $rawValue);

            foreach ($values as $value) {
                $isType = $key === 'TYPE';
                $parameters[$key][] = $isType ? mb_strtoupper($value) : $value;
            }
        }

        return $parameters;
    }

    /**
     * @param array<string, list<string>> $parameters
     */
    private function decodeValue(string $value, array $parameters): string
    {
        $encodings = $parameters['ENCODING'] ?? [];
        $encoding = mb_strtoupper($encodings[0] ?? '');

        if ($encoding !== 'QUOTED-PRINTABLE') {
            return $value;
        }

        $decoded = quoted_printable_decode($value);
        $charsets = $parameters['CHARSET'] ?? [];
        $charset = mb_strtoupper($charsets[0] ?? 'UTF-8');

        if ($charset === 'UTF-8') {
            return $decoded;
        }

        $converted = mb_convert_encoding($decoded, 'UTF-8', $charset);

        return $converted === false ? $decoded : $converted;
    }

    /**
     * @param list<VCardProperty> $properties
     * @return array<string, mixed>
     */
    private function createPayload(array $properties): array
    {
        $groupLabels = $this->collectGroupLabels($properties);
        $payload = $this->createEmptyPayload();

        foreach ($properties as $property) {
            $label = $property->group === null ? null : $groupLabels[$property->group] ?? null;
            $payload = $this->applyProperty($payload, $property, $label);
        }

        $hasName = $payload['firstName'] !== null || $payload['lastName'] !== null;
        $isCompanyName = $payload['formattedName'] === $payload['company'];
        if (!$hasName && !$isCompanyName && $payload['formattedName'] !== null) {
            $payload['firstName'] = $payload['formattedName'];
        }

        unset($payload['formattedName']);

        return $payload;
    }

    /**
     * @param list<VCardProperty> $properties
     * @return array<string, string>
     */
    private function collectGroupLabels(array $properties): array
    {
        $labels = [];

        foreach ($properties as $property) {
            $isLabel = in_array($property->name, self::LABEL_PROPERTIES, true);
            if (!$isLabel || $property->group === null) {
                continue;
            }

            $rawLabel = VCardText::unescape($property->value);
            $labels[$property->group] = VCardLabelMap::normalizeCustomLabel($rawLabel);
        }

        return $labels;
    }

    /**
     * @return array<string, mixed>
     */
    private function createEmptyPayload(): array
    {
        return [
            'formattedName' => null,
            'prefix' => null,
            'firstName' => null,
            'middleName' => null,
            'lastName' => null,
            'suffix' => null,
            'nickname' => null,
            'company' => null,
            'department' => null,
            'jobTitle' => null,
            'birthday' => null,
            'photo' => null,
            'notes' => null,
            'favorite' => false,
            'uid' => null,
            'revision' => null,
            'gender' => null,
            'pronouns' => null,
            'language' => null,
            'timezone' => null,
            'customFields' => [],
            'tags' => [],
            'emails' => [],
            'phones' => [],
            'urls' => [],
            'messengers' => [],
            'relations' => [],
            'addresses' => [],
            'dates' => []
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function applyProperty(array $payload, VCardProperty $property, ?string $groupLabel): array
    {
        $text = VCardText::unescape($property->value);
        $types = $property->getTypes();
        $label = $groupLabel ?? VCardLabelMap::labelFromTypes($types);
        $isPrimary = in_array('PREF', $types, true) || $property->hasParameter('PREF');

        switch ($property->name) {
            case 'FN':
                $payload['formattedName'] = $text;

                break;
            case 'N':
                $payload = $this->applyName($payload, $property->value);

                break;
            case 'NICKNAME':
                $payload['nickname'] = $text;

                break;
            case 'ORG':
                $organizationParts = VCardText::splitUnescaped($property->value, ';');
                $payload['company'] = VCardText::unescape($organizationParts[0]);
                $payload['department'] = isset($organizationParts[1]) ? VCardText::unescape($organizationParts[1]) : null;

                break;
            case 'TITLE':
                $payload['jobTitle'] = $text;

                break;
            case 'BDAY':
                $payload['birthday'] = $this->normalizeDate($text, $property);

                break;
            case 'NOTE':
                $payload['notes'] = $text;

                break;
            case 'CATEGORIES':
                $tags = VCardText::splitUnescaped($property->value, ',');
                $payload['tags'] = array_map(VCardText::unescape(...), $tags);

                break;
            case self::FAVORITE_PROPERTY:
                $payload['favorite'] = $text === '1';

                break;
            case 'UID':
                $uid = preg_replace('~^urn:uuid:~i', '', $text) ?? $text;
                $payload['uid'] = mb_trim($uid);

                break;
            case 'REV':
                $payload['revision'] = $text;

                break;
            case 'GENDER':
            case 'X-GENDER':
                $payload['gender'] = $this->createGender($property->value);

                break;
            case 'PRONOUNS':
            case 'X-PRONOUNS':
                $payload['pronouns'] = $text;

                break;
            case 'LANG':
            case 'X-LANG':
                $payload['language'] ??= $text;

                break;
            case 'TZ':
                $payload['timezone'] = $text;

                break;
            case 'PHOTO':
                $payload['photo'] = $this->createPhoto($property);

                break;
            case 'EMAIL':
                $payload = $this->appendValue($payload, ContactValueKind::EMAIL, $text, $label, $isPrimary);

                break;
            case 'TEL':
                $phone = preg_replace('~^tel:~i', '', $text) ?? $text;
                $payload = $this->appendValue($payload, ContactValueKind::PHONE, $phone, $label, $isPrimary);

                break;
            case 'URL':
                $payload = $this->appendValue($payload, ContactValueKind::URL, $text, $label, false);

                break;
            case 'IMPP':
                $payload = $this->applyMessenger($payload, $property, $text, $groupLabel);

                break;
            case 'X-ABRELATEDNAMES':
            case 'RELATED':
                $payload = $this->appendValue($payload, ContactValueKind::RELATION, $text, $label, false);

                break;
            case 'ADR':
                $payload = $this->applyAddress($payload, $property->value, $label);

                break;
            case 'X-ABDATE':
            case 'ANNIVERSARY':
                $dateLabel = $property->name === 'ANNIVERSARY' ? 'anniversary' : $label;
                $payload = $this->appendDate($payload, $text, $dateLabel, $property);

                break;
            default:
                $messengerLabel = VCardLabelMap::MESSENGER_PROPERTIES[$property->name] ?? null;
                if ($messengerLabel !== null) {
                    $payload = $this->appendValue($payload, ContactValueKind::MESSENGER, $text, $messengerLabel, false);

                    break;
                }

                $payload = $this->appendCustomField($payload, $property, $text);
        }

        return $payload;
    }

    private function createGender(string $value): ?string
    {
        $parts = VCardText::splitUnescaped($value, ';');
        $gender = mb_strtoupper(mb_trim($parts[0]));

        return in_array($gender, self::GENDERS, true) ? $gender : null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function appendCustomField(array $payload, VCardProperty $property, string $text): array
    {
        $name = $property->name;
        if (!str_starts_with($name, 'X-')) {
            return $payload;
        }

        foreach (self::IGNORED_CUSTOM_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return $payload;
            }
        }

        /** @var list<array<string, string>> $fields */
        $fields = $payload['customFields'];
        $fields[] = [
            'key' => $name,
            'value' => $text
        ];
        $payload['customFields'] = $fields;

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function applyName(array $payload, string $value): array
    {
        $parts = VCardText::splitUnescaped($value, ';');
        $keys = ['lastName', 'firstName', 'middleName', 'prefix', 'suffix'];

        foreach ($keys as $index => $key) {
            $part = $parts[$index] ?? '';
            $part = VCardText::unescape($part);
            $part = str_replace(',', ' ', $part);
            $part = mb_trim($part);

            $payload[$key] = $part === '' ? null : $part;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function applyMessenger(array $payload, VCardProperty $property, string $text, ?string $groupLabel): array
    {
        $serviceTypes = $property->parameters['X-SERVICE-TYPE'] ?? [];
        $serviceType = $serviceTypes[0] ?? null;
        $handle = $text;

        $matches = [];
        $hasScheme = preg_match('~^([a-z][a-z0-9+.-]*):(.+)$~i', $text, $matches) === 1;
        if ($hasScheme) {
            $serviceType ??= $matches[1];
            $handle = $matches[2];
        }

        $label = $groupLabel ?? $serviceType;
        $label = $label === null ? null : mb_strtolower($label);

        return $this->appendValue($payload, ContactValueKind::MESSENGER, $handle, $label, false);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function applyAddress(array $payload, string $value, ?string $label): array
    {
        $parts = VCardText::splitUnescaped($value, ';');
        $keys = ['postOfficeBox', 'extended', 'street', 'city', 'region', 'postalCode', 'country'];

        $address = ['label' => $label];
        foreach ($keys as $index => $key) {
            $part = $parts[$index] ?? '';
            $address[$key] = VCardText::unescape($part);
        }

        $postOfficeBox = $address['postOfficeBox'];
        if ($postOfficeBox !== '' && $address['extended'] === '') {
            $address['extended'] = $postOfficeBox;
        }

        unset($address['postOfficeBox']);

        /** @var list<array<string, mixed>> $addresses */
        $addresses = $payload['addresses'];
        $addresses[] = $address;
        $payload['addresses'] = $addresses;

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function appendValue(array $payload, ContactValueKind $kind, string $value, ?string $label, bool $primary): array
    {
        $key = $kind->collectionKey();

        /** @var list<array<string, mixed>> $rows */
        $rows = $payload[$key];
        $rows[] = [
            'label' => $label,
            'value' => $value,
            'primary' => $primary
        ];
        $payload[$key] = $rows;

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function appendDate(array $payload, string $text, ?string $label, VCardProperty $property): array
    {
        $date = $this->normalizeDate($text, $property);

        /** @var list<array<string, mixed>> $dates */
        $dates = $payload['dates'];
        $dates[] = [
            'label' => $label,
            'date' => $date
        ];
        $payload['dates'] = $dates;

        return $payload;
    }

    private function normalizeDate(string $text, VCardProperty $property): string
    {
        $omitYears = $property->parameters['X-APPLE-OMIT-YEAR'] ?? [];
        $omitYear = $omitYears[0] ?? null;
        $isOmittedYear = $omitYear !== null && str_starts_with($text, $omitYear);

        if ($isOmittedYear) {
            $monthAndDay = mb_substr($text, 5, 5);

            return '--' . $monthAndDay;
        }

        return $text;
    }

    private function createPhoto(VCardProperty $property): ?string
    {
        $value = mb_trim($property->value);
        if (str_starts_with($value, 'data:')) {
            return $value;
        }

        $encodings = $property->parameters['ENCODING'] ?? [];
        $encoding = mb_strtoupper($encodings[0] ?? '');
        $isBase64 = $encoding === 'B' || $encoding === 'BASE64';

        if (!$isBase64) {
            return null;
        }

        $types = $property->getTypes();
        $type = mb_strtolower($types[0] ?? 'jpeg');
        $type = $type === 'jpg' ? 'jpeg' : $type;
        $mimeType = 'image/' . $type;

        return DataUri::create($mimeType, $value);
    }
}
