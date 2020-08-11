<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Model\Adjustment;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Imagine\Image\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;

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
