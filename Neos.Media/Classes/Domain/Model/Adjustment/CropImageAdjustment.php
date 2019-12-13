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

use Doctrine\ORM\Mapping as ORM;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\Point;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\ValueObject\Configuration\AspectRatio;

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
     * @var string
     * @ORM\Column(nullable = true)
     */
    protected $aspectRatioAsString;

    /**
     * Sets height
     *
     * @param integer $height
     * @return void
     * @api
     */
    public function setHeight(int $height = null): void
    {
        $this->height = $height;
        $this->aspectRatioAsString = null;
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
    public function setWidth(int $width = null): void
    {
        $this->width = $width;
        $this->aspectRatioAsString = null;
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
    public function setX(int $x = null): void
    {
        $this->x = $x;
        $this->aspectRatioAsString = null;
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
    public function setY(int $y = null): void
    {
        $this->y = $y;
        $this->aspectRatioAsString = null;
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
     * This setter accepts strings in order to make configuration / mapping of settings easier.
     *
     * @param AspectRatio | string | null $aspectRatio
     */
    public function setAspectRatio($aspectRatio = null): void
    {
        if ($aspectRatio === null) {
            $this->aspectRatioAsString = null;
            return;
        }
        if (!$aspectRatio instanceof AspectRatio && !is_string($aspectRatio)) {
            throw new \InvalidArgumentException(sprintf('Aspect ratio must be either AspectRatio or string, %s given.', gettype($aspectRatio)), 1552652570);
        }
        if (is_string($aspectRatio)) {
            $aspectRatio = AspectRatio::fromString($aspectRatio);
        }

        $this->aspectRatioAsString = (string)$aspectRatio;
        $this->x = null;
        $this->y = null;
        $this->width = null;
        $this->height = null;
    }

    /**
     * @return AspectRatio|null
     */
    public function getAspectRatio(): ?AspectRatio
    {
        return $this->aspectRatioAsString ? AspectRatio::fromString($this->aspectRatioAsString) : null;
    }

    /**
     * Calculates the actual position and dimensions of the cropping area based on the given image
     * dimensions and desired aspect ratio.
     *
     * @param int $originalWidth Width of the original image
     * @param int $originalHeight Height of the original image
     * @param AspectRatio $desiredAspectRatio The desired aspect ratio
     * @return array Returns an array containing x, y, width and height
     */
    public static function calculateDimensionsByAspectRatio(int $originalWidth, int $originalHeight, AspectRatio $desiredAspectRatio): array
    {
        $newWidth = $originalWidth;
        $newHeight = round($originalWidth / $desiredAspectRatio->getRatio());
        $newX = 0;
        $newY = round(($originalHeight - $newHeight) / 2);

        if ($newHeight > $originalHeight) {
            $newHeight = $originalHeight;
            $newY = 0;
            $newWidth = round($originalHeight * $desiredAspectRatio->getRatio());
            $newX = round(($originalWidth - $newWidth) / 2);
        }

        return [$newX, $newY, $newWidth, $newHeight];
    }

    /**
     * Check if this Adjustment can or should be applied to its ImageVariant.
     *
     * @param ImagineImageInterface $image
     * @return bool
     */
    public function canBeApplied(ImagineImageInterface $image): bool
    {
        if ($this->aspectRatioAsString !== null) {
            $desiredAspectRatio = AspectRatio::fromString($this->aspectRatioAsString);
            $originalAspectRatio = new AspectRatio($image->getSize()->getWidth(), $image->getSize()->getHeight());
            return $originalAspectRatio->getRatio() !== $desiredAspectRatio->getRatio();
        }

        return !(
            $this->x === 0 &&
            $this->y === 0 &&
            $image->getSize()->getWidth() === $this->width &&
            $image->getSize()->getHeight() === $this->height
        );
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
        $desiredAspectRatio = $this->getAspectRatio();
        if ($desiredAspectRatio !== null) {
            $originalWidth = $image->getSize()->getWidth();
            $originalHeight = $image->getSize()->getHeight();

            [$newX, $newY, $newWidth, $newHeight] = self::calculateDimensionsByAspectRatio($originalWidth, $originalHeight, $desiredAspectRatio);

            $point = new Point($newX, $newY);
            $box = new Box($newWidth, $newHeight);
        } else {
            $point = new Point($this->x, $this->y);
            $box = new Box($this->width, $this->height);
        }
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
        $roundedHeight = (int)round($this->getHeight() / $ratio, 0);
        $this->setHeight($roundedHeight);

        if ($this->getHeight() > $image->getHeight()) {
            $ratio = $this->getHeight() / $image->getHeight();
            $roundedWidth = (int)round($this->getWidth() / $ratio, 0);
            $this->setWidth($roundedWidth);
            $this->setHeight($image->getHeight());
        }
    }
}
