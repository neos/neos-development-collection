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
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Exception;

/**
 * A system-generated preview version of a Document (PDF, AI and EPS)
 */
class DocumentThumbnailGenerator extends AbstractThumbnailGenerator
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
        try {
            $filenameWithoutExtension = pathinfo($thumbnail->getOriginalAsset()->getResource()->getFilename(), PATHINFO_FILENAME);

            $temporaryLocalCopyFilename = $thumbnail->getOriginalAsset()->getResource()->createTemporaryLocalCopy();

            $documentFile = sprintf(in_array($thumbnail->getOriginalAsset()->getResource()->getFileExtension(), $this->getOption('paginableDocuments')) ? '%s[0]' : '%s', $temporaryLocalCopyFilename);

            $width = $thumbnail->getConfigurationValue('width') ?: $thumbnail->getConfigurationValue('maximumWidth');
            $height = $thumbnail->getConfigurationValue('height') ?: $thumbnail->getConfigurationValue('maximumHeight');

            $im = new \Imagick();
            $im->setResolution($this->getOption('resolution'), $this->getOption('resolution'));
            $im->readImage($documentFile);
            $im->setImageFormat('png');
            $im->setImageBackgroundColor('white');
            $im->setImageCompose(\Imagick::COMPOSITE_OVER);
            $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_RESET);
            $im->thumbnailImage($width, $height, true);

            $im->flattenImages();
            // Replace flattenImages in imagick 3.3.0
            // @see https://pecl.php.net/package/imagick/3.3.0RC2
            // $im->mergeImageLayers(\Imagick::LAYERMETHOD_MERGE);

            $resource = $this->resourceManager->importResourceFromContent($im->getImageBlob(), $filenameWithoutExtension . '.png');
            $im->destroy();

            $thumbnail->setResource($resource);
            $thumbnail->setWidth($width);
            $thumbnail->setHeight($height);
        } catch (\Exception $exception) {
            $filename = $thumbnail->getOriginalAsset()->getResource()->getFilename();
            $sha1 = $thumbnail->getOriginalAsset()->getResource()->getSha1();
            $message = sprintf('Unable to generate thumbnail for the given document (filename: %s, SHA1: %s)', $filename, $sha1);
            throw new Exception\NoThumbnailAvailableException($message, 1433109652, $exception);
        }
    }
}
