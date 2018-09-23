<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain as ContentRepository;
use Neos\Flow\Annotations as Flow;

/**
 * The node relation anchor value object
 */
class NodeRelationAnchorPoint extends ContentRepository\ValueObject\AbstractIdentifier
{
}
