<?php
namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Implementation of the NodeSearchServiceInterface for greater backwards compatibility
 *
 * Note: This implementation is meant to ease the transition to an event sourced content repository
 * but since it uses legacy classes (like \Neos\ContentRepository\Domain\Service\Context) it is
 * advised to use NodeAccessor::findDescendants() directly instead.
 *
 * @Flow\Scope("singleton")
 * @deprecated see above
 */
class NodeSearchService implements NodeSearchServiceInterface
{
    /**
     * @param array<int,string> $searchNodeTypes
     */
    public function findByProperties(
        string $term,
        array $searchNodeTypes,
        ?NodeInterface $startingPoint = null
    ): never {
        throw new \InvalidArgumentException('Cannot find nodes with the current set of arguments', 1651923867);
    }
}
