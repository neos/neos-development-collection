<?php

/*
 * This file is part of the Neos.ContentRepository.BehavioralTests package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Projection\CatchUpHookInterface;

/**
 * For full docs and context, see {@see RaceTrackerCatchUpHook}
 *
 * @internal
 */
final class RaceTrackerCatchUpHookFactory implements CatchUpHookFactoryInterface
{
    public function build(ContentRepository $contentRepository): CatchUpHookInterface
    {
        return new RaceTrackerCatchUpHook();
    }
}
