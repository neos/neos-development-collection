<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\ContentAccess;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;

interface NodeAccessorFactoryInterface
{
    public function build(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint, VisibilityConstraints $visibilityConstraints, ?NodeAccessorInterface $nextAccessor = null): NodeAccessorInterface;
}
