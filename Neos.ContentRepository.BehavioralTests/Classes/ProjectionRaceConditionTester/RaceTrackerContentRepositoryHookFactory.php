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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryHookFactoryInterface;

/**
 * For full docs and context, see {@see RaceTrackerContentRepositoryHook}
 *
 * @internal
 */
final class RaceTrackerContentRepositoryHookFactory implements ContentRepositoryHookFactoryInterface
{
    public function build(ContentRepository $contentRepository, array $options): RaceTrackerContentRepositoryHook
    {
        return new RaceTrackerContentRepositoryHook();
    }
}
