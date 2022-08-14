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

namespace Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester;


use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntries;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntry;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntryType;

/**
 * For full docs and context, see {@see RaceTrackerCatchUpHook}
 *
 * @internal
 */
final class RedisInterleavingLogger
{
    private static \Redis $redis;

    public static function connect(string $host, int $port)
    {
        self::$redis = new \Redis();
        self::$redis->connect($host, $port);
    }

    public static function reset()
    {
        self::$redis->del('tracePids', 'trace');
    }

    public static function trace(TraceEntryType $type, array $payload = [])
    {
        $pid = getmypid();
        self::$redis->sAdd('tracePids', $pid);
        self::$redis->xAdd('trace', '*', [
            ...$payload,
            'pid' => $pid,
            'type' => $type->value
        ]);
    }

    /**
     * @return TraceEntries
     * @throws
     */
    public static function getTraces(): TraceEntries
    {
        $traces = [];
        foreach (self::$redis->xRange('trace', '-', '+') as $k => $v) {
            $pid = $v['pid'];
            $type = TraceEntryType::from($v['type']);
            unset($v['pid']);
            unset($v['type']);
            $traces[] = new TraceEntry(
                $k,
                $pid,
                $type,
                $v
            );
        }
        return new TraceEntries($traces);
    }
}
