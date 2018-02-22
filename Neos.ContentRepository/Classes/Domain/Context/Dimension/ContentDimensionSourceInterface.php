<?php
namespace Neos\ContentRepository\Domain\Context\Dimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The content dimension source interface
 * Declares the API for
 */
interface ContentDimensionSourceInterface
{
    /**
     * Returns a content dimension by its identifier, if available
     *
     * @param ContentDimensionIdentifier $dimensionIdentifier
     * @return ContentDimension|null
     */
    public function getDimension(ContentDimensionIdentifier $dimensionIdentifier): ?ContentDimension;

    /**
     * Returns all available content dimensions in correct order of priority
     *
     * @return array|ContentDimension[]
     */
    public function getContentDimensionsOrderedByPriority(): array;
}