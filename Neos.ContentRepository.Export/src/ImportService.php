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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Export\Processors\EventStoreImportProcessor;
use Neos\EventStore\EventStoreInterface;

class ImportService implements ContentRepositoryServiceInterface
{

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly EventNormalizer $eventNormalizer,
        private readonly EventStoreInterface $eventStore,
        private readonly ContentStreamId $contentStreamIdentifier,
    ) {
    }

    public function runAllProcessors(\Closure $outputLineFn, bool $verbose = false): void
    {
        /** @var ProcessorInterface[] $processors */
        $processors = [
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
            $verbose && $processor->onMessage(fn (Severity $severity, string $message) => $outputLineFn('<%1$s>%2$s</%1$s>', [$severity === Severity::ERROR ? 'error' : 'comment', $message]));
            $result = $processor->run();
            if ($result->severity === Severity::ERROR) {
                throw new \RuntimeException($label . ': ' . $result->message ?? '');
            }
            $outputLineFn('  ' . $result->message);
            $outputLineFn();
        }
    }
}
