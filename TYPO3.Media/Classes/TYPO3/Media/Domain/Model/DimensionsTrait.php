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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Trait for methods regarding the dimensions of an asset
 */
trait DimensionsTrait
{
    /**
     * @var integer
     * @ORM\Column(nullable=true)
     */
    protected $width = 0;

    /**
     * @var integer
     * @ORM\Column(nullable=true)
     */
    protected $height = 0;

    /**
     * Width of the image in pixels
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Height of the image in pixels
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Does the asset have dimensions
     *
     * @return boolean
     */
    public function hasDimensions()
    {
        return ($this->width !== null && $this->height !== null);
    }

    /**
     * Edge / aspect ratio of the image
     *
     * @param boolean $respectOrientation If false (the default), orientation is disregarded and always a value >= 1 is returned (like usual in "4 / 3" or "16 / 9")
     * @return float
     */
    public function getAspectRatio($respectOrientation = false)
    {
        if (!$this->hasDimensions()) {
            return 0;
        }

        $aspectRatio = $this->getWidth() / $this->getHeight();
        if ($respectOrientation === false && $aspectRatio < 1) {
            $aspectRatio = 1 / $aspectRatio;
        }

        return $aspectRatio;
    }

    /**
     * Orientation of this image, i.e. portrait, landscape or square
     *
     * @return string One of this interface's ORIENTATION_* constants.
     */
    public function getOrientation()
    {
        $aspectRatio = $this->getAspectRatio(true);
        if ($aspectRatio > 1) {
            return ImageInterface::ORIENTATION_LANDSCAPE;
        } elseif ($aspectRatio < 1) {
            return ImageInterface::ORIENTATION_PORTRAIT;
        } else {
            return ImageInterface::ORIENTATION_SQUARE;
        }
    }

    /**
     * Whether this image is square aspect ratio and therefore has a square orientation
     *
     * @return boolean
     */
    public function isOrientationSquare()
    {
        return $this->getOrientation() === ImageInterface::ORIENTATION_SQUARE;
    }

    /**
     * Whether this image is in landscape orientation
     *
     * @return boolean
     */
    public function isOrientationLandscape()
    {
        return $this->getOrientation() === ImageInterface::ORIENTATION_LANDSCAPE;
    }

    /**
     * Whether this image is in portrait orientation
     *
     * @return boolean
     */
    public function isOrientationPortrait()
    {
        return $this->getOrientation() === ImageInterface::ORIENTATION_PORTRAIT;
    }
}
