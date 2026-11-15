<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Tests\Unit\Service\VCard;

use Danilovl\ContactBundle\Service\VCard\VCardLabelMap;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VCardLabelMapTest extends TestCase
{
    /**
     * @param list<string> $types
     */
    #[DataProvider('provideLabelFromTypesCases')]
    public function testLabelFromTypes(array $types, ?string $expected): void
    {
        $result = VCardLabelMap::labelFromTypes($types);

        $this->assertSame($expected, $result);
    }

    #[DataProvider('provideNormalizeCustomLabelCases')]
    public function testNormalizeCustomLabel(string $label, string $expected): void
    {
        $result = VCardLabelMap::normalizeCustomLabel($label);

        $this->assertSame($expected, $result);
    }

    /**
     * @return Generator<string, array{list<string>, ?string}>
     */
    public static function provideLabelFromTypesCases(): Generator
    {
        yield 'cell' => [['CELL'], 'mobile'];
        yield 'lowercase type' => [['work'], 'work'];
        yield 'ignored then known' => [['INTERNET', 'HOME'], 'home'];
        yield 'known wins over custom' => [['Custom', 'FAX'], 'fax'];
        yield 'custom type' => [['Gym'], 'gym'];
        yield 'only ignored' => [['INTERNET', 'PREF'], null];
        yield 'extension type is ignored' => [['X-CUSTOM'], null];
        yield 'empty' => [[], null];
    }

    /**
     * @return Generator<string, array{string, string}>
     */
    public static function provideNormalizeCustomLabelCases(): Generator
    {
        yield 'apple label' => ['_$!<Spouse>!$_', 'spouse'];
        yield 'homepage' => ['HomePage', 'website'];
        yield 'known uppercase' => ['Mother', 'mother'];
        yield 'custom keeps case' => ['  Best Friend  ', 'Best Friend'];
    }
}
