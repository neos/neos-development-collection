<?php
namespace TYPO3\Media\Domain\Model\ThumbnailGenerator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Service\ImageService;
use TYPO3\Media\Exception;

/**
 * A system-generated preview version of an Image
 */
class ImageThumbnailGenerator extends AbstractThumbnailGenerator
{
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
            $adjustments = array(
                new ResizeImageAdjustment(
                    array(
                        'width' => $thumbnail->getConfigurationValue('width'),
                        'maximumWidth' => $thumbnail->getConfigurationValue('maximumWidth'),
                        'height' => $thumbnail->getConfigurationValue('height'),
                        'maximumHeight' => $thumbnail->getConfigurationValue('maximumHeight'),
                        'ratioMode' => $thumbnail->getConfigurationValue('ratioMode'),
                        'allowUpScaling' => $thumbnail->getConfigurationValue('allowUpScaling'),
                    )
                )
            );

            $processedImageInfo = $this->imageService->processImage($thumbnail->getOriginalAsset()->getResource(), $adjustments);

            $thumbnail->setResource($processedImageInfo['resource'], $processedImageInfo['width'], $processedImageInfo['height']);
        } catch (\Exception $exception) {
            throw new Exception\NoThumbnailAvailableException('Unable to generate thumbnail for the given image', 1433109566, $exception);
        }
    }
}
