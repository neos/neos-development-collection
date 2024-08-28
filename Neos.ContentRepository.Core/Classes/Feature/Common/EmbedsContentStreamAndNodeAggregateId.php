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

namespace Neos\ContentRepository\Core\Feature\Common;

/**
 * This interface is implemented by **events** which contain ContentStreamId and NodeAggregateId.
 *
 * This is relevant e.g. for content cache flushing as a result of an event.
 *
 * @internal
 * @deprecated Use {@see EmbedsContentStreamId} and/or {@see EmbedsNodeAggregateId} instead. Will be removed with Neos 10.
 */
interface EmbedsContentStreamAndNodeAggregateId extends EmbedsContentStreamId, EmbedsNodeAggregateId
{
}
