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
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Exception;

/**
 * A system-generated preview version of an Pdf
 */
class DocumentThumbnailGenerator extends AbstractThumbnailGenerator
{
    /**
     * @var array
     */
    protected $supportedExtensions = array('pdf', 'eps', 'ai');

    /**
     * @param Thumbnail $thumbnail
     * @return boolean
     */
    public function canRefresh(Thumbnail $thumbnail)
    {
        return (
            $thumbnail->getOriginalAsset() instanceof Document &&
            $this->isExtensionSupported($thumbnail) &&
            $this->imagineService instanceof \Imagine\Imagick\Imagine
        );
    }

    /**
     * @param Thumbnail $thumbnail
     * @return void
     * @throws Exception\NoThumbnailAvailableException
     */
    public function refresh(Thumbnail $thumbnail)
    {
        $transformedImageTemporaryPathAndFilename = null;
        try {
            $filename = pathinfo($thumbnail->getOriginalAsset()->getResource()->getFilename(), PATHINFO_FILENAME);

            $temporaryLocalCopyFilename = $thumbnail->getOriginalAsset()->getResource()->createTemporaryLocalCopy();
            $transformedImageTemporaryPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('ProcessedDocumentThumbnail-') . '.' . $filename . '.png';

            if ($this->currentExtension === 'pdf') {
                // Just to be sure we extract only the first page of the PDF
                $imageFile = sprintf('%s[0]', $temporaryLocalCopyFilename);
            } else {
                $imageFile = sprintf('%s', $temporaryLocalCopyFilename);
            }

            $imagick = new \Imagick();
            $imagick->setResolution($this->options['resolution'], $this->options['resolution']);
            $imagick->readImage($imageFile);
            $imagick->setImageFormat('png');
            $imagick->setImageBackgroundColor(new \ImagickPixel('white'));
            $imagick->setImageCompose(\Imagick::COMPOSITE_OVER);
            $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_RESET);
            $imagick->thumbnailImage($thumbnail->getConfigurationValue('maximumWidth') ?: 1000, $thumbnail->getConfigurationValue('maximumHeight') ?: 1000, true);
            $imagick->flattenImages();
            $imagick->writeImage($transformedImageTemporaryPathAndFilename);

            $resource = $this->resourceManager->importResource($transformedImageTemporaryPathAndFilename);
            $thumbnail->setResource($resource, $imagick->getImageWidth(), $imagick->getImageHeight());

            $this->unlinkTemporaryFiles([$transformedImageTemporaryPathAndFilename]);
        } catch (\Exception $exception) {
            $this->unlinkTemporaryFiles([$transformedImageTemporaryPathAndFilename]);
            throw new Exception\NoThumbnailAvailableException('Unable to generate thumbnail for the given document', 1433109652, $exception);
        }
    }
}
