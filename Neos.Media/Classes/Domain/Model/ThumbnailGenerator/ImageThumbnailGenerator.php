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
use Neos\Media\Domain\Model\Adjustment\QualityImageAdjustment;
use Neos\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Service\ImageService;
use Neos\Media\Exception;

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

            $targetFormat = $thumbnail->getConfigurationValue('format');
            $processedImageInfo = $this->imageService->processImage($thumbnail->getOriginalAsset()->getResource(), $adjustments, $targetFormat);

            $thumbnail->setResource($processedImageInfo['resource']);
            $thumbnail->setWidth($processedImageInfo['width']);
            $thumbnail->setHeight($processedImageInfo['height']);
            $thumbnail->setQuality($processedImageInfo['quality']);
        } catch (\Exception $exception) {
            $message = sprintf('Unable to generate thumbnail for the given image (filename: %s, SHA1: %s)', $thumbnail->getOriginalAsset()->getResource()->getFilename(), $thumbnail->getOriginalAsset()->getResource()->getSha1());
            throw new Exception\NoThumbnailAvailableException($message, 1433109654, $exception);
        }
    }
}
