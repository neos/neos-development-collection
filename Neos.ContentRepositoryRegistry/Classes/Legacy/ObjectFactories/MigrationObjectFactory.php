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

use Neos\ContentRepository\Feature\Migration\Filter\FilterFactory;
use Neos\ContentRepository\Feature\Migration\MigrationCommandHandler;
use Neos\ContentRepository\Feature\Migration\Transformation\TransformationFactory;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
final class MigrationObjectFactory
{
    public function __construct(
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly WorkspaceCommandHandler $workspaceCommandHandler,
        private readonly ContentGraphInterface $contentGraph,
        private readonly ObjectManagerInterface $objectManager
    )
    {
    }

    public function buildMigrationCommandHandler(): MigrationCommandHandler
    {
        return new MigrationCommandHandler(
            $this->workspaceFinder,
            $this->workspaceCommandHandler,
            $this->contentGraph,
            $this->buildFilterFactory(),
            $this->buildTransformationFactory()
        );
    }

    private function buildFilterFactory(): FilterFactory
    {
        return new FilterFactory($this->objectManager);
    }

    private function buildTransformationFactory(): TransformationFactory
    {
        return new TransformationFactory($this->objectManager);
    }

}
