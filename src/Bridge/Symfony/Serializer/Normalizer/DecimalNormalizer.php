<?php

declare(strict_types=1);

namespace Gansel\Decimal\Bridge\Symfony\Serializer\Normalizer;

use Gansel\Decimal\Decimal;
use Gansel\Decimal\Exception\InvalidArgument;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DecimalNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct()
    {
    }

    /**
     * @param Decimal $object
     */
    public function normalize($object, $format = null, array $context = []): string
    {
        return $object->toString();
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Decimal;
    }

    /**
     * @throws UnexpectedValueException
     */
    public function denormalize($data, $class, $format = null, array $context = []): Decimal
    {
        try {
            return Decimal::create($data);
        } catch (InvalidArgument $e) {
            throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return Decimal::class === $type;
    }
}
