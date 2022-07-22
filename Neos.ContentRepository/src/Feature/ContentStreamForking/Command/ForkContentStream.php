<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Feature\ContentStreamForking\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * ForkContentStream for creating a new fork of a content stream.
 */
final class ForkContentStream implements CommandInterface
{
    public function __construct(
        /**
         * TODO: TargetContentStreamIdentifier??
         *
         * Content stream identifier for the new content stream
         *
         * @var ContentStreamIdentifier
         */
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly ContentStreamIdentifier $sourceContentStreamIdentifier,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    /**
     * @param array<string,string> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            ContentStreamIdentifier::fromString($array['sourceContentStreamIdentifier']),
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }
}
