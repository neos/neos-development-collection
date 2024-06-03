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
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Helper for nodes in various content dimensions.
 *
 * @api For usage in Fusion/Eel as `Neos.Dimension.*`
 */
final class DimensionHelper implements ProtectedContextAwareInterface
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
     * @param ContentDimensionId|string $dimensionName String will be converted to `ContentDimensionId`
     * @return ContentDimensionValue|null
     */
    public function currentValue(Node $node, ContentDimensionId|string $dimensionName): ?ContentDimensionValue
    {
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;
        $currentDimensionValueAsString = $node->dimensionSpacePoint->getCoordinate($contentDimensionId);

        if (is_string($currentDimensionValueAsString)) {
            return $this->allDimensionValues($node, $contentDimensionId)?->getValue($currentDimensionValueAsString);
        }

        return null;
    }

    /**
     * Get original dimension value for `node`. Differs form current value in cases of dimension fallback.
     *
     * Example::
     *
     *     Neos.Dimension.originValue(node, 'language')
     *
     * @param Node $node
     * @param ContentDimensionId|string $dimensionName String will be converted to `ContentDimensionId`
     * @return ContentDimensionValue|null
     */
    public function originValue(Node $node, ContentDimensionId|string $dimensionName): ?ContentDimensionValue
    {
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;
        $originalDimensionValueAsString = $node->originDimensionSpacePoint->getCoordinate($contentDimensionId);

        if (is_string($originalDimensionValueAsString)) {
            return $this->allDimensionValues($node, $contentDimensionId)?->getValue($originalDimensionValueAsString);
        }

        return null;
    }

    /**
     * Find all content dimensions in content repository defined by `contentRepositoryId` or `node`.
     *
     * Example::
     *
     *     Neos.Dimension.all(contentRepositoryId)
     *     Neos.Dimension.all(node)
     *
     * @param ContentRepositoryId|Node $subject Node will be used to determine `ContentRepositoryId`
     * @return array<string,ContentDimension>
     */
    public function all(ContentRepositoryId|Node $subject): array
    {
        $contentRepositoryId = $subject instanceof Node ? $subject->contentRepositoryId : $subject;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        return $contentRepository->getContentDimensionSource()->getContentDimensionsOrderedByPriority();
    }


    /**
     * Find all content dimension values in content repository defined by `contentRepositoryId` or `node`.
     *
     * Example::
     *
     *     Neos.Dimension.allDimensionValues(contentRepositoryId, 'language')
     *     Neos.Dimension.allDimensionValues(node, 'language')
     *
     * @param ContentRepositoryId|Node $subject Node will be used to determine `ContentRepositoryId`
     * @param ContentDimensionId|string $dimensionName String will be converted to `ContentDimensionId`
     * @return ContentDimensionValues|null
     */
    public function allDimensionValues(ContentRepositoryId|Node $subject, ContentDimensionId|string $dimensionName): ?ContentDimensionValues
    {
        $contentRepositoryId = $subject instanceof Node ? $subject->contentRepositoryId : $subject;
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentDimension = $contentRepository->getContentDimensionSource()->getDimension($contentDimensionId);

        return $contentDimension?->values;
    }

    /**
     * Find the variant of `node` in the specified dimension and value.
     *
     * Example::
     *
     *     Neos.Dimension.findVariantInDimension(node, 'language', 'en_UK')
     *
     * @param Node $node
     * @param ContentDimensionId|string $dimensionName String will be converted to `ContentDimensionId`
     * @param ContentDimensionValue|string $dimensionValue String will be converted to `ContentDimensionValue`
     * @return Node|null
     */
    public function findVariantInDimension(Node $node, ContentDimensionId|string $dimensionName, ContentDimensionValue|string $dimensionValue): ?Node
    {
        $contentDimensionId = is_string($dimensionName) ? new ContentDimensionId($dimensionName) : $dimensionName;
        $contentDimensionValue = is_string($dimensionValue) ? new ContentDimensionValue($dimensionValue) : $dimensionValue;
        $contentRepository = $this->contentRepositoryRegistry->get($node->contentRepositoryId);

        try {
            return $contentRepository
                ->getContentGraph($node->workspaceName)
                ->getSubgraph(
                    $node->dimensionSpacePoint->vary($contentDimensionId, $contentDimensionValue->value),
                    $node->visibilityConstraints
                )->findNodeById($node->aggregateId);
        } catch (WorkspaceDoesNotExist) {
            return null;
        }
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
