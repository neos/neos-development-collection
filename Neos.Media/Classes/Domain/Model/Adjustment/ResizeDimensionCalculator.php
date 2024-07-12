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

use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use Imagine\Image\PointInterface;
use Neos\Media\Domain\Model\Dto\PreliminaryCropSpecification;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\ValueObject\Configuration\AspectRatio;
use Neos\Media\Imagine\Box;

/**
 * Container for static methods to calculate the target dimensions for resizing images
 *
 * @see: ResizeImageAdjustment, ImageThumbnailGenerator(to calculte a preliminary crop for images with focal point),
 *       ThumbnailService(to calculate dimensions and focal points for async thumbnails)
 *
 * @internal
 */
class ResizeDimensionCalculator
{
    /**
     * @param BoxInterface $originalDimensions
     * @param int|null $width
     * @param int|null $height
     * @param int|null $maximumWidth
     * @param int|null $maximumHeight
     * @param bool $allowUpScaling
     * @param string $ratioMode
     * @return BoxInterface
     */
    public static function calculateRequestedDimensions(
        BoxInterface $originalDimensions,
        ?int $width = null,
        ?int $height = null,
        ?int $maximumWidth = null,
        ?int $maximumHeight = null,
        bool $allowUpScaling = false,
        string $ratioMode = ImageInterface::RATIOMODE_INSET
    ): BoxInterface {
        $newDimensions = clone $originalDimensions;

        switch (true) {
            case ($width !== null && $height !== null): // height and width are set explicitly
                $newDimensions = self::calculateWithFixedDimensions($originalDimensions, $width, $height, $allowUpScaling, $ratioMode);
                break;
            case ($width !== null): // only width is set explicitly
                $newDimensions = self::calculateScalingToWidth($originalDimensions, $width, $allowUpScaling);
                break;
            case ($height !== null): // only height is set explicitly
                $newDimensions = self::calculateScalingToHeight($originalDimensions, $height, $allowUpScaling);
                break;
        }

        // We apply maximum dimensions and scale the new dimensions proportionally down to fit into the maximum.
        if ($maximumWidth !== null && $newDimensions->getWidth() > $maximumWidth) {
            $newDimensions = $newDimensions->widen($maximumWidth);
        }

        if ($maximumHeight !== null && $newDimensions->getHeight() > $maximumHeight) {
            $newDimensions = $newDimensions->heighten($maximumHeight);
        }

        return $newDimensions;
    }

    /**
     * @param BoxInterface $originalDimensions
     * @param integer $requestedWidth
     * @param integer $requestedHeight
     * @param bool $allowUpScaling
     * @param string $ratioMode
     * @return BoxInterface
     */
    protected static function calculateWithFixedDimensions(BoxInterface $originalDimensions, int $requestedWidth, int $requestedHeight, bool $allowUpScaling = false, string $ratioMode = ImageInterface::RATIOMODE_INSET): BoxInterface
    {
        if ($ratioMode === ImageInterface::RATIOMODE_OUTBOUND) {
            return self::calculateOutboundBox($originalDimensions, $requestedWidth, $requestedHeight, $allowUpScaling);
        }

        $newDimensions = clone $originalDimensions;

        $ratios = [
            $requestedWidth / $originalDimensions->getWidth(),
            $requestedHeight / $originalDimensions->getHeight()
        ];

        $ratio = min($ratios);
        $newDimensions = $newDimensions->scale($ratio);

        if ($allowUpScaling === false && $originalDimensions->contains($newDimensions) === false) {
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
     * @param bool $allowUpScaling
     * @return BoxInterface
     */
    protected static function calculateOutboundBox(BoxInterface $originalDimensions, int $requestedWidth, int $requestedHeight, bool $allowUpScaling): BoxInterface
    {
        $newDimensions = new Box($requestedWidth, $requestedHeight);

        if ($allowUpScaling === true || $originalDimensions->contains($newDimensions) === true) {
            return $newDimensions;
        }

        // We need to make sure that the new dimensions are such that no upscaling is needed.
        $ratios = [
            $originalDimensions->getWidth() / $requestedWidth,
            $originalDimensions->getHeight() / $requestedHeight
        ];

        $ratio = min($ratios);
        $newDimensions = $newDimensions->scale($ratio);

        return $newDimensions;
    }

    /**
     * Calculates new dimensions with a requested width applied. Takes upscaling into consideration.
     *
     * @param BoxInterface $originalDimensions
     * @param integer $requestedWidth
     * @param bool $allowUpScaling
     * @return BoxInterface
     */
    protected static function calculateScalingToWidth(BoxInterface $originalDimensions, int $requestedWidth, bool $allowUpScaling): BoxInterface
    {
        if ($allowUpScaling === false && $requestedWidth >= $originalDimensions->getWidth()) {
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
     * @param bool $allowUpScaling
     * @return BoxInterface
     */
    protected static function calculateScalingToHeight(BoxInterface $originalDimensions, int $requestedHeight, bool $allowUpScaling): BoxInterface
    {
        if ($allowUpScaling === false && $requestedHeight >= $originalDimensions->getHeight()) {
            return $originalDimensions;
        }

        $newDimensions = clone $originalDimensions;
        $newDimensions = $newDimensions->heighten($requestedHeight);

        return $newDimensions;
    }

    /**
     * Calculates a resize dimension box that allows for outbound resize.
     * The scaled image will be bigger than the requested dimensions in one dimension and then cropped.
     *
     * @param BoxInterface $imageSize
     * @param BoxInterface $requestedDimensions
     * @return BoxInterface
     */
    public static function calculateOutboundScalingDimensions(BoxInterface $imageSize, BoxInterface $requestedDimensions, string $ratioMode = ImageInterface::RATIOMODE_INSET): BoxInterface
    {
        if ($ratioMode === ImageInterface::RATIOMODE_OUTBOUND) {
            $ratios = [
                $requestedDimensions->getWidth() / $imageSize->getWidth(),
                $requestedDimensions->getHeight() / $imageSize->getHeight()
            ];

            return $imageSize->scale(max($ratios));
        }
        return $requestedDimensions;
    }

    /**
     * Calculate the informations for a preliminary crop to ensure that the given focal point stays inside the final image
     * with the requested dimensions
     *
     * - The cropDimensions have the aspect of requested dimensions and have the maximal possible dimensions
     * - The cropOffset will position the crop with the focal point as close to the center as possible
     * - The returned focal point is the position of the focal point after the crop inside the requested dimensions
     */
    public static function calculatePreliminaryCropSpecification(
        BoxInterface $originalDimensions,
        PointInterface $originalFocalPoint,
        BoxInterface $targetDimensions,
    ): PreliminaryCropSpecification {
        $originalAspect = new AspectRatio($originalDimensions->getWidth(), $originalDimensions->getHeight());
        $targetAspect = new AspectRatio($targetDimensions->getWidth(), $targetDimensions->getHeight());

        if ($originalAspect->getRatio() >= $targetAspect->getRatio()) {
            // target-aspect is wider as original-aspect or same: use full height, width is cropped
            $factor = $originalDimensions->getHeight() / $targetDimensions->getHeight();
            $cropDimensions = new \Imagine\Image\Box((int)($targetDimensions->getWidth() * $factor), $originalDimensions->getHeight());
            $cropOffsetX = $originalFocalPoint->getX() - (int)($cropDimensions->getWidth() / 2);
            $cropOffsetXMax = $originalDimensions->getWidth() - $cropDimensions->getWidth();
            if ($cropOffsetX < 0) {
                $cropOffsetX = 0;
            } elseif ($cropOffsetX > $cropOffsetXMax) {
                $cropOffsetX = $cropOffsetXMax;
            }
            $cropOffset = new Point($cropOffsetX, 0);
        } else {
            // target-aspect is higher than original-aspect: use full width, height is cropped
            $factor = $originalDimensions->getWidth() / $targetDimensions->getWidth();
            $cropDimensions = new Box($originalDimensions->getWidth(), (int)($targetDimensions->getHeight() * $factor));
            $cropOffsetY = $originalFocalPoint->getY() - (int)($cropDimensions->getHeight() / 2);
            $cropOffsetYMax = $originalDimensions->getHeight() - $cropDimensions->getHeight();
            if ($cropOffsetY < 0) {
                $cropOffsetY = 0;
            } elseif ($cropOffsetY > $cropOffsetYMax) {
                $cropOffsetY = $cropOffsetYMax;
            }
            $cropOffset = new Point(0, $cropOffsetY);
        }

        return new PreliminaryCropSpecification(
            $cropOffset,
            $cropDimensions,
            new Point(
                (int)round(($originalFocalPoint->getX() - $cropOffset->getX()) / $factor),
                (int)round(($originalFocalPoint->getY() - $cropOffset->getY()) / $factor)
            )
        );
    }
}
