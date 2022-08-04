<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Infrastructure\Property\Normalizer;

use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as OriginalObjectNormalizer;

/**
 * An implementation of the ObjectNormalizer that supports Flow proxy classes
 */
final class ProxyAwareObjectNormalizer extends OriginalObjectNormalizer
{
    protected function getConstructor(array &$data, $class, array &$context, \ReflectionClass $reflectionClass, $allowedAttributes): ?\ReflectionMethod
    {
        if (interface_exists(ProxyInterface::class) && $reflectionClass->implementsInterface(ProxyInterface::class)) {
            return $reflectionClass->getParentClass()->getConstructor();
        }
        return $reflectionClass->getConstructor();
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
