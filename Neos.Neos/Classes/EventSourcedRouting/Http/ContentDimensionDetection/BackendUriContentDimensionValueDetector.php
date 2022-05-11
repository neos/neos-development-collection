<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\EventSourcedRouting\Http\ContentDimensionDetection;

use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\Neos\EventSourcedRouting\Routing\WorkspaceNameAndDimensionSpacePointForUriSerialization;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A content dimension preset detector that evaluates backend URIs, i.e. component contexts containing context paths
 */
final class BackendUriContentDimensionValueDetector implements ContentDimensionValueDetectorInterface
{
    /**
     * @param array<string,mixed>|null $overrideOptions
     */
    public function detectValue(
        Dimension\ContentDimension $contentDimension,
        ServerRequestInterface $request,
        ?array $overrideOptions = null
    ): ?Dimension\ContentDimensionValue {
        $path = $request->getUri()->getPath();
        $firstPivot = mb_strpos($path, '@');
        if ($firstPivot !== false) {
            $path = '/' . mb_substr($path, $firstPivot);
        }
        $secondPivot = mb_strpos($path, '.');
        if ($secondPivot !== false) {
            $path = mb_substr($path, 0, $secondPivot);
        }
        $nodePathAndContext = WorkspaceNameAndDimensionSpacePointForUriSerialization::fromBackendUri($path);
        $detectedValue = $nodePathAndContext->getDimensionSpacePoint()->getCoordinate($contentDimension->identifier);

        return $detectedValue
            ? $contentDimension->getValue($detectedValue)
            : null;
    }
}
