<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Tests\Unit\Service;

use Danilovl\ContactBundle\Entity\{
    Contact,
    ContactValue
};
use Danilovl\ContactBundle\Model\ContactValueKind;
use Danilovl\ContactBundle\Service\ContactNameFormatter;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContactNameFormatterTest extends TestCase
{
    private ContactNameFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ContactNameFormatter;
    }

    /**
     * @param array<string, string> $fields
     */
    #[DataProvider('provideNamesCases')]
    public function testNames(array $fields, string $full, string $display, string $sort): void
    {
        $contact = $this->createContact($fields);

        $this->assertSame($full, $this->formatter->getFullName($contact));
        $this->assertSame($display, $this->formatter->getDisplayName($contact));
        $this->assertSame($sort, $this->formatter->getSortName($contact));
    }

    #[DataProvider('provideNormalizeValueCases')]
    public function testNormalizeValue(ContactValueKind $kind, string $value, string $expected): void
    {
        $result = $this->formatter->normalizeValue($kind, $value);

        $this->assertSame($expected, $result);
    }

    /**
     * @param array<string, string> $first
     * @param array<string, string> $second
     */
    #[DataProvider('provideFingerprintCases')]
    public function testFingerprint(array $first, array $second, bool $same): void
    {
        $firstContact = $this->createContact($first);
        $secondContact = $this->createContact($second);

        $firstFingerprint = $this->formatter->getFingerprint($firstContact);
        $secondFingerprint = $this->formatter->getFingerprint($secondContact);

        $this->assertSame($same, $firstFingerprint === $secondFingerprint);
    }

    /**
     * @param array<string, string> $fields
     */
    private function createContact(array $fields): Contact
    {
        $contact = new Contact('admin');

        foreach ($fields as $field => $value) {
            match ($field) {
                'prefix' => $contact->setPrefix($value),
                'firstName' => $contact->setFirstName($value),
                'lastName' => $contact->setLastName($value),
                'suffix' => $contact->setSuffix($value),
                'nickname' => $contact->setNickname($value),
                'company' => $contact->setCompany($value),
                'email' => $contact->addValue(new ContactValue(ContactValueKind::EMAIL, $value)),
                'phone' => $contact->addValue(new ContactValue(ContactValueKind::PHONE, $value)),
                default => throw new InvalidArgumentException($field)
            };
        }

        return $contact;
    }

    /**
     * @return Generator<string, array{array<string, string>, string, string, string}>
     */
    public static function provideNamesCases(): Generator
    {
        yield 'all parts' => [
            ['prefix' => 'Dr.', 'firstName' => 'John', 'lastName' => 'Smith', 'suffix' => 'Jr.'],
            'Dr. John Smith Jr.',
            'Dr. John Smith Jr.',
            'john smith'
        ];
        yield 'nickname fallback' => [['nickname' => 'Johnny', 'company' => 'Acme'], '', 'Johnny', 'johnny'];
        yield 'company fallback' => [['company' => 'Acme'], '', 'Acme', 'acme'];
        yield 'email fallback' => [['email' => 'john@example.com'], '', 'john@example.com', 'john@example.com'];
        yield 'empty contact' => [[], '', '', ''];
    }

    /**
     * @return Generator<string, array{ContactValueKind, string, string}>
     */
    public static function provideNormalizeValueCases(): Generator
    {
        yield 'phone' => [ContactValueKind::PHONE, '+420 (123) 456-789', '+420123456789'];
        yield 'email' => [ContactValueKind::EMAIL, 'John@Example.COM', 'john@example.com'];
    }

    /**
     * @return Generator<string, array{array<string, string>, array<string, string>, bool}>
     */
    public static function provideFingerprintCases(): Generator
    {
        yield 'formatting and case ignored' => [
            ['firstName' => 'John', 'email' => 'John@Example.com', 'phone' => '+420 123 456'],
            ['firstName' => 'JOHN', 'email' => 'john@example.com', 'phone' => '+420-123-456'],
            true
        ];
        yield 'other phone' => [
            ['firstName' => 'John', 'phone' => '111'],
            ['firstName' => 'John', 'phone' => '222'],
            false
        ];
        yield 'other name' => [
            ['firstName' => 'John', 'phone' => '111'],
            ['firstName' => 'Jane', 'phone' => '111'],
            false
        ];
    }
}
