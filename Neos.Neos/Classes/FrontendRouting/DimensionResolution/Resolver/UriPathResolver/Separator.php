<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class Separator
{
    private function __construct(
        public readonly string $value
    ) {
    }

    public static function fromString(string $value): self
    {
        if (str_contains($value, '/')) {
            throw new UriPathResolverConfigurationException('Separator is not allowed to contain "/"');
        }
        return new self($value);
    }
}
