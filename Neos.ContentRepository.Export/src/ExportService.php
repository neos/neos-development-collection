<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Export;

use League\Flysystem\Filesystem;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Export\Processors\AssetExportProcessor;
use Neos\ContentRepository\Export\Processors\EventExportProcessor;
use Neos\EventStore\EventStoreInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\AssetUsageService;

/**
 * @internal
 */
class ExportService implements ContentRepositoryServiceInterface
{

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly Filesystem $filesystem,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly AssetRepository $assetRepository,
        private readonly AssetUsageService $assetUsageService,
        private readonly EventStoreInterface $eventStore,
    ) {
    }

    public function runAllProcessors(\Closure $outputLineFn, bool $verbose = false): void
    {
        /** @var array<string, ProcessorInterface> $processors */
        $processors = [
            'Exporting events' => new EventExportProcessor(
                $this->filesystem,
                $this->workspaceFinder,
                $this->eventStore
            ),
            'Exporting assets' => new AssetExportProcessor(
                $this->contentRepositoryId,
                $this->filesystem,
                $this->assetRepository,
                $this->workspaceFinder,
                $this->assetUsageService
            )
        ];

        foreach ($processors as $label => $processor) {
            $outputLineFn($label . '...');
            $verbose && $processor->onMessage(
                fn(Severity $severity, string $message) => $outputLineFn('<%1$s>%2$s</%1$s>', [$severity === Severity::ERROR ? 'error' : 'comment', $message])
            );
            $result = $processor->run();
            if ($result->severity === Severity::ERROR) {
                throw new \RuntimeException($label . ': ' . ($result->message ?? ''));
            }
            $outputLineFn('  ' . $result->message);
            $outputLineFn();
        }
    }
}
