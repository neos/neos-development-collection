<?php

namespace Neos\EventSourcedContentRepository\Domain\Projection\Content\InMemoryCache;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;


/**
 * This cache is only filled for a $parentNodeIdentifier if we have retrieved *all* childNodes, without any restriction.
 */
final class AllChildNodesByNodeIdentifierCache
{
    protected $childNodes = [];

    public function add(NodeIdentifier $parentNodeIdentifier, array $allChildNodes)
    {
        $key = (string)$parentNodeIdentifier;
        $this->childNodes[$key] = $allChildNodes;
    }

    public function contains(NodeIdentifier $parentNodeIdentifier)
    {
        $key = (string)$parentNodeIdentifier;
        return isset($this->childNodes[$key]);
    }

    public function findChildNodes(NodeIdentifier $parentNodeIdentifier, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array
    {
        $key = (string)$parentNodeIdentifier;
        $result = [];

        if (isset($this->childNodes[$key])) {
            $childNodes = $this->childNodes[$key];
            foreach ($childNodes as $childNode) {
                /* @var \Neos\EventSourcedContentRepository\Domain\Model\NodeInterface $childNode */
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
