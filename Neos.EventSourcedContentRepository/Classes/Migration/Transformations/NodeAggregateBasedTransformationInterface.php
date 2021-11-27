<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Migration\Transformations;

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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;

/**
 * A node-aggregate-based transformation, like changing the node aggregate type
 *
 * Settings given to a transformation will be passed to accordingly named setters.
 */
interface NodeAggregateBasedTransformationInterface
{
    public function execute(ReadableNodeAggregateInterface $nodeAggregate, ContentStreamIdentifier $contentStreamForWriting): CommandResult;
}
