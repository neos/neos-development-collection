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

use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\Changes\ChangeProjector;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;

final class ChangeProjectorObjectFactory
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient,
        private readonly WorkspaceFinder $workspaceFinder
    )
    {
    }

    public function buildChangeProjector(): ChangeProjector
    {
        return new ChangeProjector(
            $this->dbalClient,
            $this->workspaceFinder,
        );
    }
}
