<?php
namespace Neos\Media\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An interface which defines that an asset has support for variants
 */
interface VariantSupportInterface
{
    /**
     * Returns all variants (if any) derived from this asset
     *
     * @return array
     * @api
     */
    public function getVariants();
}
