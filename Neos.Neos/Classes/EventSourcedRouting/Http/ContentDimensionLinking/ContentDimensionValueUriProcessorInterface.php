<?php
declare(strict_types=1);
namespace Neos\Neos\EventSourcedRouting\Http\ContentDimensionLinking;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing;
use Neos\ContentRepository\DimensionSpace\Dimension;

/**
 * Interface for processing an URI to match the given content dimension value
 */
interface ContentDimensionValueUriProcessorInterface
{
    /**
     * @param array<string,mixed>|null $overrideOptions
     */
    public function processUriConstraints(
        Routing\Dto\UriConstraints $uriConstraints,
        Dimension\ContentDimension $contentDimension,
        Dimension\ContentDimensionValue $contentDimensionValue,
        ?array $overrideOptions = null
    ): Routing\Dto\UriConstraints;
}
