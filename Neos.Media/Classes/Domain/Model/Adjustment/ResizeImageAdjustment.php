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

use Doctrine\ORM\Mapping as ORM;
use Neos\Media\Imagine\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\Point;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ImageInterface;

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
     * @ORM\Column(nullable = TRUE)
     */
    protected $width;

    /**
     * @var integer
     * @ORM\Column(nullable = TRUE)
     */
    protected $height;

    /**
     * @var integer
     * @ORM\Column(nullable = TRUE)
     */
    protected $maximumWidth;

    /**
     * @var integer
     * @ORM\Column(nullable = TRUE)
     */
    protected $maximumHeight;

    /**
     * @var integer
     * @ORM\Column(nullable = TRUE)
     */
    protected $minimumWidth;

    /**
     * @var integer
     * @ORM\Column(nullable = TRUE)
     */
    protected $minimumHeight;

    /**
     * One of the ImagineImageInterface::RATIOMODE_* constants
     *
     * @var string
     * @ORM\Column(nullable = TRUE)
     */
    protected $ratioMode;

    /**
     * @var boolean
     * @ORM\Column(nullable = TRUE)
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
    public function setMaximumHeight($maximumHeight)
    {
        $this->maximumHeight = $maximumHeight;
    }

    /**
     * Returns maximumHeight
     *
     * @return integer
     * @api
     */
    public function getMaximumHeight()
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
    public function setMaximumWidth($maximumWidth)
    {
        $this->maximumWidth = $maximumWidth;
    }

    /**
     * Returns maximumWidth
     *
     * @return integer
     * @api
     */
    public function getMaximumWidth()
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
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Returns height
     *
     * @return integer
     * @api
     */
    public function getHeight()
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
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * Returns width
     *
     * @return integer
     * @api
     */
    public function getWidth()
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
    public function setMinimumHeight($minimumHeight)
    {
        $this->minimumHeight = $minimumHeight;
    }

    /**
     * Returns minimumHeight
     *
     * @return integer
     * @api
     */
    public function getMinimumHeight()
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
    public function setMinimumWidth($minimumWidth)
    {
        $this->minimumWidth = $minimumWidth;
    }

    /**
     * Returns minimumWidth
     *
     * @return integer
     * @api
     */
    public function getMinimumWidth()
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
    public function setRatioMode($ratioMode)
    {
        $this->ratioMode = $ratioMode;
    }

    /**
     * Returns ratioMode
     *
     * @return string
     * @api
     */
    public function getRatioMode()
    {
        return $this->ratioMode;
    }

    /**
     * Returns allowUpScaling
     *
     * @return boolean
     */
    public function getAllowUpScaling()
    {
        return (boolean)$this->allowUpScaling;
    }

    /**
     * Sets allowUpScaling
     *
     * @param boolean $allowUpScaling
     * @return void
     */
    public function setAllowUpScaling($allowUpScaling)
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
        $expectedDimensions = $this->calculateDimensions($image->getSize());

        return ((string)$expectedDimensions !== (string)$image->getSize());
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
        $ratioMode = $this->ratioMode ?: ImageInterface::RATIOMODE_INSET;
        return $this->resize($image, $ratioMode, $this->filter);
    }

    /**
     * Calculates and returns the dimensions the image should have according all parameters set
     * in this adjustment.
     *
     * @param BoxInterface $originalDimensions Dimensions of the unadjusted image
     * @return BoxInterface
     */
    protected function calculateDimensions(BoxInterface $originalDimensions)
    {
        $newDimensions = clone $originalDimensions;

        switch (true) {
            // height and width are set explicitly:
            case ($this->width !== null && $this->height !== null):
                $newDimensions = $this->calculateWithFixedDimensions($originalDimensions, $this->width, $this->height);
                break;
            // only width is set explicitly:
            case ($this->width !== null):
                $newDimensions = $this->calculateScalingToWidth($originalDimensions, $this->width);
                break;
            // only height is set explicitly:
            case ($this->height !== null):
                $newDimensions = $this->calculateScalingToHeight($originalDimensions, $this->height);
                break;
        }

        // We apply maximum dimensions and scale the new dimensions proportionally down to fit into the maximum.
        if ($this->maximumWidth !== null && $newDimensions->getWidth() > $this->maximumWidth) {
            $newDimensions = $newDimensions->widen($this->maximumWidth);
        }

        if ($this->maximumHeight !== null && $newDimensions->getHeight() > $this->maximumHeight) {
            $newDimensions = $newDimensions->heighten($this->maximumHeight);
        }

        return $newDimensions;
    }

    /**
     * @param BoxInterface $originalDimensions
     * @param integer $requestedWidth
     * @param integer $requestedHeight
     * @return BoxInterface
     */
    protected function calculateWithFixedDimensions(BoxInterface $originalDimensions, $requestedWidth, $requestedHeight)
    {
        $ratioMode = $this->ratioMode ?: ImageInterface::RATIOMODE_INSET;

        if ($ratioMode === ImageInterface::RATIOMODE_OUTBOUND) {
            return $this->calculateOutboundBox($originalDimensions, $requestedWidth, $requestedHeight);
        }

        $newDimensions = clone $originalDimensions;

        $ratios = array(
            $requestedWidth / $originalDimensions->getWidth(),
            $requestedHeight / $originalDimensions->getHeight()
        );

        $ratio = min($ratios);
        $newDimensions = $newDimensions->scale($ratio);

        if ($this->getAllowUpScaling() === false && $originalDimensions->contains($newDimensions) === false) {
            return clone $originalDimensions;
        }

        return $newDimensions;
    }

    /**
     * Calculate the final dimensions for an outbound box. usually exactly the requested width and height unless that
     * would require upscaling and it is not allowed.
     *
     * @param BoxInterface $originalDimensions
     * @param integer $requestedWidth
     * @param integer $requestedHeight
     * @return BoxInterface
     */
    protected function calculateOutboundBox(BoxInterface $originalDimensions, $requestedWidth, $requestedHeight)
    {
        $newDimensions = new Box($requestedWidth, $requestedHeight);

        if ($this->getAllowUpScaling() === true || $originalDimensions->contains($newDimensions) === true) {
            return $newDimensions;
        }

        // We need to make sure that the new dimensions are such that no upscaling is needed.
        $ratios = array(
            $originalDimensions->getWidth() / $requestedWidth,
            $originalDimensions->getHeight() / $requestedHeight
        );

        $ratio = min($ratios);
        $newDimensions = $newDimensions->scale($ratio);

        return $newDimensions;
    }

    /**
     * Calculates new dimensions with a requested width applied. Takes upscaling into consideration.
     *
     * @param BoxInterface $originalDimensions
     * @param integer $requestedWidth
     * @return BoxInterface
     */
    protected function calculateScalingToWidth(BoxInterface $originalDimensions, $requestedWidth)
    {
        if ($this->getAllowUpScaling() === false && $requestedWidth >= $originalDimensions->getWidth()) {
            return $originalDimensions;
        }

        $newDimensions = clone $originalDimensions;
        $newDimensions = $newDimensions->widen($requestedWidth);

        return $newDimensions;
    }

    /**
     * Calculates new dimensions with a requested height applied. Takes upscaling into consideration.
     *
     * @param BoxInterface $originalDimensions
     * @param integer $requestedHeight
     * @return BoxInterface
     */
    protected function calculateScalingToHeight(BoxInterface $originalDimensions, $requestedHeight)
    {
        if ($this->getAllowUpScaling() === false && $requestedHeight >= $originalDimensions->getHeight()) {
            return $originalDimensions;
        }

        $newDimensions = clone $originalDimensions;
        $newDimensions = $newDimensions->heighten($requestedHeight);

        return $newDimensions;
    }

    /**
     * Executes the actual resizing operation on the Imagine image.
     * In case of an outbound resize the image will be resized and cropped.
     *
     * @param ImagineImageInterface $image
     * @param string $mode
     * @param string $filter
     * @return \Imagine\Image\ManipulatorInterface
     */
    protected function resize(ImagineImageInterface $image, $mode = ImageInterface::RATIOMODE_INSET, $filter = ImagineImageInterface::FILTER_UNDEFINED)
    {
        if ($mode !== ImageInterface::RATIOMODE_INSET &&
            $mode !== ImageInterface::RATIOMODE_OUTBOUND
        ) {
            throw new \InvalidArgumentException('Invalid mode specified');
        }

        $imageSize = $image->getSize();
        $requestedDimensions = $this->calculateDimensions($imageSize);

        $image->strip();

        $resizeDimensions = $requestedDimensions;
        if ($mode === ImageInterface::RATIOMODE_OUTBOUND) {
            $resizeDimensions = $this->calculateOutboundScalingDimensions($imageSize, $requestedDimensions);
        }

        $image->resize($resizeDimensions, $filter);

        if ($mode === ImageInterface::RATIOMODE_OUTBOUND) {
            $image->crop(new Point(
                max(0, round(($resizeDimensions->getWidth() - $requestedDimensions->getWidth()) / 2)),
                max(0, round(($resizeDimensions->getHeight() - $requestedDimensions->getHeight()) / 2))
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
     */
    protected function calculateOutboundScalingDimensions(BoxInterface $imageSize, BoxInterface $requestedDimensions)
    {
        $ratios = array(
            $requestedDimensions->getWidth() / $imageSize->getWidth(),
            $requestedDimensions->getHeight() / $imageSize->getHeight()
        );

        return $imageSize->scale(max($ratios));
    }
}
