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

namespace Neos\Neos\PendingChangesProjection;

use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\Neos\PendingChangesProjection\ChangeProjection;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceProjection;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathProjection;

/**
 * @implements ProjectionFactoryInterface<ChangeProjection>
 */
class ChangeProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient
    ) {
    }

    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
        CatchUpHookFactoryInterface $catchUpHookFactory,
        Projections $projectionsSoFar
    ): ChangeProjection {
        $workspaceFinder = $projectionsSoFar->get(WorkspaceProjection::class)->getState();
        assert($workspaceFinder instanceof WorkspaceFinder);
        $projectionShortName = strtolower(str_replace(
            'Projection',
            '',
            (new \ReflectionClass(ChangeProjection::class))->getShortName()
        ));
        return new ChangeProjection(
            $projectionFactoryDependencies->eventNormalizer,
            $this->dbalClient,
            $workspaceFinder,
            sprintf(
                'cr_%s_p_neos_%s',
                $projectionFactoryDependencies->contentRepositoryId,
                $projectionShortName
            ),
        );
    }
}
