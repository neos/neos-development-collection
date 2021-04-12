<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Infrastructure\Property\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ValueObjectFloatDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        return $type::fromFloat($data);
    }

    public function supportsDenormalization($data, $type, string $format = null)
    {
        return is_float($data) && class_exists($type) && method_exists($type, 'fromFloat');
    }
}
