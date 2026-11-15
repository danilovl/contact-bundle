<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Tests\Unit\Service;

use Danilovl\ContactBundle\DependencyInjection\ContactConfig;
use Danilovl\ContactBundle\Exception\ContactValidationException;
use Danilovl\ContactBundle\Service\ContactDataFactory;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ContactDataFactoryTest extends TestCase
{
    private ContactDataFactory $factory;

    protected function setUp(): void
    {
        $validator = Validation::createValidator();
        $config = new ContactConfig('/api', '/contact', 'Contacts', 1_024, 'default');
        $this->factory = new ContactDataFactory($validator, $config);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidPayloadProvider')]
    public function testLooseModeCollectsError(array $payload, string $key, string $message): void
    {
        $this->factory->create($payload, false);
        $errors = $this->factory->getErrors();

        $this->assertSame($message, $errors[$key] ?? null);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidPayloadProvider')]
    public function testStrictModeThrows(array $payload, string $key, string $message): void
    {
        try {
            $this->factory->create($payload);
            $this->fail('Exception expected');
        } catch (ContactValidationException $exception) {
            $errors = $exception->getErrors();

            $this->assertSame($message, $errors[$key] ?? null);
        }
    }

    #[DataProvider('provideValidValueCases')]
    public function testValidValue(string $key, string $value): void
    {
        $payload = ['firstName' => 'John', $key => [['value' => $value]]];

        $data = $this->factory->create($payload);

        $this->assertCount(1, $data->values);
        $this->assertSame($value, $data->values[0]->value);
    }

    public function testTooLongNameStrict(): void
    {
        $payload = ['firstName' => str_repeat('a', 129)];

        try {
            $this->factory->create($payload);
            $this->fail('Exception expected');
        } catch (ContactValidationException $exception) {
            $errors = $exception->getErrors();

            $this->assertSame('Maximum length is 128 characters.', $errors['firstName']);
        }
    }

    public function testTooLongNameLoose(): void
    {
        $payload = ['firstName' => str_repeat('a', 129)];

        $data = $this->factory->create($payload, false);

        $this->assertSame(128, mb_strlen((string) $data->firstName));
        $this->assertSame([], $this->factory->getErrors());
    }

    public function testStringsAreTrimmed(): void
    {
        $payload = ['firstName' => '  John  ', 'lastName' => '   '];

        $data = $this->factory->create($payload);

        $this->assertSame('John', $data->firstName);
        $this->assertNull($data->lastName);
    }

    public function testOnlyFirstPrimaryKept(): void
    {
        $payload = [
            'phones' => [
                ['value' => '111', 'primary' => true],
                ['value' => '222', 'primary' => true]
            ]
        ];

        $data = $this->factory->create($payload);

        $this->assertTrue($data->values[0]->primary);
        $this->assertFalse($data->values[1]->primary);
    }

    public function testEmptyRowsSkipped(): void
    {
        $payload = [
            'firstName' => 'John',
            'urls' => [['value' => ''], ['value' => 'https://example.com']],
            'dates' => [['date' => '']],
            'addresses' => [['label' => 'home', 'street' => '   ']],
            'workHistory' => [['company' => '', 'jobTitle' => '']],
            'relations' => 'not-an-array'
        ];

        $data = $this->factory->create($payload);

        $this->assertCount(1, $data->values);
        $this->assertSame([], $data->dates);
        $this->assertSame([], $data->addresses);
        $this->assertSame([], $data->workHistory);
    }

    public function testRowsLimit(): void
    {
        $rows = [];
        for ($index = 0; $index < 60; $index++) {
            $rows[] = ['value' => 'user' . $index];
        }

        $data = $this->factory->create(['messengers' => $rows]);

        $this->assertCount(50, $data->values);
    }

    public function testDatesNormalized(): void
    {
        $payload = [
            'firstName' => 'John',
            'birthday' => '19900517',
            'dates' => [['date' => '--12-24', 'label' => 'anniversary']]
        ];

        $data = $this->factory->create($payload);

        $this->assertSame('1990-05-17', $data->birthday);
        $this->assertSame('--12-24', $data->dates[0]->date);
    }

    public function testWorkHistoryMonthPrecision(): void
    {
        $payload = [
            'firstName' => 'John',
            'workHistory' => [
                ['company' => 'Acme', 'startDate' => '2020-03-15', 'endDate' => '2022-01']
            ]
        ];

        $data = $this->factory->create($payload);

        $this->assertSame('2020-03', $data->workHistory[0]->startDate);
        $this->assertSame('2022-01', $data->workHistory[0]->endDate);
    }

    public function testTagsCleaned(): void
    {
        $payload = [
            'firstName' => 'John',
            'tags' => [' friend ', 'Friend', '', 'work', 42, str_repeat('x', 70)]
        ];

        $data = $this->factory->create($payload);

        $this->assertCount(3, $data->tags);
        $this->assertSame('friend', $data->tags[0]);
        $this->assertSame('work', $data->tags[1]);
        $this->assertSame(64, mb_strlen($data->tags[2]));
    }

    public function testPhotoWhitespaceRemoved(): void
    {
        $payload = ['firstName' => 'John', 'photo' => "data:image/png;base64,aGVs\nbG8="];

        $data = $this->factory->create($payload);

        $this->assertSame('data:image/png;base64,aGVsbG8=', $data->photo);
    }

    #[DataProvider('provideFavoriteStrictCases')]
    public function testFavoriteStrict(mixed $favorite, bool $expected): void
    {
        $payload = ['firstName' => 'John', 'favorite' => $favorite];

        $data = $this->factory->create($payload);

        $this->assertSame($expected, $data->favorite);
    }

    #[DataProvider('provideProfileCases')]
    public function testProfile(string $key, string $value, ?string $expected): void
    {
        $payload = ['firstName' => 'John', $key => $value];

        $data = $this->factory->create($payload);

        $this->assertSame($expected, $data->{$key});
    }

    #[DataProvider('provideCustomFieldKeyCases')]
    public function testCustomFieldKey(string $key, ?string $expected): void
    {
        $payload = ['firstName' => 'John', 'customFields' => [['key' => $key, 'value' => '42']]];

        $data = $this->factory->create($payload, false);

        $this->assertSame($expected, $data->customFields[0]->key ?? null);
    }

    #[DataProvider('provideRevisionCases')]
    public function testRevision(string $value, ?string $expected): void
    {
        $payload = ['firstName' => 'John', 'revision' => $value];

        $data = $this->factory->create($payload);
        $result = $data->revisionAt?->format('Y-m-d');

        $this->assertSame($expected, $result);
    }

    public function testCustomFieldsEmptyRowSkipped(): void
    {
        $payload = ['firstName' => 'John', 'customFields' => [['key' => '', 'value' => '']]];

        $data = $this->factory->create($payload);

        $this->assertSame([], $data->customFields);
    }

    public function testErrorsReset(): void
    {
        $this->factory->create([], false);
        $this->factory->create(['firstName' => 'John']);

        $this->assertSame([], $this->factory->getErrors());
    }

    /**
     * @return Generator<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidPayloadProvider(): Generator
    {
        yield 'empty payload' => [[], 'firstName', 'Fill in a name, company, email or phone.'];
        yield 'email' => [
            ['firstName' => 'John', 'emails' => [['value' => 'ok@example.com'], ['value' => 'broken']]],
            'emails.1.value',
            'Invalid value.'
        ];
        yield 'phone' => [
            ['firstName' => 'John', 'phones' => [['value' => 'call me']]],
            'phones.0.value',
            'Invalid value.'
        ];
        yield 'birthday' => [
            ['firstName' => 'John', 'birthday' => '1990-02-31'],
            'birthday',
            'Invalid date.'
        ];
        yield 'important date' => [
            ['firstName' => 'John', 'dates' => [['date' => 'tomorrow']]],
            'dates.0.date',
            'Invalid date.'
        ];
        yield 'work month' => [
            ['firstName' => 'John', 'workHistory' => [['company' => 'Acme', 'startDate' => '2022-13']]],
            'workHistory.0.startDate',
            'Invalid date.'
        ];
        yield 'work end before start' => [
            ['firstName' => 'John', 'workHistory' => [['company' => 'Acme', 'startDate' => '2022-05', 'endDate' => '2021-01']]],
            'workHistory.0.endDate',
            'End date must not be before start date.'
        ];
        yield 'photo not image' => [
            ['firstName' => 'John', 'photo' => 'data:text/html;base64,PGgxPg=='],
            'photo',
            'Invalid photo.'
        ];
        yield 'gender' => [
            ['firstName' => 'John', 'gender' => 'X'],
            'gender',
            'Invalid value.'
        ];
        yield 'language' => [
            ['firstName' => 'John', 'language' => 'not a language'],
            'language',
            'Invalid value.'
        ];
        yield 'timezone' => [
            ['firstName' => 'John', 'timezone' => 'Mars/Phobos'],
            'timezone',
            'Invalid value.'
        ];
        yield 'custom key reserved' => [
            ['firstName' => 'John', 'customFields' => [['key' => 'X-GENDER', 'value' => 'a']]],
            'customFields.0.key',
            'Invalid key.'
        ];
        yield 'custom key invalid chars' => [
            ['firstName' => 'John', 'customFields' => [['key' => 'a_b!', 'value' => 'a']]],
            'customFields.0.key',
            'Invalid key.'
        ];
        yield 'custom value missing' => [
            ['firstName' => 'John', 'customFields' => [['key' => 'X-ID', 'value' => '']]],
            'customFields.0.value',
            'Invalid value.'
        ];
        yield 'custom key duplicate' => [
            ['firstName' => 'John', 'customFields' => [['key' => 'X-ID', 'value' => '1'], ['key' => 'x-id', 'value' => '2']]],
            'customFields.1.key',
            'Duplicate key.'
        ];
    }

    /**
     * @return Generator<string, array{string, string, string|null}>
     */
    public static function provideProfileCases(): Generator
    {
        yield 'gender uppercased' => ['gender', 'f', 'F'];
        yield 'language tag' => ['language', 'cs-CZ', 'cs-CZ'];
        yield 'timezone identifier' => ['timezone', 'Europe/Prague', 'Europe/Prague'];
        yield 'timezone offset' => ['timezone', '+02:00', '+02:00'];
        yield 'pronouns trimmed' => ['pronouns', '  she/her ', 'she/her'];
        yield 'uid kept' => ['uid', 'abc-123', 'abc-123'];
        yield 'uid with space dropped' => ['uid', 'abc 123', null];
    }

    /**
     * @return Generator<string, array{string, string|null}>
     */
    public static function provideRevisionCases(): Generator
    {
        yield 'iso date' => ['2024-05-17T10:00:00Z', '2024-05-17'];
        yield 'invalid dropped' => ['not a date', null];
    }

    /**
     * @return Generator<string, array{string, string|null}>
     */
    public static function provideCustomFieldKeyCases(): Generator
    {
        yield 'prefixed' => ['X-CUSTOMER-ID', 'X-CUSTOMER-ID'];
        yield 'prefix added and uppercased' => ['customer id', 'X-CUSTOMER-ID'];
        yield 'apple reserved' => ['X-APPLE-FOO', null];
        yield 'messenger reserved' => ['X-SKYPE', null];
    }

    /**
     * @return Generator<string, array{string, string}>
     */
    public static function provideValidValueCases(): Generator
    {
        yield 'email' => ['emails', 'john@example.com'];
        yield 'phone international' => ['phones', '+420 (123) 456-789'];
        yield 'phone local' => ['phones', '02 123-456'];
        yield 'url' => ['urls', 'https://example.com'];
    }

    /**
     * @return Generator<string, array{mixed, bool}>
     */
    public static function provideFavoriteStrictCases(): Generator
    {
        yield 'true' => [true, true];
        yield 'false' => [false, false];
        yield 'string' => ['yes', false];
        yield 'number' => [1, false];
    }
}
