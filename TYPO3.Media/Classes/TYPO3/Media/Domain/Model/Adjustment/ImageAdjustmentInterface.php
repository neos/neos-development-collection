<?php
namespace TYPO3\Media\Domain\Model\Adjustment;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use Imagine\Image\ImageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\ImageVariant;

/**
 * Interface for an Image Adjustment
 */
interface ImageAdjustmentInterface extends AdjustmentInterface
{
    /**
     * Applies this adjustment to the given Imagine Image object
     *
     * @param ImageInterface $image
     * @return ImageInterface
     */
    public function applyToImage(ImageInterface $image);

    /**
     * Sets the image variant this adjustment belongs to
     *
     * @param ImageVariant $imageVariant
     * @return void
     */
    public function setImageVariant(ImageVariant $imageVariant);

    /**
     * Check if this Adjustment can or should be applied to its ImageVariant.
     *
     * @param ImageInterface $image
     * @return boolean
     */
    public function canBeApplied(ImageInterface $image);
}
