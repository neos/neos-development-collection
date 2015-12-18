<?php
namespace TYPO3\Media\Domain\Model\ThumbnailGenerator;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\Files;
use TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Service\ImageService;
use TYPO3\Media\Exception;

/**
 * A system-generated preview version of a font document.
 *
 * Format support depend on GD/FreeType 2 and your configuration (Settings.yaml)
 *
 * @see http://php.net/manual/en/function.imagefttext.php
 */
class FontDocumentThumbnailGenerator extends AbstractThumbnailGenerator
{
    /**
     * The priority for this thumbnail generator.
     *
     * @var integer
     * @api
     */
    protected static $priority = 5;

    /**
     * @Flow\Inject
     * @var ImageService
     */
    protected $imageService;

    /**
     * @param Thumbnail $thumbnail
     * @return boolean
     */
    public function canRefresh(Thumbnail $thumbnail)
    {
        return (
            $this->isExtensionSupported($thumbnail) &&
            function_exists('imagefttext')
        );
    }

    /**
     * @param Thumbnail $thumbnail
     * @return void
     * @throws Exception\NoThumbnailAvailableException
     */
    public function refresh(Thumbnail $thumbnail)
    {
        $temporaryPathAndFilename = null;
        try {
            $filename = pathinfo($thumbnail->getOriginalAsset()->getResource()->getFilename(), PATHINFO_FILENAME);

            $temporaryLocalCopyFilename = $thumbnail->getOriginalAsset()->getResource()->createTemporaryLocalCopy();
            $temporaryPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('ProcessedFontThumbnail-') . '.' . $filename . '.jpg';

            $width = 1000;
            $height = 1000;
            $im = imagecreate($width, $height);
            $red = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
            $black = imagecolorallocate($im, 0x00, 0x00, 0x00);

            imagefilledrectangle($im, 0, 0, 1000, 1000, $red);
            imagefttext($im, 48, 0, 80, 150, $black, $temporaryLocalCopyFilename, 'Neos Font Preview');
            imagefttext($im, 32, 0, 80, 280, $black, $temporaryLocalCopyFilename, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            imagefttext($im, 32, 0, 80, 360, $black, $temporaryLocalCopyFilename, 'abcdefghijklmopqrstuvwxyz');
            imagefttext($im, 32, 0, 80, 440, $black, $temporaryLocalCopyFilename, '1234567890');
            imagefttext($im, 32, 0, 80, 560, $black, $temporaryLocalCopyFilename, '+ " * ç % & / ( ) = ? @ €');

            imagejpeg($im, $temporaryPathAndFilename);

            $resource = $this->resourceManager->importResource($temporaryPathAndFilename);
            $processedImageInfo = $this->resize($thumbnail, $resource);

            $thumbnail->setResource($processedImageInfo['resource']);
            $thumbnail->setWidth($processedImageInfo['width']);
            $thumbnail->setHeight($processedImageInfo['height']);

            Files::unlink($temporaryPathAndFilename);
        } catch (\Exception $exception) {
            Files::unlink($temporaryPathAndFilename);
            $filename = $thumbnail->getOriginalAsset()->getResource()->getFilename();
            $sha1 = $thumbnail->getOriginalAsset()->getResource()->getSha1();
            $message = sprintf('Unable to generate thumbnail for the given font (filename: %s, SHA1: %s)', $filename, $sha1);
            throw new Exception\NoThumbnailAvailableException($message, 1433109653, $exception);
        }
    }

    /**
     * @param Thumbnail $thumbnail
     * @param Resource $resource
     * @return array
     * @throws Exception\ImageFileException
     */
    protected function resize(Thumbnail $thumbnail, Resource $resource)
    {
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

        return $this->imageService->processImage($resource, $adjustments);
    }
}
