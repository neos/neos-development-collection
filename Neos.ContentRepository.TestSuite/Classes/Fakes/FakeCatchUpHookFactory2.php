<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Fakes;

use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;

/**
 * @implements CatchUpHookFactoryInterface<ProjectionStateInterface>
 * @internal helper to configure custom catchup hook mocks for testing
 */
final class FakeCatchUpHookFactory2 implements CatchUpHookFactoryInterface
{
    /**
     * @var array<string, CatchUpHookInterface>
     */
    private static array $catchupHooks;

    public function build(CatchUpHookFactoryDependencies $dependencies): CatchUpHookInterface
    {
        return static::$catchupHooks[spl_object_hash($dependencies->projectionState)] ?? throw new \RuntimeException('No catchup hook defined for Fake.');
    }

    public static function setCatchupHook(ProjectionStateInterface $projectionState, CatchUpHookInterface $catchUpHook): void
    {
        self::$catchupHooks[spl_object_hash($projectionState)] = $catchUpHook;
    }
}
