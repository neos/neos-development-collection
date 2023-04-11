<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Export;

/*
 * This file is part of the Neos.ContentRepository.LegacyNodeMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use League\Flysystem\Filesystem;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Processors\EventStoreImportProcessor;
use Neos\EventStore\EventStoreInterface;
use Neos\ContentRepository\Export\Processors\AssetRepositoryImportProcessor;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;

class ImportService implements ContentRepositoryServiceInterface
{

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ContentStreamId $contentStreamIdentifier,
        private readonly AssetRepository $assetRepository,
        private readonly ResourceRepository $resourceRepository,
        private readonly ResourceManager $resourceManager,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly EventNormalizer $eventNormalizer,
        private readonly EventStoreInterface $eventStore,
    ) {
    }

    public function runAllProcessors(\Closure $outputLineFn, bool $verbose = false): void
    {
        if ($this->liveWorkspaceContentStreamExisits()) {
            throw new \RuntimeException("Your content repository already contains a live workspace. Please use ./flow cr:prune <content-repository-identifier> to clear the regarding content repository before you import a one.");
        }

        /** @var ProcessorInterface[] $processors */
        $processors = [
            'Importing assets' => new AssetRepositoryImportProcessor(
                $this->filesystem,
                $this->assetRepository,
                $this->resourceRepository,
                $this->resourceManager,
                $this->persistenceManager,
            ),
            'Importing events' => new  EventStoreImportProcessor(
                false,
                $this->filesystem,
                $this->eventStore,
                $this->eventNormalizer,
                $this->contentStreamIdentifier,
            )
        ];

        foreach ($processors as $label => $processor) {
            $outputLineFn($label . '...');
            $verbose && $processor->onMessage(fn(Severity $severity, string $message) => $outputLineFn('<%1$s>%2$s</%1$s>', [$severity === Severity::ERROR ? 'error' : 'comment', $message]));
            $result = $processor->run();
            if ($result->severity === Severity::ERROR) {
                throw new \RuntimeException($label . ': ' . $result->message ?? '');
            }
            $outputLineFn('  ' . $result->message);
            $outputLineFn();
        }
    }

    private function liveWorkspaceContentStreamExisits(): bool
    {
        $workspaceStreamName = WorkspaceEventStreamName::fromWorkspaceName(WorkspaceName::forLive())->getEventStreamName();
        return $this->eventStore->load($workspaceStreamName)->getIterator()->current() !== null;
    }
}
