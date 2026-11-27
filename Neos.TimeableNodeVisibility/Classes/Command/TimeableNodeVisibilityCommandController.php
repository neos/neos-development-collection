<?php

declare(strict_types=1);

namespace Neos\TimeableNodeVisibility\Command;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\TimeableNodeVisibility\Domain\ChangedVisibilities;
use Neos\TimeableNodeVisibility\Domain\ChangedVisibility;
use Neos\TimeableNodeVisibility\Domain\ChangedVisibilityType;
use Neos\TimeableNodeVisibility\Service\TimeableNodeVisibilityService;

class TimeableNodeVisibilityCommandController extends CommandController
{
    #[Flow\Inject]
    protected TimeableNodeVisibilityService $timeableNodeVisibilityService;

    public function executeCommand(string $contentRepository = 'default', bool $quiet = false): void
    {
        $changedVisibilities = $this->handleExceededNodeDates($contentRepository, $quiet);

        if ($changedVisibilities->hasErrors()) {
            $this->quit(1);
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
            $this->handleExceededNodeDates($contentRepository, $quiet);

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

    private function handleExceededNodeDates(string $contentRepository, bool $quiet): ChangedVisibilities
    {
        $changedVisibilities = $this->timeableNodeVisibilityService->handleExceededNodeDates(
            ContentRepositoryId::fromString($contentRepository),
            WorkspaceName::forLive()
        );

        if (!$quiet) {
            $this->outputLine(sprintf('Enabled %d nodes with exceeded timed dates.', $changedVisibilities->countByType(ChangedVisibilityType::NODE_WAS_ENABLED)));
            foreach ($changedVisibilities->getByType(ChangedVisibilityType::NODE_WAS_ENABLED) as $changedVisibility) {
                $this->outputLine('- ' . $this->renderNodeIdentity($changedVisibility));
            }

            $this->outputLine(sprintf('Disabled %d nodes with exceeded timed dates.', $changedVisibilities->countByType(ChangedVisibilityType::NODE_WAS_DISABLED)));
            foreach ($changedVisibilities->getByType(ChangedVisibilityType::NODE_WAS_DISABLED) as $changedVisibility) {
                $this->outputLine('- ' . $this->renderNodeIdentity($changedVisibility));
            }
        }

        if ($changedVisibilities->hasErrors()) {
            $this->outputLine(sprintf('<error>Failed to change visibility of %d nodes with exceeded timed dates.</error>', $changedVisibilities->countByType(ChangedVisibilityType::ERROR)));
            foreach ($changedVisibilities->getByType(ChangedVisibilityType::ERROR) as $changedVisibility) {
                $this->outputLine(sprintf(
                    '<error>- %s: %s</error>',
                    $this->renderNodeIdentity($changedVisibility),
                    $changedVisibility->message
                ));
            }
        }

        return $changedVisibilities;
    }

    private function renderNodeIdentity(ChangedVisibility $changedVisibility): string
    {
        return sprintf(
            'NodeAggregateId: %s, DimensionSpacePoint: %s',
            $changedVisibility->node->aggregateId->value,
            implode(',', $changedVisibility->node->originDimensionSpacePoint->coordinates)
        );
    }
}
