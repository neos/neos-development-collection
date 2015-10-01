<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
