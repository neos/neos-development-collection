<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Infrastructure\Property\Normalizer;

use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as OriginalObjectNormalizer;

/**
 * An implementation of the ObjectNormalizer that supports Flow proxy classes
 *
 * Flow issue {@see https://github.com/neos/flow-development-collection/issues/3076#issuecomment-1790701913}
 */
final class ProxyAwareObjectNormalizer extends OriginalObjectNormalizer
{
    /**
     * @param string $class
     * @param array<string|int, mixed> $data
     * @param array<string|int, mixed> $context
     * @param \ReflectionClass<object> $reflectionClass
     * @param array<string|int, mixed>|bool $allowedAttributes
     */
    protected function getConstructor(array &$data, $class, array &$context, \ReflectionClass $reflectionClass, $allowedAttributes): ?\ReflectionMethod
    {
        if (interface_exists(ProxyInterface::class) && $reflectionClass->implementsInterface(ProxyInterface::class)) {
            $parentReflectionClass = $reflectionClass->getParentClass();
            if ($parentReflectionClass === false) {
                throw new \RuntimeException(sprintf('Proxy error. Class "%s" must have a parent class.', $class), 1698652279);
            }
            return $parentReflectionClass->getConstructor();
        }
        return $reflectionClass->getConstructor();
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
