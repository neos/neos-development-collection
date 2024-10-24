<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service\ContentStreamPruner;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;

/**
 * @internal
 */
final readonly class ContentStreamForPruning
{
    private function __construct(
        public ContentStreamId $contentStreamId,
        public ContentStreamStatus $status,
        public ?ContentStreamId $sourceContentStreamId,
        public bool $removed,
    ) {
    }

    public static function create(
        ContentStreamId $contentStreamId,
        ContentStreamStatus $status,
        ?ContentStreamId $sourceContentStreamId
    ): self {
        return new self(
            $contentStreamId,
            $status,
            $sourceContentStreamId,
            false
        );
    }

    public function withStatus(ContentStreamStatus $status): self
    {
        return new self(
            $this->contentStreamId,
            $status,
            $this->sourceContentStreamId,
            $this->removed
        );
    }

    public function withRemoved(): self
    {
        return new self(
            $this->contentStreamId,
            $this->status,
            $this->sourceContentStreamId,
            true
        );
    }
}
