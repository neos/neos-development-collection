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
 * @api commands are the write-API of the ContentRepository
 */
final class CloseContentStream implements CommandInterface
{
    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to close
     */
    private function __construct(
        public readonly ContentStreamId $contentStreamId,
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to close
     */
    public static function create(ContentStreamId $contentStreamId): self
    {
        return new self($contentStreamId);
    }

    /**
     * @param array<string,string> $array
     * @internal only used for testcases
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
        );
    }
}
