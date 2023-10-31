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

namespace Neos\ContentRepository\BehavioralTests\Command;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\CatchUpTriggerWithSynchronousOption;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\Fusion\Cache\GraphProjectorCatchUpHookForCacheFlushing;

final class PerformanceMeasurementCommandController extends CommandController
{
    private PerformanceMeasurementService $performanceMeasurementService;

    public function __construct(
        ContentRepositoryRegistry $contentRepositoryRegistry,
        PerformanceMeasurementServiceFactory $performanceMeasurementServiceFactory
    ) {
        $this->performanceMeasurementService = $contentRepositoryRegistry->buildService(
            ContentRepositoryId::fromString('default'),
            $performanceMeasurementServiceFactory
        );

        parent::__construct();
    }

    /**
     * Prepare the performance test by removing existing data and creating nodes for the test.
     *
     * @param int $nodesPerLevel The number of nodes to create per level.
     * @param int $levels The number of levels in the node tree.
     * @internal
     */
    public function preparePerformanceTestCommand(int $nodesPerLevel, int $levels): void
    {
        $this->performanceMeasurementService->removeEverything();
        $this->outputLine("All removed. Starting to fill.");
        CatchUpTriggerWithSynchronousOption::synchronously(
            fn() => GraphProjectorCatchUpHookForCacheFlushing::disabled(
                fn() => $this->performanceMeasurementService->createNodesForPerformanceTest($nodesPerLevel, $levels)
            )
        );
    }

    /**
     * Test the performance of forking a content stream and measure the time taken.
     *
     * @internal
     */
    public function testPerformanceCommand(): void
    {
        $time = microtime(true);
        CatchUpTriggerWithSynchronousOption::synchronously(
            fn() => $this->performanceMeasurementService->forkContentStream()
        );

        $timeElapsed = microtime(true) - $time;
        $this->outputLine('Time: ' . $timeElapsed);
    }
}
