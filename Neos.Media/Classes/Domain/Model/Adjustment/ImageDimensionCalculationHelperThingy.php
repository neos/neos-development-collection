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
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Imagine\Box;

class ImageDimensionCalculationHelperThingy
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
    public static function calculateFinalDimensions(BoxInterface $imageSize, BoxInterface $requestedDimensions, string $ratioMode = ImageInterface::RATIOMODE_INSET): BoxInterface
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
}
