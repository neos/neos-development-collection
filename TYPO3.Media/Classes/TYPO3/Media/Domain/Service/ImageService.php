<?php
namespace TYPO3\Media\Domain\Service;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Media\Imagine\Box;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\Exception\InvalidConfigurationException;
use TYPO3\Flow\Resource\Resource as FlowResource;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Unicode\Functions as UnicodeFunctions;
use TYPO3\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;
use TYPO3\Media\Exception\ImageFileException;
use TYPO3\Media\Exception\ImageServiceException;

/**
 * An image service that acts as abstraction for the Imagine library
 *
 * @Flow\Scope("singleton")
 */
class ImageService
{
    /**
     * @var \Imagine\Image\ImagineInterface
     * @Flow\Inject(lazy = false)
     */
    protected $imagineService;

    /**
     * @var \TYPO3\Flow\Resource\ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Utility\Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var \TYPO3\Media\Domain\Repository\AssetRepository
     */
    protected $assetRepository;

    /**
     * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
     * @Flow\Inject
     */
    protected $imageSizeCache;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param FlowResource $originalResource
     * @param array $adjustments
     * @return array resource, width, height as keys
     * @throws ImageFileException
     * @throws InvalidConfigurationException
     * @throws \TYPO3\Flow\Resource\Exception
     */
    public function processImage(FlowResource $originalResource, array $adjustments)
    {
        $additionalOptions = array();
        $adjustmentsApplied = false;

        // TODO: Special handling for SVG should be refactored at a later point.
        if ($originalResource->getMediaType() === 'image/svg+xml') {
            $originalResourceStream = $originalResource->getStream();
            $resource = $this->resourceManager->importResource($originalResourceStream, $originalResource->getCollectionName());
            fclose($originalResourceStream);
            $resource->setFilename($originalResource->getFilename());
            return [
                'width' => null,
                'height' => null,
                'resource' => $resource
            ];
        }

        $resourceUri = $originalResource->createTemporaryLocalCopy();

        $resultingFileExtension = $originalResource->getFileExtension();
        $transformedImageTemporaryPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('ProcessedImage-') . '.' . $resultingFileExtension;

        if (!file_exists($resourceUri)) {
            throw new ImageFileException(sprintf('An error occurred while transforming an image: the resource data of the original image does not exist (%s, %s).', $originalResource->getSha1(), $resourceUri), 1374848224);
        }

        $imagineImage = $this->imagineService->open($resourceUri);
        if ($this->imagineService instanceof \Imagine\Imagick\Imagine && $originalResource->getFileExtension() === 'gif' && $this->isAnimatedGif(file_get_contents($resourceUri)) === true) {
            $imagineImage->layers()->coalesce();
            $layers = $imagineImage->layers();
            $newLayers = array();
            foreach ($layers as $index => $imagineFrame) {
                $imagineFrame = $this->applyAdjustments($imagineFrame, $adjustments, $adjustmentsApplied);
                $newLayers[] = $imagineFrame;
            }

            $imagineImage = array_shift($newLayers);
            $layers = $imagineImage->layers();
            foreach ($newLayers as $imagineFrame) {
                $layers->add($imagineFrame);
            }
            $additionalOptions['animated'] = true;
        } else {
            $imagineImage = $this->applyAdjustments($imagineImage, $adjustments, $adjustmentsApplied);
        }

        if ($adjustmentsApplied === true) {
            $imagineImage->save($transformedImageTemporaryPathAndFilename, $this->getOptionsMergedWithDefaults($additionalOptions));
            $imageSize = $imagineImage->getSize();

            // TODO: In the future the collectionName of the new resource should be configurable.
            $resource = $this->resourceManager->importResource($transformedImageTemporaryPathAndFilename, $originalResource->getCollectionName());
            if ($resource === false) {
                throw new ImageFileException('An error occurred while importing a generated image file as a resource.', 1413562208);
            }
            unlink($transformedImageTemporaryPathAndFilename);

            $pathInfo = UnicodeFunctions::pathinfo($originalResource->getFilename());
            $resource->setFilename(sprintf('%s-%ux%u.%s', $pathInfo['filename'], $imageSize->getWidth(), $imageSize->getHeight(), $pathInfo['extension']));
        } else {
            $originalResourceStream = $originalResource->getStream();
            $resource = $this->resourceManager->importResource($originalResourceStream, $originalResource->getCollectionName());
            fclose($originalResourceStream);
            $resource->setFilename($originalResource->getFilename());
            $imageSize = $this->getImageSize($originalResource);
            $imageSize = new Box($imageSize['width'], $imageSize['height']);
        }
        $this->imageSizeCache->set($resource->getCacheEntryIdentifier(), array('width' => $imageSize->getWidth(), 'height' => $imageSize->getHeight()));

        $result = array(
            'width' => $imageSize->getWidth(),
            'height' => $imageSize->getHeight(),
            'resource' => $resource
        );

        return $result;
    }

    /**
     * @param array $additionalOptions
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function getOptionsMergedWithDefaults(array $additionalOptions = array())
    {
        $defaultOptions = Arrays::getValueByPath($this->settings, 'image.defaultOptions');
        if (!is_array($defaultOptions)) {
            $defaultOptions = array();
        }
        if ($additionalOptions !== array()) {
            $defaultOptions = Arrays::arrayMergeRecursiveOverrule($defaultOptions, $additionalOptions);
        }
        $quality = isset($defaultOptions['quality']) ? (integer)$defaultOptions['quality'] : 90;
        if ($quality < 0 || $quality > 100) {
            throw new InvalidConfigurationException(
                sprintf('Setting "TYPO3.Media.image.defaultOptions.quality" allow only value between 0 and 100, current value: %s', $quality),
                1404982574
            );
        }
        $defaultOptions['jpeg_quality'] = $quality;
        // png_compression_level should be an integer between 0 and 9 and inverse to the quality level given. So quality 100 should result in compression 0.
        $defaultOptions['png_compression_level'] = (9 - ceil($quality * 9 / 100));

        return $defaultOptions;
    }

    /**
     * Get the size of a Flow Resource object that contains an image file.
     *
     * @param FlowResource $resource
     * @return array width and height as keys
     * @throws ImageFileException
     */
    public function getImageSize(FlowResource $resource)
    {
        $cacheIdentifier = $resource->getCacheEntryIdentifier();

        $imageSize = $this->imageSizeCache->get($cacheIdentifier);
        if ($imageSize !== false) {
            return $imageSize;
        }

        // TODO: Special handling for SVG should be refactored at a later point.
        if ($resource->getMediaType() === 'image/svg+xml') {
            $imageSize = ['width' => null, 'height' => null];
        } else {
            try {
                $imagineImage = $this->imagineService->read($resource->getStream());
                $sizeBox = $imagineImage->getSize();
                $imageSize = array('width' => $sizeBox->getWidth(), 'height' => $sizeBox->getHeight());
            } catch (\Exception $e) {
                throw new ImageFileException(sprintf('The given resource was not an image file your choosen driver can open. The original error was: %s', $e->getMessage()), 1336662898);
            }
        }

        $this->imageSizeCache->set($cacheIdentifier, $imageSize);
        return $imageSize;
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array $adjustments Ordered list of adjustments to apply.
     * @param boolean $adjustmentsApplied Reference to a variable that will hold information if an adjustment was actually applied.
     * @return \Imagine\Image\ImageInterface
     * @throws ImageServiceException
     */
    protected function applyAdjustments(\Imagine\Image\ImageInterface $image, array $adjustments, &$adjustmentsApplied)
    {
        foreach ($adjustments as $adjustment) {
            if (!$adjustment instanceof ImageAdjustmentInterface) {
                throw new ImageServiceException(sprintf('Could not apply the %s adjustment to image because it does not implement the ImageAdjustmentInterface.', get_class($adjustment)), 1381400362);
            }
            if ($adjustment->canBeApplied($image)) {
                $image = $adjustment->applyToImage($image);
                $adjustmentsApplied = true;
            }
        }

        return $image;
    }

    /**
     * Detects whether the given GIF image data contains more than one frame
     *
     * @param string $image string containing the binary GIF data
     * @return boolean true if gif contains more than one frame
     */
    protected function isAnimatedGif($image)
    {
        $count = preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $image, $matches);
        return $count ? true : false;
    }
}
