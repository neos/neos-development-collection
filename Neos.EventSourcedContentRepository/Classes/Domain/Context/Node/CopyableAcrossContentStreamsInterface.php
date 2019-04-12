<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;

/**
 * This interface is implemented by commands and events which allow to be copied to a different content stream.
 */
interface CopyableAcrossContentStreamsInterface
{
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier);
}
