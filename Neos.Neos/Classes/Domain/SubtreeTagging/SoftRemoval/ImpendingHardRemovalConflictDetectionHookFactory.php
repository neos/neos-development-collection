<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\SubtreeTagging\SoftRemoval;

use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;

/**
 * @implements CatchUpHookFactoryInterface<ContentGraphReadModelInterface>
 * @internal
 */
final readonly class ImpendingHardRemovalConflictDetectionHookFactory implements CatchUpHookFactoryInterface
{
    public function __construct(
        private ImpendingHardRemovalConflictRepository $impendingHardRemovalConflictRepository
    ) {
    }

    public function build(CatchUpHookFactoryDependencies $dependencies): CatchUpHookInterface
    {
        return new ImpendingHardRemovalConflictDetectionHook(
            $dependencies->contentRepositoryId,
            $dependencies->projectionState,
            $this->impendingHardRemovalConflictRepository
        );
    }
}
