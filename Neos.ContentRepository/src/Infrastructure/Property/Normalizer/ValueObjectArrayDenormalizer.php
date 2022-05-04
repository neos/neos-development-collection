<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Infrastructure\Property\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ValueObjectArrayDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string,mixed> $context
     */
    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        return $type::fromArray($data);
    }

    public function supportsDenormalization($data, $type, string $format = null): bool
    {
        return is_array($data) && class_exists($type) && method_exists($type, 'fromArray');
    }
}
