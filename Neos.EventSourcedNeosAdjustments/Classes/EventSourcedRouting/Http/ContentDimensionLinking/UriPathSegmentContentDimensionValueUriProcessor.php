<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentDimensionLinking;

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
     * @var array
     */
    protected $defaultOptions = [
        'offset' => 0,
        'delimiter' => '_'
    ];

    /**
     * @param Routing\Dto\UriConstraints $uriConstraints
     * @param Dimension\ContentDimension $contentDimension
     * @param Dimension\ContentDimensionValue $contentDimensionValue
     * @param array|null $overrideOptions
     * @return Routing\Dto\UriConstraints
     */
    public function processUriConstraints(
        Routing\Dto\UriConstraints $uriConstraints,
        Dimension\ContentDimension $contentDimension,
        Dimension\ContentDimensionValue $contentDimensionValue,
        array $overrideOptions = null
    ): Routing\Dto\UriConstraints {
        $options = $overrideOptions
            ? Arrays::arrayMergeRecursiveOverrule($this->defaultOptions, $overrideOptions)
            : $this->defaultOptions;
        $pathSegmentPart = $options['offset'] > 0 ? $options['delimiter'] : '';
        $pathSegmentPart .= $contentDimensionValue->getConfigurationValue('resolution.value');

        return $uriConstraints->withPathPrefix($pathSegmentPart, true);
    }
}
