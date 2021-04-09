<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\ContentAccess;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * This is the "outside world" interface, i.e. the upper layers of Neos, the Neos UI, Fusion can always
 * rely on the fact that all these methods in here exist and are implemented.
 *
 * It is composed into single-method-interfaces, because for the internal IMPLEMENTATION, it is often enough
 * to only override certain functions selectively.
 */
interface NodeAccessorInterface extends Parts\FindChildNodesInterface
{

    /**
     * @param NodeInterface $parentNode
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return iterable<NodeInterface>
     */
    public function findChildNodes(NodeInterface $parentNode, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): iterable;
}
