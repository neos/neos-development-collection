<?php
namespace Neos\Media\Domain\Service;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\CMYK;
use Imagine\Image\Palette\RGB;
use Imagine\Imagick\Imagine;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Utility\Environment;
use Neos\Media\Domain\Model\Adjustment\QualityImageAdjustment;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Imagine\Box;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Utility\Arrays;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;
use Neos\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;
use Neos\Media\Exception\ImageFileException;
use Neos\Media\Exception\ImageServiceException;

/**
 * An image service that acts as abstraction for the Imagine library
 *
 * @Flow\Scope("singleton")
 */
class ImageService
{
    /**
     * @var ImagineInterface
     * @Flow\Inject(lazy = false)
     */
    protected $imagineService;

    /**
     * @var ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var VariableFrontend
     * @Flow\Inject
     */
    protected $imageSizeCache;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array<string>
     */
    protected static $allowedFormats = ['jpg', 'jpeg', 'gif', 'png', 'wbmp', 'xbm', 'webp', 'bmp'];

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param PersistentResource $originalResource
     * @param array $adjustments
     * @param string $format
     * @return array resource, width, height as keys
     * @throws ImageFileException
     * @throws InvalidConfigurationException
     * @throws Exception
     */
    public function processImage(PersistentResource $originalResource, array $adjustments, string $format = null)
    {
        $additionalOptions = [];
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
        $fileExtension = $originalResource->getFileExtension();
        if ($format !== null
            && $originalResource->getFileExtension() !== $format
            && in_array($format, self::$allowedFormats, true)
        ) {
            $adjustmentsApplied = true;
            $fileExtension = $format;
        }
        $transformedImageTemporaryPathAndFilename = $this->environment->getPathToTemporaryDirectory() . 'ProcessedImage-' . Algorithms::generateRandomString(13) . '.' . $fileExtension;

        if (!file_exists($resourceUri)) {
            throw new ImageFileException(sprintf('An error occurred while transforming an image: the resource data of the original image does not exist (%s, %s).', $originalResource->getSha1(), $resourceUri), 1374848224);
        }

        $imagineImage = $this->imagineService->open($resourceUri);

        $convertCMYKToRGB = $this->getOptionsMergedWithDefaults()['convertCMYKToRGB'];
        if ($convertCMYKToRGB && $imagineImage->palette() instanceof CMYK) {
            $imagineImage->usePalette(new RGB());
        }

        if ($this->imagineService instanceof Imagine && $originalResource->getFileExtension() === 'gif' && $this->isAnimatedGif(file_get_contents($resourceUri)) === true) {
            $imagineImage->layers()->coalesce();
            $layers = $imagineImage->layers();
            $newLayers = [];
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

        $qualityAdjustments = array_filter($adjustments, function ($v) {
            return $v instanceof QualityImageAdjustment;
        }, ARRAY_FILTER_USE_BOTH);
        if (count($qualityAdjustments) > 0) {
            /** @var QualityImageAdjustment $qualityAdjustment */
            $qualityAdjustment = array_pop($qualityAdjustments);
            $quality = $qualityAdjustment->getQuality();
            if ($quality >= 1 && $quality <= 100) {
                $additionalOptions['quality'] = $qualityAdjustment->getQuality();
                $adjustmentsApplied = true;
            }
        }

        $additionalOptions = $this->getOptionsMergedWithDefaults($additionalOptions);

        if ($adjustmentsApplied === true) {
            $interlace = Arrays::getValueByPath($this->settings, 'image.defaultOptions.interlace');
            if ($interlace !== null) {
                $imagineImage->interlace($interlace);
            }
            $imagineImage->save($transformedImageTemporaryPathAndFilename, $additionalOptions);
            $imageSize = $imagineImage->getSize();

            // TODO: In the future the collectionName of the new resource should be configurable.
            $resource = $this->resourceManager->importResource($transformedImageTemporaryPathAndFilename, $originalResource->getCollectionName());
            if ($resource === false) {
                throw new ImageFileException('An error occurred while importing a generated image file as a resource.', 1413562208);
            }
            unlink($transformedImageTemporaryPathAndFilename);

            $pathInfo = UnicodeFunctions::pathinfo($originalResource->getFilename());
            $resource->setFilename(sprintf('%s-%ux%u.%s', $pathInfo['filename'], $imageSize->getWidth(), $imageSize->getHeight(), $fileExtension));
        } else {
            $originalResourceStream = $originalResource->getStream();
            $resource = $this->resourceManager->importResource($originalResourceStream, $originalResource->getCollectionName());
            fclose($originalResourceStream);
            $resource->setFilename($originalResource->getFilename());
            $imageSize = $this->getImageSize($originalResource);
            $imageSize = new Box($imageSize['width'], $imageSize['height']);
        }
        $this->imageSizeCache->set($resource->getCacheEntryIdentifier(), ['width' => $imageSize->getWidth(), 'height' => $imageSize->getHeight()]);

        $result = [
            'width' => $imageSize->getWidth(),
            'height' => $imageSize->getHeight(),
            'resource' => $resource,
            'quality' => $additionalOptions['quality']
        ];

        return $result;
    }

    /**
     * @param array $additionalOptions
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function getOptionsMergedWithDefaults(array $additionalOptions = [])
    {
        $defaultOptions = Arrays::getValueByPath($this->settings, 'image.defaultOptions');
        if (!is_array($defaultOptions)) {
            $defaultOptions = [];
        }
        if ($additionalOptions !== []) {
            $defaultOptions = Arrays::arrayMergeRecursiveOverrule($defaultOptions, $additionalOptions);
        }
        $quality = isset($defaultOptions['quality']) ? (integer)$defaultOptions['quality'] : 90;
        if ($quality < 0 || $quality > 100) {
            throw new InvalidConfigurationException(
                sprintf('Setting "Neos.Media.image.defaultOptions.quality" allow only value between 0 and 100, current value: %s', $quality),
                1404982574
            );
        }
        $defaultOptions['jpeg_quality'] = $quality;
        $defaultOptions['webp_quality'] = $quality;
        // png_compression_level should be an integer between 0 and 9 and inverse to the quality level given. So quality 100 should result in compression 0.
        $defaultOptions['png_compression_level'] = (9 - ceil($quality * 9 / 100));

        return $defaultOptions;
    }

    /**
     * Get the size of a Flow PersistentResource that contains an image file.
     *
     * @param PersistentResource $resource
     * @return array width and height as keys
     * @throws ImageFileException
     */
    public function getImageSize(PersistentResource $resource)
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

                $autorotateFilter = new \Imagine\Filter\Basic\Autorotate();
                $autorotateFilter->apply($imagineImage);

                $sizeBox = $imagineImage->getSize();
                $imageSize = ['width' => $sizeBox->getWidth(), 'height' => $sizeBox->getHeight()];
            } catch (\Exception $e) {
                throw new ImageFileException(sprintf('The given resource was not an image file your chosen driver can open. The original error was: %s', $e->getMessage()), 1336662898, $e);
            }
        }

        $this->imageSizeCache->set($cacheIdentifier, $imageSize);
        return $imageSize;
    }

    /**
     * @param ImageInterface $image
     * @param array $adjustments Ordered list of adjustments to apply.
     * @param boolean $adjustmentsApplied Reference to a variable that will hold information if an adjustment was actually applied.
     * @return ImageInterface
     * @throws ImageServiceException
     */
    protected function applyAdjustments(ImageInterface $image, array $adjustments, &$adjustmentsApplied)
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
