<?php
namespace Neos\Media\Domain\Model\ThumbnailGenerator;

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
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\Adjustment\ResizeDimensionCalculator;
use Neos\Media\Domain\Model\Adjustment\MarkPointAdjustment;
use Neos\Media\Domain\Model\Adjustment\QualityImageAdjustment;
use Neos\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use Neos\Media\Domain\Model\Dto\PreliminaryCropSpecification;
use Neos\Media\Domain\Model\FocalPointSupportInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Service\ImageService;
use Neos\Media\Exception;
use Neos\Media\Imagine\Box;

/**
 * A system-generated preview version of an Image
 */
class ImageThumbnailGenerator extends AbstractThumbnailGenerator
{
    /**
     * The priority for this thumbnail generator.
     *
     * @var integer
     * @api
     */
    protected static $priority = 5;

    /**
     * @var ImageService
     * @Flow\Inject
     */
    protected $imageService;

    /**
     * @param Thumbnail $thumbnail
     * @return boolean
     */
    public function canRefresh(Thumbnail $thumbnail)
    {
        return (
            $thumbnail->getOriginalAsset() instanceof ImageInterface
        );
    }

    /**
     * @param Thumbnail $thumbnail
     * @return void
     * @throws Exception\NoThumbnailAvailableException
     */
    public function refresh(Thumbnail $thumbnail)
    {
        try {
            $adjustments = [
                new ResizeImageAdjustment(
                    [
                        'width' => $thumbnail->getConfigurationValue('width'),
                        'maximumWidth' => $thumbnail->getConfigurationValue('maximumWidth'),
                        'height' => $thumbnail->getConfigurationValue('height'),
                        'maximumHeight' => $thumbnail->getConfigurationValue('maximumHeight'),
                        'ratioMode' => $thumbnail->getConfigurationValue('ratioMode'),
                        'allowUpScaling' => $thumbnail->getConfigurationValue('allowUpScaling'),
                    ]
                ),
                new QualityImageAdjustment(
                    [
                        'quality' => $thumbnail->getConfigurationValue('quality')
                    ]
                )
            ];

            $asset = $thumbnail->getOriginalAsset();
            $preliminaryCropSpecification = null;
            if ($asset instanceof FocalPointSupportInterface && $asset->hasFocalPoint()) {
                // in case we have a focal point we calculate the target dimension and add an
                // preliminary crop to ensure that the focal point stays inside the final image
                // while beeing as central as possible

                $originalFocalPoint = $asset->getFocalPoint();
                $originalDimensions = new Box($asset->getWidth(), $asset->getHeight());
                $requestedDimensions = ResizeDimensionCalculator::calculateRequestedDimensions(
                    originalDimensions: $originalDimensions,
                    width: $thumbnail->getConfigurationValue('width'),
                    maximumWidth: $thumbnail->getConfigurationValue('maximumWidth'),
                    height: $thumbnail->getConfigurationValue('height'),
                    maximumHeight: $thumbnail->getConfigurationValue('maximumHeight'),
                    ratioMode: $thumbnail->getConfigurationValue('ratioMode'),
                    allowUpScaling: $thumbnail->getConfigurationValue('allowUpScaling'),
                );

                $preliminaryCropSpecification = ResizeDimensionCalculator::calculatePreliminaryCropSpecification(
                    originalDimensions: $originalDimensions,
                    originalFocalPoint: $originalFocalPoint,
                    targetDimensions: $requestedDimensions,
                );

                $adjustments = array_merge(
                    [
                        new CropImageAdjustment(
                            [
                                'x' => $preliminaryCropSpecification->cropOffset->getX(),
                                'y' => $preliminaryCropSpecification->cropOffset->getY(),
                                'width' => $preliminaryCropSpecification->cropDimensions->getWidth(),
                                'height' => $preliminaryCropSpecification->cropDimensions->getHeight(),
                            ]
                        )
                    ],
                    $adjustments,
                    [
                        // this is for debugging purposes only
                        // @todo remove before merging
                        new MarkPointAdjustment(
                            [
                                'x' => $preliminaryCropSpecification->focalPoint->getX(),
                                'y' => $preliminaryCropSpecification->focalPoint->getY(),
                                'radius' => 5,
                                'color' => '#0f0',
                                'thickness' => 4
                            ]
                        ),
                    ]
                );
            }

            $targetFormat = $thumbnail->getConfigurationValue('format');
            $processedImageInfo = $this->imageService->processImage($thumbnail->getOriginalAsset()->getResource(), $adjustments, $targetFormat);

            $thumbnail->setResource($processedImageInfo['resource']);
            $thumbnail->setWidth($processedImageInfo['width']);
            $thumbnail->setHeight($processedImageInfo['height']);
            $thumbnail->setQuality($processedImageInfo['quality']);

            if ($preliminaryCropSpecification instanceof PreliminaryCropSpecification) {
                $thumbnail->setFocalPointX($preliminaryCropSpecification->focalPoint->getX());
                $thumbnail->setFocalPointY($preliminaryCropSpecification->focalPoint->getY());
            }
        } catch (\Exception $exception) {
            $message = sprintf('Unable to generate thumbnail for the given image (filename: %s, SHA1: %s)', $thumbnail->getOriginalAsset()->getResource()->getFilename(), $thumbnail->getOriginalAsset()->getResource()->getSha1());
            throw new Exception\NoThumbnailAvailableException($message, 1433109654, $exception);
        }
    }
}
