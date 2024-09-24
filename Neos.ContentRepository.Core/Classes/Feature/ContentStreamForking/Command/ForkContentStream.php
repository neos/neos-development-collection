<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\ContentStreamForking\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * ForkContentStream for creating a new fork of a content stream.
 *
 * @internal implementation detail. You must not use this command directly.
 * Direct use may lead to hard to revert senseless state in your content repository.
 * Please use the higher level workspace commands instead.
 */
final readonly class ForkContentStream implements CommandInterface
{
    /**
     * @param ContentStreamId $newContentStreamId The id of the new content stream
     * @param ContentStreamId $sourceContentStreamId The id of the content stream to fork
     */
    private function __construct(
        public ContentStreamId $newContentStreamId,
        public ContentStreamId $sourceContentStreamId,
    ) {
    }

    /**
     * @param ContentStreamId $newContentStreamId The id of the new content stream
     * @param ContentStreamId $sourceContentStreamId The id of the content stream to fork
     */
    public static function create(ContentStreamId $newContentStreamId, ContentStreamId $sourceContentStreamId): self
    {
        return new self($newContentStreamId, $sourceContentStreamId);
    }

    /**
     * @param array<string,string> $array
     * @internal only used for testcases
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
            ContentStreamId::fromString($array['sourceContentStreamId']),
        );
    }
}
