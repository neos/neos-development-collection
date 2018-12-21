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

use Doctrine\ORM\Mapping as ORM;

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
