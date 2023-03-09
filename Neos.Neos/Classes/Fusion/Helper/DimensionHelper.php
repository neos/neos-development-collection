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

use Neos\ContentRepository\Core\Dimension\ContentDimension;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValue;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValues;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;

/**
 * Helper for nodes in various content dimensions.
 */
class DimensionHelper implements ProtectedContextAwareInterface
{
    /**
     * @var ContentRepositoryRegistry
     */
    #[Flow\Inject(lazy: true)]
    protected $contentRepositoryRegistry;

    /**
     * Get current dimension value for `node`.
     *
     * Example::
     *
     *     Neos.Dimension.currentValue(node, 'language')
     *
     * @param Node $node
     * @param string|ContentDimensionId $dimensionName
     * @return string|null
     */
    public function currentValue(Node $node, string|ContentDimensionId $dimensionName): ?string
    {
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;
        return $node->subgraphIdentity->dimensionSpacePoint->getCoordinate($contentDimensionId);
    }

    /**
     * Get original dimension value for `node`. Differs form current value in cases of dimension fallback.
     *
     * Example::
     *
     *     Neos.Dimension.originValue(node, 'language')
     *
     * @param Node $node
     * @param string|ContentDimensionId $dimensionName
     * @return string|null
     */
    public function originValue(Node $node, string|ContentDimensionId $dimensionName): ?string
    {
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;
        return $node->originDimensionSpacePoint->getCoordinate($contentDimensionId);
    }

    /**
     * Get default dimension value for the content repository defined by `node`.
     *
     * Example::
     *
     *     Neos.Dimension.findDefaultValue(node, 'language')
     *
     * @param Node $node
     * @param string|ContentDimensionId $dimensionName
     * @return string|null
     */
    public function findDefaultValue(Node $node, string|ContentDimensionId $dimensionName): ?string
    {
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;
        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId);
        $rootValues = $contentRepository
            ->getContentDimensionSource()
            ->getDimension($contentDimensionId)
            ?->getRootValues() ?: [];

        $dimensionValue = reset($rootValues);

        return $dimensionValue ? $dimensionValue->value : null;
    }

    /**
     * Find all content dimensions in content repository defined by `node`.
     *
     * Example::
     *
     *     Neos.Dimension.findDimensions(node)
     *
     * @param Node $node
     * @return array<string,ContentDimension>
     */
    public function findDimensions(Node $node): array
    {
        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId);

        return $contentRepository->getContentDimensionSource()->getContentDimensionsOrderedByPriority();
    }

    /**
     * Find the variant of `node` in the specified dimension and value.
     *
     * Example::
     *
     *     Neos.Dimension.findVariantInDimension(node, 'language', 'en_UK')
     *
     * @param Node $node
     * @param string|ContentDimensionId $dimensionName
     * @param string|ContentDimensionValue $dimensionValue
     * @return Node|null
     */
    public function findVariantInDimension(Node $node, string|ContentDimensionId $dimensionName, string|ContentDimensionValue $dimensionValue): ?Node
    {
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;
        $contentDimensionValue = is_string($dimensionValue) ? new ContentDimensionValue($dimensionValue) : $dimensionValue;
        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId);

        return $contentRepository
            ->getContentGraph()
            ->getSubgraph($node->subgraphIdentity->contentStreamId, $node->subgraphIdentity->dimensionSpacePoint->vary($contentDimensionId, $contentDimensionValue->value), $node->subgraphIdentity->visibilityConstraints)
            ->findNodeById($node->nodeAggregateId);
    }

    /**
     * Find all variants of `node` across the specified dimension.
     *
     * Example::
     *
     *     Neos.Dimension.findVariantsInDimension(node, 'language')
     *
     * @param Node $node
     * @param string|ContentDimensionId $dimensionName
     * @return Nodes
     */
    public function findVariantsInDimension(Node $node, string|ContentDimensionId $dimensionName): Nodes
    {
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;
        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId);

        $variantNodes = [];
        foreach ($contentRepository->getVariationGraph()->getDimensionSpacePoints() as $dimensionSpacePoint) {
            if ($dimensionSpacePoint->equals($node->subgraphIdentity->dimensionSpacePoint)) {
                $variantNodes[] = $node;
            } elseif (
                $dimensionSpacePoint->isDirectVariantInDimension(
                    $node->subgraphIdentity->dimensionSpacePoint,
                    $contentDimensionId
                )
            ) {
                $variantNode = $contentRepository
                    ->getContentGraph()
                    ->getSubgraph($node->subgraphIdentity->contentStreamId, $dimensionSpacePoint, $node->subgraphIdentity->visibilityConstraints)
                    ->findNodeById($node->nodeAggregateId);

                if ($variantNode instanceof Node) {
                    $variantNodes[] = $variantNode;
                }
            }
        }

        return Nodes::fromArray($variantNodes);
    }

    /**
     * Find all values the specified dimension can have.
     *
     * Example::
     *
     *     Neos.Dimension.findPotentialDimensionValues(node, 'language')
     *
     * @param Node $node
     * @param string|ContentDimensionId $dimensionName
     * @return ContentDimensionValues
     */
    public function findPotentialDimensionValues(Node $node, string|ContentDimensionId $dimensionName): ContentDimensionValues
    {
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;
        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId);

        return $contentRepository->getContentDimensionSource()->getDimension($contentDimensionId)?->values ?: new ContentDimensionValues([]);
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
