<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;


use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;

class Separator
{
    public function __construct(
        public readonly string $value
    ) {}
}
