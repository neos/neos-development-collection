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

namespace Neos\Neos\Fusion\Helper;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;

/**
 * Render Content Dimension Names, Node Labels
 *
 * These helpers are *WORK IN PROGRESS* and *NOT STABLE YET*
 */
class RenderingHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    #[Flow\Inject]
    protected ContentDimensionSourceInterface $contentDimensionSource;

    /**
     * Render a human-readable description for the passed $dimensions
     *
     * @param array<string,mixed> $dimensions
     */
    public function renderDimensions(array $dimensions): string
    {
        $rendered = [];
        foreach ($dimensions as $dimensionIdentifier => $dimensionValue) {
            $dimension = $this->contentDimensionSource->getDimension(
                new ContentDimensionIdentifier($dimensionIdentifier)
            );
            $value = $dimension?->getValue($dimensionValue);
            $rendered[] = $dimension?->getConfigurationValue('label')
                . ' ' . $value?->getConfigurationValue('label');
        }

        return implode(', ', $rendered);
    }

    /**
     * Render the label for the given $nodeTypeName
     *
     * @param string $nodeTypeName
     * @throws NodeTypeNotFoundException
     * @return string
     */
    public function labelForNodeType($nodeTypeName)
    {
        if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            $explodedNodeTypeName = explode(':', $nodeTypeName);

            return end($explodedNodeTypeName);
        }

        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);

        return $nodeType->getLabel();
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
