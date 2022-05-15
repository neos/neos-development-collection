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

use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class NodeAddressFactoryObjectFactory
{

    public function __construct(
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly ContentGraphInterface $contentGraph
    )
    {
    }

    public function buildNodeAddressFactory(): NodeAddressFactory
    {
        return new NodeAddressFactory(
            $this->workspaceFinder,
            $this->contentGraph
        );
    }
}
