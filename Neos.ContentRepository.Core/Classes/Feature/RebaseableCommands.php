<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
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
            if ($eventEnvelope->event->metadata && isset($eventEnvelope->event->metadata?->value['commandClass'])) {
                $commands[] = RebaseableCommand::extractFromEventEnvelope($eventEnvelope);
            }
        }

        return new RebaseableCommands(...$commands);
    }

    /**
     * @return array{RebaseableCommands,RebaseableCommands}
     */
    public function separateMatchingAndRemainingCommands(
        NodeIdsToPublishOrDiscard $nodeIdsToPublishOrDiscard
    ): array {
        $matchingCommands = [];
        $remainingCommands = [];
        foreach ($this->items as $extractedCommand) {
            $originalCommand = $extractedCommand->originalCommand;
            if (!$originalCommand instanceof MatchableWithNodeIdToPublishOrDiscardInterface) {
                throw new \Exception(
                    'Command class ' . get_class($originalCommand) . ' does not implement '
                    . MatchableWithNodeIdToPublishOrDiscardInterface::class,
                    1645393655
                );
            }
            if (self::commandMatchesAtLeastOneNode($originalCommand, $nodeIdsToPublishOrDiscard)) {
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
        MatchableWithNodeIdToPublishOrDiscardInterface $command,
        NodeIdsToPublishOrDiscard $nodeIds,
    ): bool {
        foreach ($nodeIds as $nodeId) {
            // todo rather use the domain event here and use `EmbedsNodeAggregateId` already!
            if ($command->matchesNodeId($nodeId)) {
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
