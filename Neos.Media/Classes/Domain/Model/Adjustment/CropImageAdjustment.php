<?php
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

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Neos\Media\Domain\Model\ImageInterface;

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
     * @var integer
     * @ORM\Column(nullable = true)
     */
    protected $x;

    /**
     * @var integer
     * @ORM\Column(nullable = true)
     */
    protected $y;
    /**
     * @var integer
     * @ORM\Column(nullable = true)
     */
    protected $width;

    /**
     * @var integer
     * @ORM\Column(nullable = true)
     */
    protected $height;

    /**
     * Sets height
     *
     * @param integer $height
     * @return void
     * @api
     */
    public function setHeight($height): void
    {
        $this->height = $height;
    }

    /**
     * Returns height
     *
     * @return integer|null
     * @api
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * Sets width
     *
     * @param integer $width
     * @return void
     * @api
     */
    public function setWidth($width): void
    {
        $this->width = $width;
    }

    /**
     * Returns width
     *
     * @return integer
     * @api
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * Sets x
     *
     * @param integer $x
     * @return void
     * @api
     */
    public function setX($x): void
    {
        $this->x = $x;
    }

    /**
     * Returns x
     *
     * @return integer
     * @api
     */
    public function getX(): ?int
    {
        return $this->x;
    }

    /**
     * Sets y
     *
     * @param integer $y
     * @return void
     * @api
     */
    public function setY($y): void
    {
        $this->y = $y;
    }

    /**
     * Returns y
     *
     * @return integer
     * @api
     */
    public function getY(): ?int
    {
        return $this->y;
    }

    /**
     * Check if this Adjustment can or should be applied to its ImageVariant.
     *
     * @param ImagineImageInterface $image
     * @return bool
     */
    public function canBeApplied(ImagineImageInterface $image): bool
    {
        if (
            $this->x === 0 &&
            $this->y === 0 &&
            $image->getSize()->getWidth() === $this->width &&
            $image->getSize()->getHeight() === $this->height
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
    public function applyToImage(ImagineImageInterface $image): ImagineImageInterface
    {
        $point = new Point($this->x, $this->y);
        $box = new Box($this->width, $this->height);
        return $image->crop($point, $box);
    }

    /**
     * Refits the crop proportions to be the maximum size within the image boundaries.
     *
     * @param ImageInterface $image
     * @return void
     */
    public function refit(ImageInterface $image): void
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
