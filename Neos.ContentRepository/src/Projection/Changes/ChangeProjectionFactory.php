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

namespace Neos\ContentRepository\Projection\Change;

use Neos\ContentRepository\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Projection\Changes\ChangeProjection;
use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Projection\Workspace\WorkspaceProjection;

// TODO: MOVE TO NEOS
class ChangeProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient
    ) {
    }

    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies, array $options, CatchUpHookFactoryInterface $catchUpHookFactory, Projections $projectionsSoFar): ProjectionInterface
    {
        $workspaceFinder = $projectionsSoFar->get(WorkspaceProjection::class)->getState();
        assert($workspaceFinder instanceof WorkspaceFinder);

        return new ChangeProjection(
            $projectionFactoryDependencies->eventNormalizer,
            $this->dbalClient,
            $workspaceFinder,
            sprintf('neos_cr_%s_projection_%s', $projectionFactoryDependencies->contentRepositoryIdentifier, strtolower(str_replace('Projection', '', (new \ReflectionClass(ChangeProjection::class))->getShortName()))),
        );
    }
}
