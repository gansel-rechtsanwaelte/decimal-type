<?php

declare(strict_types=1);

namespace Gansel\Decimal\Test\Bridge\Doctrine\DBAL\Types;

use Gansel\Decimal\Bridge\Doctrine\DBAL\Types\DecimalType;
use PHPUnit\Framework\TestCase;

final class DecimalTypeTest extends TestCase
{
    /**
     * @test
     */
    public function constants(): void
    {
        self::assertSame(
            'gansel_decimal',
            DecimalType::NAME
        );
    }
}
