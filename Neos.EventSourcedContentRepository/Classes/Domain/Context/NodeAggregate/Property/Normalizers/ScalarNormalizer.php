<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Property\Normalizers;

use Neos\Utility\TypeHandling;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ScalarNormalizer implements NormalizerInterface, DenormalizerInterface
{

    public function normalize($object, string $format = null, array $context = [])
    {
        return $object;
    }

    public function supportsNormalization($data, string $format = null)
    {
        // TODO: array of objects
        return TypeHandling::isSimpleType(TypeHandling::getTypeForValue($data));
    }

    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        return $data;
    }

    public function supportsDenormalization($data, $type, string $format = null)
    {
        // TODO: array of objects
        return TypeHandling::isSimpleType($type);
    }
}
