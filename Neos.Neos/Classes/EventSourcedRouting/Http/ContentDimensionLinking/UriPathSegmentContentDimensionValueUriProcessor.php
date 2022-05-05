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

use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\Flow\Mvc\Routing;
use Neos\Utility\Arrays;

/**
 * URI path segment based content dimension value uri processor
 */
final class UriPathSegmentContentDimensionValueUriProcessor implements ContentDimensionValueUriProcessorInterface
{
    /**
     * @var array<string,mixed>
     */
    protected array $defaultOptions = [
        'offset' => 0,
        'delimiter' => '_'
    ];

    /**
     * @param array<string,mixed>|null $overrideOptions
     */
    public function processUriConstraints(
        Routing\Dto\UriConstraints $uriConstraints,
        Dimension\ContentDimension $contentDimension,
        Dimension\ContentDimensionValue $contentDimensionValue,
        ?array $overrideOptions = null
    ): Routing\Dto\UriConstraints {
        $options = $overrideOptions
            ? Arrays::arrayMergeRecursiveOverrule($this->defaultOptions, $overrideOptions)
            : $this->defaultOptions;
        $pathSegmentPart = $options['offset'] > 0 ? $options['delimiter'] : '';
        $pathSegmentPart .= $contentDimensionValue->getConfigurationValue('resolution.value');

        return $uriConstraints->withPathPrefix($pathSegmentPart, true);
    }
}
