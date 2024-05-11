<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * Fusion implementation for a dimensions menu.
 *
 * The items generated by this menu will be all possible variants (according to the configured dimensions
 * and presets) of the given node (including the given node).
 *
 * If a 'dimension' is configured via Fusion, only those variants of the the current subgraph
 * that match its other dimension values will be evaluated
 *
 * Main Options:
 * - dimension (optional, string): Name of the dimension which this menu should be limited to. Example: "language".
 * - values (optional, array): If set, only the given dimension values for the given dimension will be evaluated
 * - includeAllPresets (optional, bool): If set, generalizations in the other dimensions will be evaluated additionally
 *   if necessary to fetch a result for a given dimension value
 */
class DimensionsMenuItemsImplementation extends AbstractMenuItemsImplementation
{
    /**
     * @return array<mixed>
     */
    public function getDimension(): array
    {
        return $this->fusionValue('dimension');
    }

    /**
     * Builds the array of Menu items for this variant menu
     * @return array<int,DimensionMenuItem>
     */
    protected function buildItems(): array
    {
        $menuItems = [];
        $currentNode = $this->getCurrentNode();

        $contentRepositoryId = $currentNode->subgraphIdentity->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get(
            $contentRepositoryId,
        );

        $dimensionMenuItemsImplementationInternals = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new DimensionsMenuItemsImplementationInternalsFactory()
        );
        assert($dimensionMenuItemsImplementationInternals instanceof DimensionsMenuItemsImplementationInternals);

        $interDimensionalVariationGraph = $dimensionMenuItemsImplementationInternals->interDimensionalVariationGraph;
        $currentDimensionSpacePoint = $currentNode->subgraphIdentity->dimensionSpacePoint;
        $contentDimensionIdentifierToLimitTo = $this->getContentDimensionIdentifierToLimitTo();
        // FIXME: node->workspaceName
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId($currentNode->subgraphIdentity->contentStreamId);
        if (is_null($workspace)) {
            return $menuItems;
        }
        $contentGraph = $contentRepository->getContentGraph($workspace->workspaceName);

        foreach ($interDimensionalVariationGraph->getDimensionSpacePoints() as $dimensionSpacePoint) {
            $variant = null;
            if ($this->isDimensionSpacePointRelevant($dimensionSpacePoint)) {
                if ($dimensionSpacePoint->equals($currentDimensionSpacePoint)) {
                    $variant = $currentNode;
                } else {
                    $variant = $contentGraph
                        ->getSubgraph(
                            $dimensionSpacePoint,
                            $currentNode->subgraphIdentity->visibilityConstraints,
                        )
                        ->findNodeById($currentNode->nodeAggregateId);
                }

                if (!$variant && $this->includeGeneralizations() && $contentDimensionIdentifierToLimitTo) {
                    $variant = $this->findClosestGeneralizationMatchingDimensionValue(
                        $dimensionSpacePoint,
                        $contentDimensionIdentifierToLimitTo,
                        $currentNode->nodeAggregateId,
                        $dimensionMenuItemsImplementationInternals,
                        $contentGraph
                    );
                }

                $metadata = $this->determineMetadata($dimensionSpacePoint, $dimensionMenuItemsImplementationInternals);

                if ($variant === null || !$this->isNodeHidden($variant)) {
                    $menuItems[] = new DimensionMenuItem(
                        $variant,
                        $this->isCalculateItemStatesEnabled() ? $this->calculateItemState($variant) : null,
                        $this->determineLabel($variant, $metadata),
                        $metadata,
                        $variant ? $this->buildUri($variant) : null
                    );
                }
            }
        }

        $valuesToRestrictTo = $this->getValuesToRestrictTo();
        if ($contentDimensionIdentifierToLimitTo && $valuesToRestrictTo) {
            $order = array_flip($valuesToRestrictTo);
            usort($menuItems, function (
                DimensionMenuItem $menuItemA,
                DimensionMenuItem $menuItemB
            ) use (
                $order,
                $contentDimensionIdentifierToLimitTo
            ) {
                return (int)$order[$menuItemA->node?->subgraphIdentity->dimensionSpacePoint->getCoordinate(
                    $contentDimensionIdentifierToLimitTo
                )] <=> (int)$order[$menuItemB->node?->subgraphIdentity->dimensionSpacePoint->getCoordinate(
                    $contentDimensionIdentifierToLimitTo
                )];
            });
        }

        return $menuItems;
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return bool
     */
    protected function isDimensionSpacePointRelevant(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        return !$this->getContentDimensionIdentifierToLimitTo() // no limit to one dimension, so all DSPs are relevant
            // always include the current variant
            || $dimensionSpacePoint->equals($this->currentNode->subgraphIdentity->dimensionSpacePoint)
            // include all direct variants in the dimension we're limited to unless their values
            // in that dimension are missing in the specified list
            || $dimensionSpacePoint->isDirectVariantInDimension(
                $this->currentNode->subgraphIdentity->dimensionSpacePoint,
                $this->getContentDimensionIdentifierToLimitTo()
            )
            && (
                empty($this->getValuesToRestrictTo())
                || in_array(
                    $dimensionSpacePoint->getCoordinate($this->getContentDimensionIdentifierToLimitTo()),
                    $this->getValuesToRestrictTo()
                )
            );
    }

    protected function findClosestGeneralizationMatchingDimensionValue(
        DimensionSpacePoint $dimensionSpacePoint,
        ContentDimensionId $contentDimensionIdentifier,
        NodeAggregateId $nodeAggregateId,
        DimensionsMenuItemsImplementationInternals $dimensionMenuItemsImplementationInternals,
        ContentGraphInterface $contentGraph
    ): ?Node {
        $generalizations = $dimensionMenuItemsImplementationInternals->interDimensionalVariationGraph
            ->getWeightedGeneralizations($dimensionSpacePoint);
        ksort($generalizations);
        foreach ($generalizations as $generalization) {
            if (
                $generalization->getCoordinate($contentDimensionIdentifier)
                === $dimensionSpacePoint->getCoordinate($contentDimensionIdentifier)
            ) {
                $variant = $contentGraph
                    ->getSubgraph(
                        $generalization,
                        $this->getCurrentNode()->subgraphIdentity->visibilityConstraints,
                    )
                    ->findNodeById($nodeAggregateId);
                if ($variant) {
                    return $variant;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>
     */
    protected function determineMetadata(
        DimensionSpacePoint $dimensionSpacePoint,
        DimensionsMenuItemsImplementationInternals $dimensionMenuItemsImplementationInternals
    ): array {
        $metadata = $dimensionSpacePoint->coordinates;
        array_walk(
            $metadata,
            function (&$dimensionValue, $rawDimensionIdentifier) use ($dimensionMenuItemsImplementationInternals) {
                $dimensionIdentifier = new ContentDimensionId($rawDimensionIdentifier);
                $dimensionValue = [
                'value' => $dimensionValue,
                'label' => $dimensionMenuItemsImplementationInternals
                    ->contentDimensionSource->getDimension($dimensionIdentifier)
                    ?->getValue($dimensionValue)?->getConfigurationValue('label') ?: $dimensionIdentifier,
                'isPinnedDimension' => (
                    !$this->getContentDimensionIdentifierToLimitTo()
                    || $dimensionIdentifier->equals($this->getContentDimensionIdentifierToLimitTo())
                )
                ];
            }
        );

        return $metadata;
    }

    /**
     * @param array<string,mixed> $metadata
     */
    protected function determineLabel(?Node $variant = null, array $metadata = []): string
    {
        if ($this->getContentDimensionIdentifierToLimitTo()) {
            return $metadata[$this->getContentDimensionIdentifierToLimitTo()->value]['label'] ?: '';
        } elseif ($variant) {
            return $variant->getLabel() ?: '';
        } else {
            return array_reduce($metadata, function ($carry, $item) {
                return $carry . (empty($carry) ? '' : '-') . $item['label'];
            }, '');
        }
    }

    protected function calculateItemState(?Node $variant = null): MenuItemState
    {
        if (is_null($variant)) {
            return MenuItemState::ABSENT;
        }

        if ($variant === $this->currentNode) {
            return MenuItemState::CURRENT;
        }
        return MenuItemState::NORMAL;
    }


    /**
     * In some cases generalization of the other dimension values is feasible
     * to find a dimension space point in which a variant can be resolved
     * @return bool
     */
    protected function includeGeneralizations(): bool
    {
        return $this->getContentDimensionIdentifierToLimitTo() && $this->fusionValue('includeAllPresets');
    }

    /**
     * @return ContentDimensionId|null
     */
    protected function getContentDimensionIdentifierToLimitTo(): ?ContentDimensionId
    {
        return $this->fusionValue('dimension')
            ? new ContentDimensionId($this->fusionValue('dimension'))
            : null;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getValuesToRestrictTo(): array
    {
        return $this->fusionValue('values') ?? ($this->fusionValue('presets') ?? []);
    }
}
