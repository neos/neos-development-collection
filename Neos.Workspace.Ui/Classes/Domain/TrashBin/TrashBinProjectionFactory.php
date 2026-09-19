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

namespace Neos\Workspace\Ui\Domain\TrashBin;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\SubscriberFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;

/**
 * @internal for communication within the Workspace UI only
 * @implements ProjectionFactoryInterface<TrashBinProjection>
 */
class TrashBinProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal,
    ) {
    }

    public function build(
        SubscriberFactoryDependencies $projectionFactoryDependencies,
        array $options,
    ): TrashBinProjection {
        return new TrashBinProjection(
            $this->dbal,
            sprintf(
                'cr_%s_p_neos_trashbin',
                $projectionFactoryDependencies->contentRepositoryId->value,
            ),
        );
    }
}
