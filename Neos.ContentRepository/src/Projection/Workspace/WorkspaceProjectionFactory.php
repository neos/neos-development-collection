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

namespace Neos\ContentRepository\Projection\Workspace;

use Neos\ContentRepository\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\CatchUpHandlerFactoryInterface;
use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\Projections;

class WorkspaceProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient
    ) {
    }

    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies, array $options, CatchUpHandlerFactoryInterface $catchUpHandlerFactory, Projections $projectionsSoFar): ProjectionInterface
    {
        new WorkspaceProjection(
            $projectionFactoryDependencies->eventNormalizer,
            $this->dbalClient,
            sprintf('neos_cr_%s_projection_%s', $projectionFactoryDependencies->contentRepositoryIdentifier, strtolower(str_replace('Projection', '', (new \ReflectionClass(WorkspaceProjection::class))->getShortName()))),
        );
    }
}
