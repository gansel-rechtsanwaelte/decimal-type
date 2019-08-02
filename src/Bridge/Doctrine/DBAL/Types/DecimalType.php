<?php

declare(strict_types=1);

namespace Gansel\Decimal\Bridge\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DecimalType as DoctrineDecimalType;
use Gansel\Decimal\Decimal;
use Gansel\Decimal\Exception\InvalidArgument;

class DecimalType extends DoctrineDecimalType
{
    /**
     * @var string
     */
    const NAME = 'gansel_decimal';

    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\DBAL\Types\DecimalType::getName()
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\DBAL\Types\Type::convertToDatabaseValue()
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Decimal) {
            throw new ConversionException(sprintf('Expected an instance of "%s"', Decimal::class));
        }

        return $value->toString();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\DBAL\Types\DecimalType::convertToPHPValue()
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Decimal
    {
        if ($value === null) {
            return null;
        }

        try {
            return Decimal::create($value);
        } catch (InvalidArgument $e) {
            throw new ConversionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\DBAL\Types\Type::requiresSQLCommentHint()
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
