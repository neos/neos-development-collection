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

use Imagine\Image\ImagineInterface;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Exception;

/**
 * A system-generated preview version of a TTF font
 */
class FontDocumentThumbnailGenerator extends AbstractThumbnailGenerator
{
    /**
     * @var array
     */
    protected $supportedExtensions = array('ttf');

    /**
     * @param Thumbnail $thumbnail
     * @return boolean
     */
    public function canRefresh(Thumbnail $thumbnail)
    {
        return (
            $this->isExtensionSupported($thumbnail) &&
            function_exists('imagecreatetruecolor')
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
            $transformedImageTemporaryPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('ProcessedFontThumbnail-') . '.' . $filename . '.jpg';

            $im = imagecreatetruecolor(300, 205);
            $red = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
            $black = imagecolorallocate($im, 0x00, 0x00, 0x00);

            imagefilledrectangle($im, 0, 0, 299, 204, $red);
            imagefttext($im, 18, 0, 40, 50, $black, $temporaryLocalCopyFilename, 'Neos Font Preview');
            imagefttext($im, 13, 0, 40, 80, $black, $temporaryLocalCopyFilename, 'ABCDEFGHIJK');
            imagefttext($im, 13, 0, 40, 105, $black, $temporaryLocalCopyFilename, 'abcdefghijk');
            imagefttext($im, 13, 0, 40, 130, $black, $temporaryLocalCopyFilename, '1234567890');
            imagefttext($im, 12, 0, 40, 155, $black, $temporaryLocalCopyFilename, 'If something can corrupt you,');
            imagefttext($im, 12, 0, 40, 174, $black, $temporaryLocalCopyFilename, 'you\'re corrupted already.');

            imagejpeg($im, $transformedImageTemporaryPathAndFilename);

            $resource = $this->resourceManager->importResource($transformedImageTemporaryPathAndFilename);
            $thumbnail->setResource($resource, 300, 205);

            $this->unlinkTemporaryFiles([$transformedImageTemporaryPathAndFilename]);
        } catch (\Exception $exception) {
            $this->unlinkTemporaryFiles([$transformedImageTemporaryPathAndFilename]);
            throw new Exception\NoThumbnailAvailableException('Unable to generate thumbnail for the given font', 1433109671, $exception);
        }
    }
}
