<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
final class NodeTypeConstraintObjectFactory
{

    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager
    )
    {
    }

    public function buildNodeTypeConstraintFactory(): NodeTypeConstraintFactory
    {
        return new NodeTypeConstraintFactory(
            $this->nodeTypeManager
        );
    }
}
