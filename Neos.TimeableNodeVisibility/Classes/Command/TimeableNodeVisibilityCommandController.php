<?php

declare(strict_types=1);

namespace Neos\TimeableNodeVisibility\Command;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\TimeableNodeVisibility\Domain\ChangedVisibilityType;
use Neos\TimeableNodeVisibility\Service\TimeableNodeVisibilityService;

class TimeableNodeVisibilityCommandController extends CommandController
{
    #[Flow\Inject]
    protected TimeableNodeVisibilityService $timeableNodeVisibilityService;

    public function executeCommand(string $contentRepository = 'default', bool $quiet = false): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $workspaceName = WorkspaceName::fromString('live');

        $handlingResult = $this->timeableNodeVisibilityService->handleExceededNodeDates(
            $contentRepositoryId,
            $workspaceName
        );

        if (!$quiet) {
            $this->output->outputLine(sprintf('Enabled %d nodes with exceeded timed dates.', $handlingResult->countByType(ChangedVisibilityType::NODE_WAS_ENABLED)));
            foreach ($handlingResult->getByType(ChangedVisibilityType::NODE_WAS_ENABLED) as $result) {
                $this->output->outputLine(sprintf(
                        '- NodeAggregateId: %s, DimensionSpacePoint: %s',
                        $result->node->aggregateId->value,
                        join(',', $result->node->originDimensionSpacePoint->coordinates)
                    )
                );
            }

            $this->output->outputLine(sprintf('Disabled %d nodes with exceeded timed dates.', $handlingResult->countByType(ChangedVisibilityType::NODE_WAS_DISABLED)));
            foreach ($handlingResult->getByType(ChangedVisibilityType::NODE_WAS_DISABLED) as $result) {
                $this->output->outputLine(sprintf(
                        '- NodeAggregateId: %s, DimensionSpacePoint: %s',
                        $result->node->aggregateId->value,
                        join(',', $result->node->originDimensionSpacePoint->coordinates)
                    )
                );
            }
        }
    }

    /**
     * Run the execute command as daemon.
     *
     * @param string $contentRepository The content repository identifier. (Default: 'default')
     * @param int $ttl The time to live for the daemon in seconds. Set to '0' for infinite. (Default: '900')
     * @param int $interval Interval in seconds, when the command has to get executed. (Default: '60')
     * @param bool $quiet Set to false if you need a more verbose output. (Default: 'true')
     */
    public function runDaemonCommand(string $contentRepository = 'default', int $ttl = 900, int $interval = 60, bool $quiet = true): void
    {
        $startTime = microtime(true);
        while (true) {
            $this->executeCommand($contentRepository, $quiet);

            $currentTime = microtime(true) - $startTime;
            if ($ttl !== 0 && $currentTime + $interval >= $ttl) {
                break;
            }

            if (!$quiet) {
                $this->outputLine(sprintf('Wait for %d seconds before next run (%d/%d).', $interval, $currentTime, $ttl));
            }
            sleep($interval);
        }

        if (!$quiet) {
            $this->outputLine(sprintf('Finished after %f seconds.', $currentTime));
        }
    }
}
