<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Property\Normalizers;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Annotations\Entity;
use Neos\Flow\Annotations\ValueObject;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ScalarNormalizer implements NormalizerInterface, DenormalizerInterface
{

    public function normalize($object, $format = null, array $context = [])
    {
        return $object;
    }

    public function supportsNormalization($data, $format = null)
    {
        // TODO: array of objects
        return TypeHandling::isSimpleType(TypeHandling::getTypeForValue($data));
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        return $data;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        // TODO: array of objects
        return TypeHandling::isSimpleType($type);
    }
}
