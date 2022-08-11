<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Common;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;

/**
 * This interface is implemented by **commands** which can be rebased to other Content Streams. This is basically all
 * node-based commands.
 *
 * Reminder: a rebase can fail, because the target content stream might contain conflicting changes.
 */
interface RebasableToOtherContentStreamsInterface
{
    public function createCopyForContentStream(ContentStreamIdentifier $target): CommandInterface;

    /**
     * called during deserialization from metadata
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self;
}
