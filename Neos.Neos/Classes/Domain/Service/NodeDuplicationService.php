<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Core\CommandHandler\Commands;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesForName;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferenceToWrite;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Exception\TetheredNodesCannotBePartiallyCopied;
use Neos\Neos\Domain\Service\NodeDuplication\NodeAggregateIdMapping;
use Neos\Neos\Domain\Service\NodeDuplication\TransientNodeCopy;
use Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints;

/**
 * Service to copy node recursively - as there is no equivalent content repository core command.
 *
 * @Flow\Scope("singleton")
 * @api
 */
final class NodeDuplicationService
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    /**
     * Copies the specified source node and its children to the target node
     *
     * Note about dimensions:
     * ---------------------
     *   Currently the copying is primitive as that we take the read-model of the dimension to copy (the subgraph). and paste that into the target dimension.
     *   That means that the copy does not alter other dimensions and that virtual variants are materialised.
     *   For more information see {@link https://github.com/neos/neos-development-collection/issues/5054}
     *
     * Note about constraints:
     * ----------------------
     *   As we cannot rely on the full integrate on the subgraph regarding the current node type schema it might not be possible to copy a node and its children.
     *   For example copying a node with tethered children that is not tethered according to the current node type schema, or copying properties that are not defined
     *   in the current node type schema anymore. In those cases the structure adjustments have to be executed. (todo only copy what is applicable and be graceful)
     *
     * Note about partial copy on error:
     * --------------------------------
     *   As the above mentioned constraints can fail and we handle the determined content repository commands one by one, a failure will lead to a partially evaluated copy.
     *   The content repository is still consistent but the intent is only partially fulfilled.
     *
     * @param ContentRepositoryId $contentRepositoryId The content repository the copy operation is performed in
     * @param WorkspaceName $workspaceName The name of the workspace where the node is copied and from and into (todo permit cross workspace copying?)
     * @param DimensionSpacePoint $sourceDimensionSpacePoint The dimension to copy from
     * @param NodeAggregateId $sourceNodeAggregateId The node aggregate which to copy (including its children)
     * @param OriginDimensionSpacePoint $targetDimensionSpacePoint the dimension space point which is the target of the copy
     * @param NodeAggregateId $targetParentNodeAggregateId Node aggregate id of the target node's parent. If not given, the node will be added as the parent's first child
     * @param NodeAggregateId|null $targetSucceedingSiblingNodeAggregateId Node aggregate id of the target node's succeeding sibling (optional)
     * @param NodeAggregateIdMapping|null $nodeAggregateIdMapping An assignment of "old" to "new" NodeAggregateIds
     */
    public function copyNodesRecursively(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        DimensionSpacePoint $sourceDimensionSpacePoint,
        NodeAggregateId $sourceNodeAggregateId,
        OriginDimensionSpacePoint $targetDimensionSpacePoint,
        NodeAggregateId $targetParentNodeAggregateId,
        ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId,
        ?NodeAggregateIdMapping $nodeAggregateIdMapping = null
    ): void {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $subgraph = $contentRepository->getContentGraph($workspaceName)->getSubgraph($sourceDimensionSpacePoint, NeosVisibilityConstraints::excludeRemoved());

        $targetParentNode = $subgraph->findNodeById($targetParentNodeAggregateId);
        if ($targetParentNode === null) {
            throw new NodeAggregateCurrentlyDoesNotExist(sprintf('The target parent node aggregate "%s" does not exist', $targetParentNodeAggregateId->value), 1732006769);
        }

        $subtree = $subgraph->findSubtree($sourceNodeAggregateId, FindSubtreeFilter::create());
        if ($subtree === null) {
            throw new NodeAggregateCurrentlyDoesNotExist(sprintf('The source node aggregate "%s" does not exist', $sourceNodeAggregateId->value), 1732006772);
        }

        $commands = $this->calculateCopyNodesRecursively(
            $subtree,
            $subgraph,
            $workspaceName,
            $targetDimensionSpacePoint,
            $targetParentNodeAggregateId,
            $targetSucceedingSiblingNodeAggregateId,
            $nodeAggregateIdMapping
        );

        foreach ($commands as $command) {
            $contentRepository->handle($command);
        }
    }

    /**
     * @internal implementation detail of {@see NodeDuplicationService::copyNodesRecursively}, exposed for EXPERIMENTAL use cases, the API can change any time!
     */
    public function calculateCopyNodesRecursively(
        Subtree $subtreeToCopy,
        ContentSubgraphInterface $subgraph,
        WorkspaceName $targetWorkspaceName,
        OriginDimensionSpacePoint $targetDimensionSpacePoint,
        NodeAggregateId $targetParentNodeAggregateId,
        ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId,
        ?NodeAggregateIdMapping $nodeAggregateIdMapping = null
    ): Commands {
        $transientNodeCopy = TransientNodeCopy::forEntry(
            $subtreeToCopy,
            $targetWorkspaceName,
            $targetDimensionSpacePoint,
            $nodeAggregateIdMapping ?? NodeAggregateIdMapping::createEmpty()
        );

        $createCopyOfNodeCommand = CreateNodeAggregateWithNode::create(
            $transientNodeCopy->workspaceName,
            $transientNodeCopy->aggregateId,
            $subtreeToCopy->node->nodeTypeName,
            $targetDimensionSpacePoint,
            $targetParentNodeAggregateId,
            succeedingSiblingNodeAggregateId: $targetSucceedingSiblingNodeAggregateId,
            // todo skip properties not in schema
            initialPropertyValues: PropertyValuesToWrite::fromArray(
                iterator_to_array($subtreeToCopy->node->properties)
            ),
            references: $this->serializeProjectedReferences(
                $subgraph->findReferences($subtreeToCopy->node->aggregateId, FindReferencesFilter::create())
            )
        );

        $createCopyOfNodeCommand = $createCopyOfNodeCommand->withTetheredDescendantNodeAggregateIds(
            $transientNodeCopy->tetheredNodeAggregateIds
        );

        $commands = Commands::create($createCopyOfNodeCommand);

        foreach ($subtreeToCopy->node->tags->withoutInherited() as $explicitTag) {
            $commands = $commands->append(
                TagSubtree::create(
                    $transientNodeCopy->workspaceName,
                    $transientNodeCopy->aggregateId,
                    $transientNodeCopy->originDimensionSpacePoint->toDimensionSpacePoint(),
                    NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
                    $explicitTag
                )
            );
        }

        foreach ($subtreeToCopy->children as $childSubtree) {
            if ($subtreeToCopy->node->classification->isTethered() && $childSubtree->node->classification->isTethered()) {
                // TODO we assume here that the child node is tethered because the grandparent specifies that.
                // this is not always fully correct and we could loosen the constraint by checking the node type schema
                throw new TetheredNodesCannotBePartiallyCopied(sprintf('Cannot copy tethered node %s because child node %s is also tethered. Only standalone tethered nodes can be copied.', $subtreeToCopy->node->aggregateId->value, $childSubtree->node->aggregateId->value), 1731264887);
            }
            $commands = $this->commandsForSubtreeRecursively($transientNodeCopy, $childSubtree, $subgraph, $commands);
        }

        return $commands;
    }

    private function commandsForSubtreeRecursively(TransientNodeCopy $transientParentNode, Subtree $subtree, ContentSubgraphInterface $subgraph, Commands $commands): Commands
    {
        if ($subtree->node->classification->isTethered()) {
            /**
             * Case Node is tethered
             */
            $transientNodeCopy = $transientParentNode->forTetheredChildNode(
                $subtree
            );

            if ($subtree->node->properties->count() > 0) {
                $setPropertiesOfTetheredNodeCommand = SetNodeProperties::create(
                    $transientParentNode->workspaceName,
                    $transientNodeCopy->aggregateId,
                    $transientParentNode->originDimensionSpacePoint,
                    // todo skip properties not in schema
                    PropertyValuesToWrite::fromArray(
                        iterator_to_array($subtree->node->properties)
                    ),
                );

                $commands = $commands->append($setPropertiesOfTetheredNodeCommand);
            }

            $references = $subgraph->findReferences($subtree->node->aggregateId, FindReferencesFilter::create());
            if ($references->count() > 0) {
                $setReferencesOfTetheredNodeCommand = SetNodeReferences::create(
                    $transientParentNode->workspaceName,
                    $transientNodeCopy->aggregateId,
                    $transientParentNode->originDimensionSpacePoint,
                    $this->serializeProjectedReferences(
                        $references
                    ),
                );

                $commands = $commands->append($setReferencesOfTetheredNodeCommand);
            }
        } else {
            /**
             * Case Node is a regular child
             */
            $transientNodeCopy = $transientParentNode->forRegularChildNode(
                $subtree
            );

            $createCopyOfNodeCommand = CreateNodeAggregateWithNode::create(
                $transientParentNode->workspaceName,
                $transientNodeCopy->aggregateId,
                $subtree->node->nodeTypeName,
                $transientParentNode->originDimensionSpacePoint,
                $transientParentNode->aggregateId,
                // todo succeedingSiblingNodeAggregateId
                // todo skip properties not in schema
                initialPropertyValues: PropertyValuesToWrite::fromArray(
                    iterator_to_array($subtree->node->properties)
                ),
                references: $this->serializeProjectedReferences(
                    $subgraph->findReferences($subtree->node->aggregateId, FindReferencesFilter::create())
                )
            );

            $createCopyOfNodeCommand = $createCopyOfNodeCommand->withTetheredDescendantNodeAggregateIds(
                $transientNodeCopy->tetheredNodeAggregateIds
            );

            $commands = $commands->append($createCopyOfNodeCommand);
        }

        /**
         * common logic
         */

        foreach ($subtree->node->tags->withoutInherited() as $explicitTag) {
            $commands = $commands->append(
                TagSubtree::create(
                    $transientNodeCopy->workspaceName,
                    $transientNodeCopy->aggregateId,
                    $transientNodeCopy->originDimensionSpacePoint->toDimensionSpacePoint(),
                    NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
                    $explicitTag
                )
            );
        }

        foreach ($subtree->children as $childSubtree) {
            $commands = $this->commandsForSubtreeRecursively($transientNodeCopy, $childSubtree, $subgraph, $commands);
        }

        return $commands;
    }

    private function serializeProjectedReferences(References $references): NodeReferencesToWrite
    {
        $serializedReferencesByName = [];
        foreach ($references as $reference) {
            if (!isset($serializedReferencesByName[$reference->name->value])) {
                $serializedReferencesByName[$reference->name->value] = [];
            }
            $serializedReferencesByName[$reference->name->value][] = NodeReferenceToWrite::fromTargetAndProperties($reference->node->aggregateId, $reference->properties?->count() > 0 ? PropertyValuesToWrite::fromArray(iterator_to_array($reference->properties)) : PropertyValuesToWrite::createEmpty());
        }

        $serializedReferences = [];
        foreach ($serializedReferencesByName as $name => $referenceObjects) {
            $serializedReferences[] = NodeReferencesForName::fromReferences(ReferenceName::fromString($name), $referenceObjects);
        }

        return NodeReferencesToWrite::fromArray($serializedReferences);
    }
}
