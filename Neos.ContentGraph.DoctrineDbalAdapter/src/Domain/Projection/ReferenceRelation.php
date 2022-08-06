<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

use Neos\Flow\Annotations as Flow;

/**
 * The active record for reading and writing reference relations from and to the database
 */
#[Flow\Proxy(false)]
final class ReferenceRelation
{
    public const TABLE_NAME = 'neos_contentgraph_referencerelation';
}
