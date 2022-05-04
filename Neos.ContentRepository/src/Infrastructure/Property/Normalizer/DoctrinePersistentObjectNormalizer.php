<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Infrastructure\Property\Normalizer;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Annotations\Entity;
use Neos\Flow\Annotations\ValueObject;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DoctrinePersistentObjectNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        return [
            '__flow_object_type' => TypeHandling::getTypeForValue($object),
            '__identifier' => $this->persistenceManager->getIdentifierByObject($object)
        ];
    }

    public function supportsNormalization($data, string $format = null)
    {
        return (
            $this->reflectionService->isClassAnnotatedWith(TypeHandling::getTypeForValue($data), Entity::class) ||
            $this->reflectionService->isClassAnnotatedWith(TypeHandling::getTypeForValue($data), ValueObject::class) ||
            $this->reflectionService->isClassAnnotatedWith(
                TypeHandling::getTypeForValue($data),
                \Doctrine\ORM\Mapping\Entity::class
            )
        );
    }

    /**
     * @param array<string,mixed> $context
     */
    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        return $this->persistenceManager->getObjectByIdentifier($data['__identifier'], $data['__flow_object_type']);
    }

    public function supportsDenormalization($data, $type, string $format = null)
    {
        // NOTE: we do not check for $type which is the expected type,
        // but we instead check for $data['__flow_object_type']. This is
        // needed because $type might be an abstract type or an interface;
        // and $data['__flow_object_type'] is the *specific* type of the
        // object.
        if (is_array($data) && isset($data['__flow_object_type'])) {
            return (
                $this->reflectionService->isClassAnnotatedWith($data['__flow_object_type'], Entity::class) ||
                $this->reflectionService->isClassAnnotatedWith($data['__flow_object_type'], ValueObject::class) ||
                $this->reflectionService->isClassAnnotatedWith(
                    $data['__flow_object_type'],
                    \Doctrine\ORM\Mapping\Entity::class
                )
            );
        }
        return false;
    }
}
