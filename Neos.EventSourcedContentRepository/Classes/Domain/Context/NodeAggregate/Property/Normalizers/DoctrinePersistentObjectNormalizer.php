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

class DoctrinePersistentObjectNormalizer implements NormalizerInterface, DenormalizerInterface
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

    public function normalize($object, $format = null, array $context = [])
    {
        return [
            '__flow_object_type' => TypeHandling::getTypeForValue($object),
            '__identifier' => $this->persistenceManager->getIdentifierByObject($object)
        ];
    }

    public function supportsNormalization($data, $format = null)
    {

        return (
            $this->reflectionService->isClassAnnotatedWith(TypeHandling::getTypeForValue($data), Entity::class) ||
            $this->reflectionService->isClassAnnotatedWith(TypeHandling::getTypeForValue($data), ValueObject::class) ||
            $this->reflectionService->isClassAnnotatedWith(TypeHandling::getTypeForValue($data), \Doctrine\ORM\Mapping\Entity::class)
        );
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        return $this->persistenceManager->getObjectByIdentifier($data['__identifier'], $data['__flow_object_type']);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return (
            $this->reflectionService->isClassAnnotatedWith($type, Entity::class) ||
            $this->reflectionService->isClassAnnotatedWith($type, ValueObject::class) ||
            $this->reflectionService->isClassAnnotatedWith($type, \Doctrine\ORM\Mapping\Entity::class)
        );
    }
}
