<?php

namespace Neos\ContentRepository\Domain\Service\Dto;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use ArrayIterator;
use Exception;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\NodePublishIntegrityCheckService;
use Neos\Flow\Annotations as Flow;
use Traversable;

/**
 * Node List to publish - internal implementation detail of {@see NodePublishIntegrityCheckService}
 *
 * @internal
 */
final class NodePublishingIntegrityNodeListToPublish implements \IteratorAggregate
{
    /**
     * @var NodeInterface[]
     */
    private array $nodesToPublish;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    private $nodeDataRepository;

    /**
     * @param NodeInterface[] $nodesToPublish
     */
    private function __construct(array $nodesToPublish)
    {
        $this->nodesToPublish = $nodesToPublish;
    }

    /**
     * @param NodeInterface[] $nodesToPublish
     * @return static
     */
    public static function createForNodes(array $nodesToPublish): self
    {
        return new self($nodesToPublish);
    }

    public function getIterator(): iterable
    {
        return new ArrayIterator($this->nodesToPublish);
    }

    /**
     * @param string $sourcePath
     * @return bool TRUE if a node was moved from $path in this batch, FALSE otherwise.
     */
    public function isMovedFrom(string $sourcePath): bool
    {
        foreach ($this->nodesToPublish as $node) {
            if ($node->getNodeData()->getMovedTo() !== null) {
                // shadow node -> that shadow node contains the source path where the move started
                //
                // NOTE: this case will usually not happen (as far as we understand right now), as the UI
                // will only send us the TARGET node of a move, and not the associated shadow node.
                if ($node->getPath() === $sourcePath) {
                    return true;
                }
            } else {
                // NO shadow node -> $node contains the TARGET path where the move has ended.
                //
                // Thus, to find out whether a move has happened from $sourcePath, we need to check whether any other
                // node exists with movedTo == $node, to determine the source path
                $referencedShadowNodeData = $this->nodeDataRepository->findOneByMovedTo($node->getNodeData());
                if ($referencedShadowNodeData) {
                    // TODO: Dimensions??
                    // we now that $node was moved from $referencedShadowNodeData->getPath() to $node->getPath();
                    if ($referencedShadowNodeData->getPath() === $sourcePath) {
                        // YES, a node was moved from $path to a new location. The new location now
                        // is $node->getPath().
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param string $path
     * @return bool TRUE if node exists in this batch and is removed. FALSE otherwise.
     */
    public function isRemoved(string $path): bool
    {
        foreach ($this->nodesToPublish as $node) {
            // TODO: Dimensions??
            if ($node->getPath() === $path) {
                return $node->isRemoved();
            }
        }

        // we did not find the node, so it was not removed either.
        return false;
    }

    /**
     * @param string $path
     * @return bool TRUE if node exists, is non-shadow, non-deleted
     */
    public function isExistingNode(string $path): bool
    {
        foreach ($this->nodesToPublish as $node) {
            // TODO: Dimensions??
            if ($node->getPath() === $path) {
                // we do not need to check for movedTo; because: if movedTo is set, removed is also set.
                return !$node->isRemoved();
            }
        }

        // we did not find the node; so we return false
        return false;
    }


}
