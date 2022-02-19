<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentDimensionDetection;

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
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Routing\WorkspaceNameAndDimensionSpacePointForUriSerialization;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A content dimension preset detector that evaluates backend URIs, i.e. component contexts containing context paths
 */
final class BackendUriContentDimensionValueDetector implements ContentDimensionValueDetectorInterface
{
    /**
     * @param Dimension\ContentDimension $contentDimension
     * @param ServerRequestInterface $request
     * @param array|null $overrideOptions
     * @return Dimension\ContentDimensionValue|null
     */
    public function detectValue(
        Dimension\ContentDimension $contentDimension,
        ServerRequestInterface $request,
        array $overrideOptions = null
    ): ?Dimension\ContentDimensionValue {
        $path = $request->getUri()->getPath();
        $path = '/' . mb_substr($path, mb_strpos($path, '@'));
        if (mb_strpos($path, '.') !== false) {
            $path = mb_substr($path, 0, mb_strrpos($path, '.'));
        }
        $nodePathAndContext = WorkspaceNameAndDimensionSpacePointForUriSerialization::fromBackendUri($path);
        $detectedValue = $nodePathAndContext->getDimensionSpacePoint()->getCoordinate($contentDimension->identifier);

        return $detectedValue
            ? $contentDimension->getValue($detectedValue)
            : null;
    }
}
