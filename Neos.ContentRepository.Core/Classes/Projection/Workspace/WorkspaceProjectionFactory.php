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

namespace Neos\ContentRepository\Core\Projection\Workspace;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;

/**
 * @implements ProjectionFactoryInterface<WorkspaceProjection>
 * @internal
 */
class WorkspaceProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal,
    ) {
    }

    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
    ): WorkspaceProjection {
        $projectionShortName = strtolower(str_replace(
            'Projection',
            '',
            (new \ReflectionClass(WorkspaceProjection::class))->getShortName()
        ));
        return new WorkspaceProjection(
            $this->dbal,
            sprintf(
                'cr_%s_p_%s',
                $projectionFactoryDependencies->contentRepositoryId->value,
                $projectionShortName
            ),
        );
    }
}
