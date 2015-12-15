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
use TYPO3\Flow\Utility\Files;
use TYPO3\Media\Domain\Model\Thumbnail;
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

            $width = 500;
            $height = 500;
            $im = imagecreate($width, $height);
            $red = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
            $black = imagecolorallocate($im, 0x00, 0x00, 0x00);

            imagefilledrectangle($im, 0, 0, 500, 500, $red);
            imagefttext($im, 24, 0, 40, 50, $black, $temporaryLocalCopyFilename, 'Neos Font Preview');
            imagefttext($im, 16, 0, 40, 85, $black, $temporaryLocalCopyFilename, 'ABCDEFGHIJK');
            imagefttext($im, 16, 0, 40, 115, $black, $temporaryLocalCopyFilename, 'abcdefghijk');
            imagefttext($im, 16, 0, 40, 145, $black, $temporaryLocalCopyFilename, '1234567890');

            imagejpeg($im, $temporaryPathAndFilename);

            $resource = $this->resourceManager->importResource($temporaryPathAndFilename);
            $thumbnail->setResource($resource);
            $thumbnail->setWidth($width);
            $thumbnail->setHeight($height);

            Files::unlink($temporaryPathAndFilename);
        } catch (\Exception $exception) {
            Files::unlink($temporaryPathAndFilename);
            $filename = $thumbnail->getOriginalAsset()->getResource()->getFilename();
            $sha1 = $thumbnail->getOriginalAsset()->getResource()->getSha1();
            $message = sprintf('Unable to generate thumbnail for the given font (filename: %s, SHA1: %s)', $filename, $sha1);
            throw new Exception\NoThumbnailAvailableException($message, 1433109653, $exception);
        }
    }
}
