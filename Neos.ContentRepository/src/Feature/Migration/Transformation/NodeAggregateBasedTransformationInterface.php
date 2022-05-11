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

namespace Neos\ContentRepository\Feature\Migration\Transformation;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;

/**
 * A node-aggregate-based transformation, like changing the node aggregate type
 *
 * Settings given to a transformation will be passed to accordingly named setters.
 */
interface NodeAggregateBasedTransformationInterface
{
    public function execute(
        ReadableNodeAggregateInterface $nodeAggregate,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult;
}
