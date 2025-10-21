<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\EventStore\Model\EventStream\EventStreamInterface;

/**
 * @internal
 * @implements \IteratorAggregate<RebaseableCommand>
 */
class RebaseableCommands implements \IteratorAggregate
{
    /**
     * @var array<RebaseableCommand>
     */
    private array $items;

    public function __construct(
        RebaseableCommand ...$items
    ) {
        $this->items = $items;
    }

    public static function extractFromEventStream(EventStreamInterface $eventStream): self
    {
        $commands = [];
        foreach ($eventStream as $eventEnvelope) {
            if (isset($eventEnvelope->event->metadata?->value['commandClass'])) {
                if ($eventEnvelope->event->metadata->value['commandClass'] === 'Neos\\ContentRepository\\Core\\Feature\\NodeDuplication\\Command\\CopyNodesRecursively') {
                    // The original draft of the CopyNodesRecursively command was removed with Beta 16. In case events that are created via that command are attempted to be
                    // normally rebased, published, or discarded we instead silently drop these events as announced.
                    continue;
                }
                $commands[] = RebaseableCommand::extractFromEventEnvelope($eventEnvelope);
            }
        }

        return new RebaseableCommands(...$commands);
    }

    /**
     * @return array{RebaseableCommands,RebaseableCommands}
     */
    public function separateMatchingAndRemainingCommands(
        NodeAggregateIds $nodeIdsToMatch
    ): array {
        $matchingCommands = [];
        $remainingCommands = [];
        foreach ($this->items as $extractedCommand) {
            if (self::commandMatchesAtLeastOneNode($extractedCommand->originalCommand, $nodeIdsToMatch)) {
                $matchingCommands[] = $extractedCommand;
            } else {
                $remainingCommands[] = $extractedCommand;
            }
        }
        return [
            new RebaseableCommands(...$matchingCommands),
            new RebaseableCommands(...$remainingCommands)
        ];
    }

    private static function commandMatchesAtLeastOneNode(
        RebasableToOtherWorkspaceInterface $command,
        NodeAggregateIds $nodeAggregateIdsToMatch,
    ): bool {
        foreach ($nodeAggregateIdsToMatch as $nodeId) {
            /**
             * This match must contain all commands which are working with individual nodes, such that they are
             * filterable whether they are applying their action to a $nodeAggregateId in question
             *
             * Used to separate commands for publish and discard individual nodes
             *
             * NOTE: We could refactor and simplify this by asking the events {@see EmbedsNodeAggregateId}
             * instead which would be more clean. But that only makes sense if we start rebasing events.
             */
            $matches = match ($command::class) {
                CreateRootNodeAggregateWithNode::class,
                CreateNodeAggregateWithNodeAndSerializedProperties::class,
                SetSerializedNodeProperties::class,
                MoveNodeAggregate::class,
                RemoveNodeAggregate::class,
                ChangeNodeAggregateName::class,
                ChangeNodeAggregateType::class,
                CreateNodeVariant::class,
                TagSubtree::class,
                UntagSubtree::class,
                UpdateRootNodeAggregateDimensions::class,
                    => $command->nodeAggregateId->equals($nodeId),
                SetSerializedNodeReferences::class => $command->sourceNodeAggregateId->equals($nodeId),
                // for non node-aggregate-changes we return false, so they are kept as remainder:
                AddDimensionShineThrough::class,
                MoveDimensionSpacePoint::class => false,
                default => throw new \RuntimeException(sprintf('Command %s does not have matching strategy for node aggregate id (%s). Partial workspace rebase not possible.', $nodeId->value, $command::class), 1645393655)
            };
            if ($matches) {
                return true;
            }
        }

        return false;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
