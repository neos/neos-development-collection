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

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

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
