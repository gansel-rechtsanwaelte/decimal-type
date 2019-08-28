<?php

declare(strict_types=1);

namespace Gansel\Decimal\Bridge\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DecimalType as DoctrineDecimalType;
use Gansel\Decimal\Decimal;
use Gansel\Decimal\Exception\InvalidArgument;

final class DecimalType extends DoctrineDecimalType
{
    /**
     * @var string
     */
    const NAME = 'gansel_decimal';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Decimal) {
            throw new ConversionException(sprintf('Expect an instance of %s. Got: %s', Decimal::class, \get_class($value)));
        }

        return $value->toString();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Decimal
    {
        if (null === $value) {
            return null;
        }

        try {
            return Decimal::create($value);
        } catch (InvalidArgument $e) {
            throw new ConversionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
