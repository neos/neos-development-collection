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

namespace Neos\ContentRepository\Dimension;

/**
 * The content dimension source interface
 *
 * @api
 */
interface ContentDimensionSourceInterface
{
    /**
     * Returns a content dimension by its identifier, if available
     */
    public function getDimension(ContentDimensionIdentifier $dimensionIdentifier): ?ContentDimension;

    /**
     * Returns all available content dimensions in correct order of priority, indexed by identifier
     *
     * @return array<string,ContentDimension>
     */
    public function getContentDimensionsOrderedByPriority(): array;
}
