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

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;

/**
 * This interface must be implemented by all commands which are working with individual nodes, such that they are
 * filterable whether they are applying their action to a NodeIdToPublish.
 *
 * This is needed to publish and discard individual nodes.
 *
 * @internal because only relevant for commands
 */
interface MatchableWithNodeIdToPublishOrDiscardInterface
{
    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool;
}
