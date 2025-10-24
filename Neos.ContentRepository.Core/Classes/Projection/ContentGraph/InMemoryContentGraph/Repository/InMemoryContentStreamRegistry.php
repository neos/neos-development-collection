<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository;

use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryContentStreamRecord;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\Version;

/**
 * The In-Memory workspace registry
 *
 * To be used as a source of workspaces for read and write in-memory
 *
 * @internal
 */
final class InMemoryContentStreamRegistry
{
    private static ?self $instance = null;

    /**
     * @param array<string,InMemoryContentStreamRecord> $contentStreams indexed by id
     */
    private function __construct(
        public array $contentStreams = []
    ) {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function reset(): void
    {
        $this->contentStreams = [];
    }

    public function createContentStream(ContentStreamId $contentStreamId, ?ContentStreamId $sourceContentStreamId = null, ?Version $sourceVersion = null): void
    {
        if (array_key_exists($contentStreamId->value, $this->contentStreams)) {
            throw new \Exception('Content stream with ID ' . $contentStreamId->value . ' already exists.', 1745098891);
        }

        $this->contentStreams[$contentStreamId->value] = new InMemoryContentStreamRecord(
            $contentStreamId,
            Version::first(),
            $sourceContentStreamId,
            $sourceVersion,
        );
    }

    public function closeContentStream(ContentStreamId $contentStreamId): void
    {
        $this->requireContentStreamToExist($contentStreamId);
        $this->contentStreams[$contentStreamId->value]->isClosed = true;
    }

    public function reopenContentStream(ContentStreamId $contentStreamId): void
    {
        $this->requireContentStreamToExist($contentStreamId);
        $this->contentStreams[$contentStreamId->value]->isClosed = false;
    }

    public function removeContentStream(ContentStreamId $contentStreamId): void
    {
        if (array_key_exists($contentStreamId->value, $this->contentStreams)) {
            unset($this->contentStreams[$contentStreamId->value]);
        }
    }

    public function updateContentStreamVersion(ContentStreamId $contentStreamId, Version $version, bool $markAsDirty): void
    {
        $this->requireContentStreamToExist($contentStreamId);

        $this->contentStreams[$contentStreamId->value]->version = $version;
        if ($markAsDirty) {
            $this->contentStreams[$contentStreamId->value]->hasChanges = true;
        }
    }

    private function requireContentStreamToExist(ContentStreamId $contentStreamId): void
    {
        if (!array_key_exists($contentStreamId->value, $this->contentStreams)) {
            throw new \Exception('Content stream with ID ' . $contentStreamId->value . ' does not exist.', 1745098990);
        }
    }
}
