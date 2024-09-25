<?php

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\PendingChangesProjection;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\Neos\FrontendRouting\NodeAddressFactory;

/**
 * Read model for a set of pending changes
 *
 * @internal !!! Still a bit unstable - might change in the future.
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<Change>
 */
final readonly class Changes implements \IteratorAggregate, \Countable
{
    private const NODE_HAS_BEEN_CREATED = 0b0001;
    private const NODE_HAS_BEEN_CHANGED = 0b0010;
    private const NODE_HAS_BEEN_MOVED = 0b0100;
    private const NODE_HAS_BEEN_DELETED = 0b1000;


    /**
     * @param list<Change> $changes
     */
    private function __construct(
        private array $changes
    ) {
    }

    public static function fromArray(array $changes): self
    {
        foreach ($changes as $change) {
            if (!$change instanceof Change) {
                throw new \InvalidArgumentException(sprintf('Changes can only consist of %s instances, given: %s', Change::class, get_debug_type($change)), 1727273148);
            }
        }
        return new self($changes);
    }

    /**
     * @deprecated
     * @return list<array{contextPath:string,documentContextPath:string,typeOfChange:int}>
     */
    public function toPublishableNodeInfo(ContentRepository $contentRepository, WorkspaceName $workspaceName): array
    {
        /** @var array{contextPath:string,documentContextPath:string,typeOfChange:int}[] $unpublishedNodes */
        $unpublishedNodes = [];
        foreach ($this->changes as $change) {
            if ($change->removalAttachmentPoint) {
                $nodeAddress = new NodeAddress(
                    $change->contentStreamId,
                    $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                    $change->nodeAggregateId,
                    $workspaceName
                );

                /**
                 * See {@see Remove::apply} -> Removal Attachment Point == closest document node.
                 */
                $documentNodeAddress = new NodeAddress(
                    $change->contentStreamId,
                    $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                    $change->removalAttachmentPoint,
                    $workspaceName
                );

                $unpublishedNodes[] = [
                    'contextPath' => $nodeAddress->serializeForUri(),
                    'documentContextPath' => $documentNodeAddress->serializeForUri(),
                    'typeOfChange' => self::getTypeOfChange($change)
                ];
            } else {
                $subgraph = $contentRepository->getContentGraph($workspaceName)->getSubgraph(
                    $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                    VisibilityConstraints::withoutRestrictions()
                );
                $node = $subgraph->findNodeById($change->nodeAggregateId);

                if ($node instanceof Node) {
                    $documentNode = $subgraph->findClosestNode($node->aggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT));
                    if ($documentNode instanceof Node) {
                        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
                        $unpublishedNodes[] = [
                            'contextPath' => $nodeAddressFactory->createFromNode($node)->serializeForUri(),
                            'documentContextPath' => $nodeAddressFactory->createFromNode($documentNode)
                                ->serializeForUri(),
                            'typeOfChange' => self::getTypeOfChange($change)
                        ];
                    }
                }
            }
        }
        return array_values(array_filter($unpublishedNodes, function ($item) {
            return (bool)$item;
        }));
    }

    private static function getTypeOfChange(Change $change): int
    {
        $result = 0;
        if ($change->created) {
            $result |= self::NODE_HAS_BEEN_CREATED;
        }
        if ($change->changed) {
            $result |= self::NODE_HAS_BEEN_CHANGED;
        }
        if ($change->moved) {
            $result |= self::NODE_HAS_BEEN_MOVED;
        }
        if ($change->deleted) {
            $result |= self::NODE_HAS_BEEN_DELETED;
        }
        return $result;
    }

    /**
     * @return \Traversable<Change>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->changes;
    }

    public function count(): int
    {
        return count($this->changes);
    }
}
