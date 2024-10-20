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
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Point;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Imagine\Box;

/**
 * An adjustment for resizing an image
 *
 * @Flow\Entity
 */
class ResizeImageAdjustment extends AbstractImageAdjustment
{
    /**
     * @var integer
     */
    protected $position = 20;

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
     * @var integer
     * @ORM\Column(nullable = true)
     */
    protected $maximumWidth;

    /**
     * @var integer
     * @ORM\Column(nullable = true)
     */
    protected $maximumHeight;

    /**
     * @var integer
     * @ORM\Column(nullable = true)
     */
    protected $minimumWidth;

    /**
     * @var integer
     * @ORM\Column(nullable = true)
     */
    protected $minimumHeight;

    /**
     * One of the ImagineImageInterface::RATIOMODE_* constants
     *
     * @var string
     * @ORM\Column(nullable = true)
     */
    protected $ratioMode = ImageInterface::RATIOMODE_INSET;

    /**
     * @var boolean
     * @ORM\Column(nullable = true)
     */
    protected $allowUpScaling;

    /**
     * @Flow\InjectConfiguration(package="Neos.Media", path="image.defaultOptions.resizeFilter")
     * @var string
     */
    protected $filter;

    /**
     * Sets maximumHeight
     *
     * @param integer $maximumHeight
     * @return void
     * @api
     */
    public function setMaximumHeight(int $maximumHeight = null): void
    {
        $this->maximumHeight = $maximumHeight;
    }

    /**
     * Returns maximumHeight
     *
     * @return integer
     * @api
     */
    public function getMaximumHeight(): ?int
    {
        return $this->maximumHeight;
    }

    /**
     * Sets maximumWidth
     *
     * @param integer $maximumWidth
     * @return void
     * @api
     */
    public function setMaximumWidth(int $maximumWidth = null): void
    {
        $this->maximumWidth = $maximumWidth;
    }

    /**
     * Returns maximumWidth
     *
     * @return integer
     * @api
     */
    public function getMaximumWidth(): ?int
    {
        return $this->maximumWidth;
    }

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
    }

    /**
     * Returns height
     *
     * @return integer
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
     * Sets minimumHeight
     *
     * @param integer $minimumHeight
     * @return void
     * @api
     */
    public function setMinimumHeight(int $minimumHeight = null): void
    {
        $this->minimumHeight = $minimumHeight;
    }

    /**
     * Returns minimumHeight
     *
     * @return integer
     * @api
     */
    public function getMinimumHeight(): ?int
    {
        return $this->minimumHeight;
    }

    /**
     * Sets minimumWidth
     *
     * @param integer $minimumWidth
     * @return void
     * @api
     */
    public function setMinimumWidth(int $minimumWidth = null): void
    {
        $this->minimumWidth = $minimumWidth;
    }

    /**
     * Returns minimumWidth
     *
     * @return integer
     * @api
     */
    public function getMinimumWidth(): ?int
    {
        return $this->minimumWidth;
    }

    /**
     * Sets ratioMode
     *
     * @param string $ratioMode One of the \Neos\Media\Domain\Model\ImageInterface::RATIOMODE_* constants
     * @return void
     * @api
     */
    public function setRatioMode(string $ratioMode): void
    {
        if ($ratioMode === '') {
            $ratioMode = ImageInterface::RATIOMODE_INSET;
        }
        $supportedModes = [ImageInterface::RATIOMODE_INSET, ImageInterface::RATIOMODE_OUTBOUND];
        if (!in_array($ratioMode, $supportedModes, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid mode "%s" specified, supported modes are: "%s" (but use the ImageInterface::RATIOMODE_* constants)', $ratioMode, implode('", "', $supportedModes)), 1574686876);
        }

        $this->ratioMode = $ratioMode;
    }

    /**
     * Returns ratioMode
     *
     * @return string
     * @api
     */
    public function getRatioMode(): string
    {
        return $this->ratioMode;
    }

    /**
     * Returns allowUpScaling
     *
     * @return boolean
     */
    public function getAllowUpScaling(): bool
    {
        return (boolean)$this->allowUpScaling;
    }

    /**
     * Sets allowUpScaling
     *
     * @param boolean $allowUpScaling
     * @return void
     */
    public function setAllowUpScaling(bool $allowUpScaling): void
    {
        $this->allowUpScaling = $allowUpScaling;
    }

    /**
     * Check if this Adjustment can or should be applied to its ImageVariant.
     *
     * @param ImagineImageInterface $image
     * @return boolean
     */
    public function canBeApplied(ImagineImageInterface $image)
    {
        $expectedDimensions = ResizeDimensionCalculator::calculateRequestedDimensions(
            $image->getSize(),
            $this->width,
            $this->height,
            $this->maximumWidth,
            $this->maximumHeight,
            $this->allowUpScaling ?? false,
            $this->ratioMode ?? ImageInterface::RATIOMODE_INSET
        );

        return ((string)$expectedDimensions !== (string)$image->getSize());
    }

    /**
     * Applies this adjustment to the given Imagine Image object
     *
     * @param ImagineImageInterface $image
     * @return ImagineImageInterface|ManipulatorInterface
     * @internal Should never be used outside of the media package. Rely on the ImageService to apply your adjustments.
     */
    public function applyToImage(ImagineImageInterface $image)
    {
        return $this->resize($image, $this->ratioMode, $this->filter);
    }

    /**
     * Calculates and returns the dimensions the image should have according all parameters set
     * in this adjustment.
     *
     * @param BoxInterface $originalDimensions Dimensions of the unadjusted image
     * @return BoxInterface
     * @deprecated use ResizeDimensionCalculator::calculateRequestedDimensions instead
     */
    protected function calculateDimensions(BoxInterface $originalDimensions): BoxInterface
    {
        return ResizeDimensionCalculator::calculateRequestedDimensions(
            $originalDimensions,
            $this->width,
            $this->height,
            $this->maximumWidth,
            $this->maximumHeight,
            $this->allowUpScaling ?? false,
            $this->ratioMode ?? ImageInterface::RATIOMODE_INSET
        );
    }

    /**
     * Executes the actual resizing operation on the Imagine image.
     * In case of an outbound resize the image will be resized and cropped.
     *
     * @param ImagineImageInterface $image
     * @param string $mode
     * @param string $filter
     * @return ManipulatorInterface
     */
    protected function resize(ImagineImageInterface $image, string $mode = ImageInterface::RATIOMODE_INSET, string $filter = ImagineImageInterface::FILTER_UNDEFINED): ManipulatorInterface
    {
        if ($mode !== ImageInterface::RATIOMODE_INSET &&
            $mode !== ImageInterface::RATIOMODE_OUTBOUND
        ) {
            throw new \InvalidArgumentException('Invalid mode specified', 1574686891);
        }

        $originalDimensions = $image->getSize();

        $requestedDimensions = ResizeDimensionCalculator::calculateRequestedDimensions(
            $originalDimensions,
            $this->width,
            $this->height,
            $this->maximumWidth,
            $this->maximumHeight,
            $this->allowUpScaling ?? false,
            $this->ratioMode ?? ImageInterface::RATIOMODE_INSET
        );

        $finalDimensions = ResizeDimensionCalculator::calculateOutboundScalingDimensions(
            $originalDimensions,
            $requestedDimensions,
            $this->ratioMode
        );

        $image->strip();
        $image->resize($finalDimensions, $filter);

        if ($mode === ImageInterface::RATIOMODE_OUTBOUND) {
            $image->crop(new Point(
                max(0, round(($finalDimensions->getWidth() - $requestedDimensions->getWidth()) / 2)),
                max(0, round(($finalDimensions->getHeight() - $requestedDimensions->getHeight()) / 2))
            ), $requestedDimensions);
        }

        return $image;
    }

    /**
     * Calculates a resize dimension box that allows for outbound resize.
     * The scaled image will be bigger than the requested dimensions in one dimension and then cropped.
     *
     * @param BoxInterface $imageSize
     * @param BoxInterface $requestedDimensions
     * @return BoxInterface
     * @deprecated use ResizeDimensionCalculator::calculateOutboundScalingDimensions instead
     */
    protected function calculateOutboundScalingDimensions(BoxInterface $imageSize, BoxInterface $requestedDimensions): BoxInterface
    {
        return ResizeDimensionCalculator::calculateOutboundScalingDimensions(
            $imageSize,
            $requestedDimensions,
            $this->ratioMode
        );
    }
}
