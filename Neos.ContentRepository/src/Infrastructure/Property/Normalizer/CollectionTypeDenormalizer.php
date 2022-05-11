<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Infrastructure\Property\Normalizer;

use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Understands collections of objects in the array<Type> notation used by Flow.
 */
final class CollectionTypeDenormalizer implements
    DenormalizerInterface,
    SerializerAwareInterface,
    CacheableSupportsMethodInterface
{
    private ?DenormalizerInterface $serializer;

    /**
     * {@inheritdoc}
     *
     * @param array<string,mixed> $context
     * @return array<int|string,mixed>
     * @throws NotNormalizableValueException
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): array
    {
        if ($this->serializer === null) {
            throw new BadMethodCallException('Please set a serializer before calling denormalize()!', 1596463254);
        }
        $actualDataType = TypeHandling::getTypeForValue($data);
        if (!TypeHandling::isCollectionType($actualDataType)) {
            throw new InvalidArgumentException(sprintf(
                'Data expected to be a collection type, given: %s',
                get_debug_type($data)
            ), 1596463272);
        }
        try {
            $parsedType = TypeHandling::parseType($type);
        } catch (InvalidTypeException $e) {
            throw new InvalidArgumentException(sprintf(
                'Failed to parse data type "%s": %s',
                $type,
                $e->getMessage()
            ), 1596466324);
        }
        if (empty($parsedType['elementType'])) {
            throw new InvalidArgumentException(sprintf(
                'Type expected to be a generic collection type in the format array<elementType>, given: %s',
                $type
            ), 1596466387);
        }
        foreach ($data as $key => $value) {
            $data[$key] = $this->serializer->denormalize($value, $parsedType['elementType'], $format, $context);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     * @param array<string,mixed> $context
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        if ($this->serializer === null) {
            throw new BadMethodCallException(sprintf(
                'The serializer needs to be set to allow "%s()" to be used.',
                __METHOD__
            ), 1596465675);
        }
        try {
            $parsedType = TypeHandling::parseType($type);
        } catch (InvalidTypeException $e) {
            return false;
        }
        if (empty($parsedType['elementType'])) {
            return false;
        }
        if ($this->serializer instanceof ContextAwareDenormalizerInterface) {
            return $this->serializer->supportsDenormalization(
                reset($data),
                $parsedType['elementType'],
                $format,
                $context
            );
        }
        if (method_exists($this->serializer, 'supportsDenormalization')) {
            return $this->serializer->supportsDenormalization(reset($data), $parsedType['elementType'], $format);
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        if (!$serializer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException(sprintf(
                'Expected a serializer that also implements the %s.',
                DenormalizerInterface::class
            ), 1596464789);
        }

        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return $this->serializer instanceof CacheableSupportsMethodInterface
            && $this->serializer->hasCacheableSupportsMethod();
    }
}
