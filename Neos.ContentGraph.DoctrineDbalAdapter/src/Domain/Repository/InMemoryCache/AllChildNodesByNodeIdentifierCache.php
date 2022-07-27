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

use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;

/**
 * This cache is only filled for a $parentNodeIdentifier if we have retrieved *all* childNodes, without any restriction.
 *
 * @internal
 */
final class AllChildNodesByNodeIdentifierCache
{
    /**
     * @var array<string,array<string,array<int,NodeInterface>>>
     */
    protected array $childNodes = [];

    protected bool $isEnabled;

    public function __construct(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * @param array<int,NodeInterface> $allChildNodes
     */
    public function add(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?NodeTypeConstraints $nodeTypeConstraints,
        array $allChildNodes
    ): void {
        if ($this->isEnabled === false) {
            return;
        }

        $key = (string)$parentNodeAggregateIdentifier;
        $nodeTypeConstraintsSerialized = $nodeTypeConstraints
            ? $nodeTypeConstraints->asLegacyNodeTypeFilterString()
            : '*';
        $this->childNodes[$key][$nodeTypeConstraintsSerialized] = $allChildNodes;
    }

    public function contains(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?NodeTypeConstraints $nodeTypeConstraints
    ): bool {
        if ($this->isEnabled === false) {
            return false;
        }

        $key = (string)$parentNodeAggregateIdentifier;
        $nodeTypeConstraintsSerialized = $nodeTypeConstraints?->asLegacyNodeTypeFilterString() ?: '*';
        return isset($this->childNodes[$key][$nodeTypeConstraintsSerialized]) || isset($this->childNodes[$key]['*']);
    }

    /**
     * @return array<int,NodeInterface>
     */
    public function findChildNodes(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        if ($this->isEnabled === false) {
            return [];
        }

        $key = (string)$parentNodeAggregateIdentifier;
        $nodeTypeConstraintsSerialized = $nodeTypeConstraints?->asLegacyNodeTypeFilterString() ?: '*';
        $result = [];

        if (isset($this->childNodes[$key][$nodeTypeConstraintsSerialized])) {
            return $this->childNodes[$key][$nodeTypeConstraintsSerialized];
        }
        // TODO: we could add more clever matching logic here
        if (isset($this->childNodes[$key]['*'])) {
            $childNodes = $this->childNodes[$key]['*'];
            foreach ($childNodes as $childNode) {
                /* @var  NodeInterface $childNode */
                if ($nodeTypeConstraints === null || $nodeTypeConstraints->matches($childNode->getNodeTypeName())) {
                    $result[] = $childNode;
                }
            }

            if ($limit || $offset) {
                $result = array_slice($result, $offset ?? 0, $limit);
            }
        }
        return $result;
    }
}
