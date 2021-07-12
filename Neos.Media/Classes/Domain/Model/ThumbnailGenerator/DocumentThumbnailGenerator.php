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

use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Exception;

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
            (
                $this->imagineService instanceof \Imagine\Imagick\Imagine ||
                $this->imagineService instanceof \Imagine\Gmagick\Imagine
            )
        );
    }

    /**
     * @param Thumbnail $thumbnail
     * @return void
     * @throws Exception\NoThumbnailAvailableException
     */
    public function refresh(Thumbnail $thumbnail)
    {
        $width = $thumbnail->getConfigurationValue('width') ?: $thumbnail->getConfigurationValue('maximumWidth');
        $height = $thumbnail->getConfigurationValue('height') ?: $thumbnail->getConfigurationValue('maximumHeight');

        $temporaryLocalCopyFilename = $thumbnail->getOriginalAsset()->getResource()->createTemporaryLocalCopy();
        $documentFile = sprintf(in_array($thumbnail->getOriginalAsset()->getResource()->getFileExtension(), $this->getOption('paginableDocuments'), true) ? '%s[0]' : '%s', $temporaryLocalCopyFilename);
        $filenameWithoutExtension = pathinfo($thumbnail->getOriginalAsset()->getResource()->getFilename(), PATHINFO_FILENAME);

        try {
            if ($this->imagineService instanceof \Imagine\Imagick\Imagine) {
                $magick = $this->refreshWithImagick($documentFile);
            } else {
                $magick = $this->refreshWithGmagick($documentFile);
            }

            $magick->thumbnailImage($width, $height, true);
            $resource = $this->resourceManager->importResourceFromContent($magick->getImageBlob(), $filenameWithoutExtension . '.png');
            $magick->destroy();
        } catch (\Exception $exception) {
            $filename = $thumbnail->getOriginalAsset()->getResource()->getFilename();
            $sha1 = $thumbnail->getOriginalAsset()->getResource()->getSha1();
            $message = sprintf('Unable to generate thumbnail for the given document (filename: %s, SHA1: %s)', $filename, $sha1);
            throw new Exception\NoThumbnailAvailableException($message, 1433109652, $exception);
        }

        $thumbnail->setResource($resource);
        $thumbnail->setWidth($width);
        $thumbnail->setHeight($height);
    }

    /**
     * @param string $documentFile
     * @return \Imagick
     * @throws Exception
     * @throws \ImagickException
     */
    protected function refreshWithImagick(string $documentFile): \Imagick
    {
        $magick = new \Imagick();
        $magick->setResolution($this->getOption('resolution'), $this->getOption('resolution'));
        $magick->readImage($documentFile);
        $magick->setImageFormat('png');
        $magick->setImageBackgroundColor('white');
        $magick->setImageCompose(\Imagick::COMPOSITE_OVER);

        if (method_exists($magick, 'mergeImageLayers')) {
            // Replace flattenImages in imagick 3.3.0
            // @see https://pecl.php.net/package/imagick/3.3.0RC2
            $magick = $magick->mergeImageLayers(\Imagick::LAYERMETHOD_MERGE);
        } else {
            $magick->flattenImages();
        }

        if (defined('\Imagick::ALPHACHANNEL_OFF')) {
            // ImageMagick >= 7.0, Imagick >= 3.4.3RC1
            // @see https://pecl.php.net/package/imagick/3.4.3RC1
            $magick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_OFF);
        } else {
            $magick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_RESET);
        }

        return $magick;
    }

    /**
     * @param string $documentFile
     * @return \Gmagick
     * @throws Exception
     * @throws \GmagickException
     */
    protected function refreshWithGmagick(string $documentFile): \Gmagick
    {
        $magick = new \Gmagick();
        $magick->setResolution($this->getOption('resolution'), $this->getOption('resolution'));
        $magick->readImage($documentFile);
        $magick->setImageFormat('png');
        $magick->setImageBackgroundColor(new \GmagickPixel('white'));
        $magick->setImageCompose(\Gmagick::COMPOSITE_OVER);

        if (method_exists($magick, 'mergeImageLayers')) {
            $magick->mergeImageLayers(\Gmagick::LAYERMETHOD_MERGE);
        } else {
            $magick->flattenImages();
        }

        return $magick;
    }
}
