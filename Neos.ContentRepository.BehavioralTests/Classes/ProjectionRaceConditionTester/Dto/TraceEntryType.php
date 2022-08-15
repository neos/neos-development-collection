<?php /** @noinspection PhpComposerExtensionStubsInspection */

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

namespace Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto;


/**
 * For full docs and context, see {@see RaceTrackerCatchUpHook}
 *
 * @internal
 */
enum TraceEntryType: string
{
    case InCriticalSection = 'InCriticalSection';
    case LockWillBeReleasedIfItWasAcquiredBefore = 'LockWillBeReleased';
    case DebugLog = 'DebugLog';
}
