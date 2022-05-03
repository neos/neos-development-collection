<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

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
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;

/**
 * This interface is implemented by **events** which contain ContentStreamIdentifier and NodeAggregateIdentifier.
 *
 * This is relevant e.g. for content cache flushing as a result of an event.
 */
interface EmbedsContentStreamAndNodeAggregateIdentifier
{
    public function getContentStreamIdentifier(): ContentStreamIdentifier;
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier;
}
