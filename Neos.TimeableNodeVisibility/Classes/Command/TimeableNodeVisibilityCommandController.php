<?php

namespace Neos\TimeableNodeVisibility\Command;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\TimeableNodeVisibility\Domain\HandlingResultType;
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
            $this->output->outputLine(sprintf('Enabled %d nodes with exceeded timed dates.', $handlingResult->countByResult(HandlingResultType::ENABLED)));
            foreach ($handlingResult->getByResult(HandlingResultType::ENABLED) as $result) {
                $this->output->outputLine(sprintf(
                        '- NodeAggregateId: %s, DimensionSpacePoint: %s, Label: %s',
                        $result->node->nodeAggregateId->value,
                        join(',', $result->node->originDimensionSpacePoint->coordinates),
                        $result->node->getLabel()
                    )
                );
            }

            $this->output->outputLine(sprintf('Disabled %d nodes with exceeded timed dates.', $handlingResult->countByResult(HandlingResultType::DISABLED)));
            foreach ($handlingResult->getByResult(HandlingResultType::DISABLED) as $result) {
                $this->output->outputLine(sprintf(
                        '- NodeAggregateId: %s, DimensionSpacePoint: %s, Label: %s',
                        $result->node->nodeAggregateId->value,
                        join(',', $result->node->originDimensionSpacePoint->coordinates),
                        $result->node->getLabel()
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
