<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Exception;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Media\Domain\Service\ImageService;
use TYPO3\Media\Exception\ImageFileException;

/**
 * An image
 *
 * TODO: Remove duplicate code in Image and ImageVariant, by introducing a common base class or through Mixins/Traits (once they are available)
 *
 * @Flow\Entity
 */
class Image extends Asset implements ImageInterface
{
    /**
     * @Flow\Inject
     * @var ImageService
     */
    protected $imageService;

    /**
     * @var integer
     * @Flow\Validate(type="NotEmpty")
     */
    protected $width;

    /**
     * @var integer
     * @Flow\Validate(type="NotEmpty")
     */
    protected $height;

    /**
     * one of PHPs IMAGETYPE_* constants
     *
     * @var integer
     * @Flow\Validate(type="NotEmpty")
     */
    protected $type;

    /**
     * @fixme this should be a collection, but that is currently not serialized by Doctrine
     * @var array
     */
    protected $imageVariants = array();

    /**
     * @var boolean
     * @Flow\Transient
     */
    protected $imageSizeAndTypeInitialized = false;

    /**
     * If the object is recreated (that is, hydrated from persistence) the size and type is set to be initialized.
     *
     * @param integer $cause Why this object is initialized
     */
    public function initializeObject($cause)
    {
        if ($cause === ObjectManagerInterface::INITIALIZATIONCAUSE_RECREATED) {
            $this->imageSizeAndTypeInitialized = true;
        }
    }

    /**
     * Calculates image width, height and type from the image resource
     * The getimagesize() method may either return FALSE; or throw a Warning
     * which is translated to a \TYPO3\Flow\Error\Exception by Flow. In both
     * cases \TYPO3\Media\Exception\ImageFileException should be thrown.
     *
     * @throws ImageFileException
     * @return void
     */
    public function initializeImageSizeAndType()
    {
        try {
            if ($this->imageSizeAndTypeInitialized === true) {
                return;
            }
            list($this->width, $this->height, $this->type) = $this->imageService->getImageSize($this->resource);
            $this->imageSizeAndTypeInitialized = true;
        } catch (ImageFileException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $exceptionMessage = 'An error with code "' . $exception->getCode() . '" occurred when trying to read the image: "' . $exception->getMessage() . '"';
            throw new ImageFileException($exceptionMessage, 1336663970);
        }
    }

    /**
     * Width of the image in pixels
     *
     * @return integer
     */
    public function getWidth()
    {
        $this->initializeImageSizeAndType();

        return $this->width;
    }

    /**
     * Height of the image in pixels
     *
     * @return integer
     */
    public function getHeight()
    {
        $this->initializeImageSizeAndType();

        return $this->height;
    }

    /**
     * Edge / aspect ratio of the image
     *
     * @param boolean $respectOrientation If false (the default), orientation is disregarded and always a value >= 1 is returned (like usual in "4 / 3" or "16 / 9")
     * @return float
     */
    public function getAspectRatio($respectOrientation = false)
    {
        $aspectRatio = $this->getWidth() / $this->getHeight();
        if ($respectOrientation === false && $aspectRatio < 1) {
            $aspectRatio = 1 / $aspectRatio;
        }

        return $aspectRatio;
    }

    /**
     * Orientation of this image, i.e. portrait, landscape or square
     *
     * @return string One of this interface's ORIENTATION_* constants.
     */
    public function getOrientation()
    {
        $aspectRatio = $this->getAspectRatio(true);
        if ($aspectRatio > 1) {
            return ImageInterface::ORIENTATION_LANDSCAPE;
        } elseif ($aspectRatio < 1) {
            return ImageInterface::ORIENTATION_PORTRAIT;
        } else {
            return ImageInterface::ORIENTATION_SQUARE;
        }
    }

    /**
     * Whether this image is square aspect ratio and therefore has a square orientation
     *
     * @return boolean
     */
    public function isOrientationSquare()
    {
        return $this->getOrientation() === ImageInterface::ORIENTATION_SQUARE;
    }

    /**
     * Whether this image is in landscape orientation
     *
     * @return boolean
     */
    public function isOrientationLandscape()
    {
        return $this->getOrientation() === ImageInterface::ORIENTATION_LANDSCAPE;
    }

    /**
     * Whether this image is in portrait orientation
     *
     * @return boolean
     */
    public function isOrientationPortrait()
    {
        return $this->getOrientation() === ImageInterface::ORIENTATION_PORTRAIT;
    }

    /**
     * One of PHPs IMAGETYPE_* constants that reflects the image type
     *
     * @see http://php.net/manual/image.constants.php
     * @return integer
     */
    public function getType()
    {
        $this->initializeImageSizeAndType();

        return $this->type;
    }

    /**
     * File extension of the image without leading dot.
     *
     * @see http://www.php.net/manual/function.image-type-to-extension.php
     *
     * @return string
     */
    public function getFileExtension()
    {
        return image_type_to_extension($this->getType(), false);
    }

    /**
     * Returns a thumbnail of this image.
     *
     * If maximum width/height is not specified or exceed the original images size,
     * width/height of the original image is used
     *
     * Note: The image variant that will be created is intentionally not added to the
     * imageVariants collection of this image. If you want to create a persisted image
     * variant, use createImageVariant() instead.
     *
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @param string $ratioMode Whether the resulting image should be cropped if both edge's sizes are supplied that would hurt the aspect ratio.
     * @return \TYPO3\Media\Domain\Model\ImageVariant
     * @see \TYPO3\Media\Domain\Service\ImageService::transformImage()
     */
    public function getThumbnail($maximumWidth = null, $maximumHeight = null, $ratioMode = ImageInterface::RATIOMODE_INSET)
    {
        $processingInstructions = array(
            array(
                'command' => 'thumbnail',
                'options' => array(
                    'size' => array(
                        'width' => intval($maximumWidth ?: $this->width),
                        'height' => intval($maximumHeight ?: $this->height)
                    ),
                    'mode' => $ratioMode
                ),
            ),
        );

        return new ImageVariant($this, $processingInstructions);
    }

    /**
     * Set all variants of this image.
     *
     * @param array<\TYPO3\Media\Domain\Model\ImageVariant> $imageVariants
     * @return void
     */
    public function setImageVariants(array $imageVariants)
    {
        $this->imageVariants = $imageVariants;
    }

    /**
     * Return all variants of this image.
     *
     * @return array<\TYPO3\Media\Domain\Model\ImageVariant>
     */
    public function getImageVariants()
    {
        return $this->imageVariants;
    }

    /**
     * Create a variant of this image using the given processing instructions.
     *
     * The variant is attached to the image for later (re-)use. If the optional alias parameter is specified, the image
     * variant can later be retrieved via getImageVariantByAlias()
     * An alias could, for example, be "thumbnail", "small", "micro", "face-emphasized" etc.
     *
     * NOTE: If you want the new image variant to be persisted, make sure to update the image with ImageRepository::update()
     *
     * @param array $processingInstructions
     * @param string $alias An optional alias name to allow easier retrieving of a previously created image variant
     * @return \TYPO3\Media\Domain\Model\ImageVariant
     */
    public function createImageVariant(array $processingInstructions, $alias = null)
    {
        $imageVariant = new ImageVariant($this, $processingInstructions, $alias);
        // FIXME we currently need a unique hash because $this->imageVariants has to be an array in order to be serialized by Doctrine
        $uniqueHash = sha1($this->resource->getResourcePointer()->getHash() . '|' . ($alias ?: json_encode($processingInstructions)));
        $this->imageVariants[$uniqueHash] = $imageVariant;

        return $imageVariant;
    }

    /**
     * Remove the given variant from this image.
     *
     * NOTE: If you want to remove the image variant from persistence, make sure to update the image with ImageRepository::update()
     *
     * @param \TYPO3\Media\Domain\Model\ImageVariant $imageVariant
     * @return void
     */
    public function removeImageVariant(ImageVariant $imageVariant)
    {
        // FIXME we currently need a unique hash because $this->imageVariants has to be an array in order to be serialized by Doctrine
        $uniqueHash = sha1($this->resource->getResourcePointer()->getHash() . '|' . ($imageVariant->getAlias() ?: json_encode($imageVariant->getProcessingInstructions())));
        if (isset($this->imageVariants[$uniqueHash])) {
            unset($this->imageVariants[$uniqueHash]);
        }
    }

    /**
     * Gets an ImageVariant by its alias
     *
     * @param string $alias
     * @return \TYPO3\Media\Domain\Model\ImageVariant The ImageVariant if such found for the given alias, or NULL if not
     */
    public function getImageVariantByAlias($alias)
    {
        foreach ($this->imageVariants as $imageVariant) {
            if ($imageVariant->getAlias() === $alias) {
                return $imageVariant;
            }
        }

        return null;
    }

    /**
     * Removes an ImageVariant by its alias
     *
     * @param string $alias
     * @return void
     */
    public function removeImageVariantByAlias($alias)
    {
        $imageVariant = $this->getImageVariantByAlias($alias);
        if ($imageVariant instanceof ImageVariant) {
            $this->removeImageVariant($imageVariant);
        }
    }
}
