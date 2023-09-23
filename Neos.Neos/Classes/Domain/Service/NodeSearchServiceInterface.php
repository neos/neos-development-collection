<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;

/**
 * Interface for the node search service for finding nodes (based on a fulltext search)
 */
interface NodeSearchServiceInterface
{
    public function findNodes(
        Node|AbsoluteNodePath $entry,
        SearchTerm $searchTerm,
        NodeTypeConstraints $nodeTypeConstraints
    ): Nodes;
}
