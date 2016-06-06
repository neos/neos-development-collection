<?php
namespace TYPO3\Media\Domain\Model\Adjustment;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use TYPO3\Media\Domain\Model\ImageInterface;

/**
 * An adjustment for cropping an image
 *
 * @Flow\Entity
 */
class CropImageAdjustment extends AbstractImageAdjustment
{
    /**
     * @var integer
     */
    protected $position = 10;

    /**
     * @var array
     */
    protected $configuration = [
        'height' => null,
        'width' => null,
        'x' => null,
        'y' => null
    ];

    /**
     * Sets height
     *
     * @param integer $height
     * @return void
     * @api
     */
    public function setHeight($height)
    {
        $this->setConfigurationValue('height', $height);
    }

    /**
     * Returns height
     *
     * @return integer
     * @api
     */
    public function getHeight()
    {
        return $this->getConfigurationValue('height');
    }

    /**
     * Sets width
     *
     * @param integer $width
     * @return void
     * @api
     */
    public function setWidth($width)
    {
        $this->setConfigurationValue('width', $width);
    }

    /**
     * Returns width
     *
     * @return integer
     * @api
     */
    public function getWidth()
    {
        return $this->getConfigurationValue('width');
    }

    /**
     * Sets x
     *
     * @param integer $x
     * @return void
     * @api
     */
    public function setX($x)
    {
        $this->setConfigurationValue('x', $x);
    }

    /**
     * Returns x
     *
     * @return integer
     * @api
     */
    public function getX()
    {
        return $this->getConfigurationValue('x');
    }

    /**
     * Sets y
     *
     * @param integer $y
     * @return void
     * @api
     */
    public function setY($y)
    {
        $this->setConfigurationValue('y', $y);
    }

    /**
     * Returns y
     *
     * @return integer
     * @api
     */
    public function getY()
    {
        return $this->getConfigurationValue('y');
    }

    /**
     * Check if this Adjustment can or should be applied to its ImageVariant.
     *
     * @param ImagineImageInterface $image
     * @return boolean
     */
    public function canBeApplied(ImagineImageInterface $image)
    {
        if (
            $this->getX() === 0 &&
            $this->getY() === 0 &&
            $image->getSize()->getWidth() === $this->getWidth() &&
            $image->getSize()->getHeight() === $this->getHeight()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Applies this adjustment to the given Imagine Image object
     *
     * @param ImagineImageInterface $image
     * @return ImagineImageInterface
     * @internal Should never be used outside of the media package. Rely on the ImageService to apply your adjustments.
     */
    public function applyToImage(ImagineImageInterface $image)
    {
        $point = new Point($this->getX(), $this->getY());
        $box = new Box($this->getWidth(), $this->getHeight());
        return $image->crop($point, $box);
    }

    /**
     * Refits the crop proportions to be the maximum size within the image boundaries.
     *
     * @param ImageInterface $image
     * @return void
     */
    public function refit(ImageInterface $image)
    {
        $this->x = 0;
        $this->y = 0;

        $ratio = $this->getWidth() / $image->getWidth();
        $this->setWidth($image->getWidth());
        $this->setHeight($this->getHeight() / $ratio);

        if ($this->getHeight() > $image->getHeight()) {
            $ratio = $this->getHeight() / $image->getHeight();
            $this->setWidth($this->getWidth() / $ratio);
            $this->setHeight($image->getHeight());
        }
    }
}
