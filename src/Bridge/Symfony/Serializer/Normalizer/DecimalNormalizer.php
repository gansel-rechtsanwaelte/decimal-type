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
     * {@inheritdoc}
     *
     * @param Decimal $object
     *
     * @see \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
     */
    public function normalize($object, $format = null, array $context = []): string
    {
        return $object->toString();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Serializer\Normalizer\NormalizerInterface::supportsNormalization()
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Decimal;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     *
     * @see \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::denormalize()
     */
    public function denormalize($data, $class, $format = null, array $context = []): Decimal
    {
        try {
            return Decimal::create($data);
        } catch (InvalidArgument $e) {
            throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::supportsDenormalization()
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Decimal::class;
    }
}
