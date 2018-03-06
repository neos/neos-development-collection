<?php
namespace Neos\Neos\Http\ContentDimensionDetection;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Http;
use Neos\Neos\Routing\WorkspaceNameAndDimensionSpacePointForUriSerialization;

/**
 * A content dimension preset detector that evaluates backend URIs, i.e. component contexts containing context paths
 */
final class BackendUriContentDimensionValueDetector implements ContentDimensionValueDetectorInterface
{
    /**
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * @param Dimension\ContentDimension $contentDimension
     * @param Http\Component\ComponentContext $componentContext
     * @param array|null $overrideOptions
     * @return Dimension\ContentDimensionValue|null
     */
    public function detectValue(Dimension\ContentDimension $contentDimension, Http\Component\ComponentContext $componentContext, array $overrideOptions = null): ?Dimension\ContentDimensionValue
    {
        $path = $componentContext->getHttpRequest()->getUri()->getPath();
        $path = '/' . mb_substr($path, mb_strpos($path, '@'));
        if (mb_strpos($path, '.') !== false) {
            $path = mb_substr($path, 0, mb_strrpos($path, '.'));
        }
        $nodePathAndContext = WorkspaceNameAndDimensionSpacePointForUriSerialization::fromBackendUri($path);
        $detectedValue = $nodePathAndContext->getDimensionSpacePoint()->getCoordinate($contentDimension->getIdentifier());

        return $detectedValue
            ? $contentDimension->getValue($detectedValue)
            : null;
    }
}
