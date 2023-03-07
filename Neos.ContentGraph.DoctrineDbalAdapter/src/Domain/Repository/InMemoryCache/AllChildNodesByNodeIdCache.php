<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\InMemoryCache;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraintsWithSubNodeTypes;

/**
 * This cache is only filled for a $parentNodeId if we have retrieved *all* childNodes, without any restriction.
 *
 * @internal
 */
final class AllChildNodesByNodeIdCache
{
    /**
     * @var array<string,array<string,array<int,Node>>>
     */
    protected array $childNodes = [];

    protected bool $isEnabled;

    public function __construct(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * @param array<int,Node> $allChildNodes
     */
    public function add(
        NodeAggregateId $parentNodeAggregateId,
        NodeTypeConstraintsWithSubNodeTypes $nodeTypeConstraintsWithSubNodeTypes,
        array $allChildNodes
    ): void {
        if ($this->isEnabled === false) {
            return;
        }

        $key = (string)$parentNodeAggregateId;
        $this->childNodes[$key][(string)$nodeTypeConstraintsWithSubNodeTypes] = $allChildNodes;
    }

    public function contains(
        NodeAggregateId $parentNodeAggregateId,
        NodeTypeConstraintsWithSubNodeTypes $nodeTypeConstraintsWithSubNodeTypes
    ): bool {
        if ($this->isEnabled === false) {
            return false;
        }

        $key = (string)$parentNodeAggregateId;
        return isset($this->childNodes[$key][(string)$nodeTypeConstraintsWithSubNodeTypes]);
    }

    /**
     * @return array<int,Node>
     */
    public function findChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        NodeTypeConstraintsWithSubNodeTypes $nodeTypeConstraintsWithSubNodeTypes,
    ): array {
        if ($this->isEnabled === false) {
            return [];
        }

        $key = (string)$parentNodeAggregateId;
        $result = [];

        if (isset($this->childNodes[$key][(string)$nodeTypeConstraintsWithSubNodeTypes])) {
            return $this->childNodes[$key][(string)$nodeTypeConstraintsWithSubNodeTypes];
        }
        // TODO: we could add more clever matching logic here
        if (isset($this->childNodes[$key]['*'])) {
            $childNodes = $this->childNodes[$key]['*'];
            foreach ($childNodes as $childNode) {
                /* @var  Node $childNode */
                if (
                    $nodeTypeConstraintsWithSubNodeTypes->matches($childNode->nodeTypeName)
                ) {
                    $result[] = $childNode;
                }
            }
        }
        return $result;
    }
}
