<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception;

/**
 * The exception to be thrown if a dimension space point is tried to be used as a generalization of another one but isn't
 */
class DimensionSpacePointIsNoGeneralization extends \DomainException
{
}
