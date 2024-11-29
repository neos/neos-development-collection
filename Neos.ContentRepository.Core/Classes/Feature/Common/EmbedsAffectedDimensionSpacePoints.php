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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;

/**
 * This interface is implemented by **events** which affect dimension space points.
 *
 * @internal
 */
interface EmbedsAffectedDimensionSpacePoints
{
    public function getAffectedDimensionSpacePoints(): DimensionSpacePointSet;
}
