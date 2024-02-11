<?php

declare(strict_types=1);

namespace Neos\Fusion\Core\Cache;

use Neos\Flow\Property\PropertyMapper;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Serializer for Fusion's [at]cache.context values
 *
 * Uses the Flows's property mapper as implementation.
 * It relies on a converter being available from the context value type to string and reverse.
 *
 * {@see RuntimeContentCache::serializeContext()}
 * {@see RuntimeContentCache::unserializeContext()}
 *
 * @internal
 */
final class FusionContextSerializer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly PropertyMapper $propertyMapper
    ) {
    }

    /**
     * @param array<int|string,mixed> $context
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        return $this->propertyMapper->convert($data, $type);
    }

    /**
     * @param array<int|string,mixed> $context
     * @return array<int|string,mixed>
     */
    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        return $this->propertyMapper->convert($object, 'string');
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return true;
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return true;
    }
}
