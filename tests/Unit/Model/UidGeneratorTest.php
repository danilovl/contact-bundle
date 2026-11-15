<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Tests\Unit\Model;

use Danilovl\ContactBundle\Model\UidGenerator;
use PHPUnit\Framework\TestCase;

final class UidGeneratorTest extends TestCase
{
    public function testFormat(): void
    {
        $uid = UidGenerator::generate();

        $this->assertMatchesRegularExpression('~^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$~', $uid);
    }

    public function testUnique(): void
    {
        $uids = [];
        for ($index = 0; $index < 50; $index++) {
            $uids[] = UidGenerator::generate();
        }

        $this->assertCount(50, array_unique($uids));
    }
}
