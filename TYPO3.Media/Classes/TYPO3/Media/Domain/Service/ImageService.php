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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Configuration\Exception\InvalidConfigurationException;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Resource\ResourcePointer;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Exception\ImageFileException;
use TYPO3\Media\Exception\MissingResourceException;

/**
 * An image service that acts as abstraction for the Imagine library
 *
 * @Flow\Scope("singleton")
 */
class ImageService
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

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
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param ImageInterface $image
     * @param array $processingInstructions
     * @return Resource
     * @throws \Exception
     */
    public function transformImage(ImageInterface $image, array $processingInstructions)
    {
        if (!$image->getResource()) {
            throw new MissingResourceException('Image resource could not be found.', 1403195673);
        }
        if (!$image->getResource()->getResourcePointer()) {
            throw new MissingResourceException('Image resource pointer could not be found.', 1403195674);
        }
        $uniqueHash = sha1($image->getResource()->getResourcePointer()->getHash() . '|' . json_encode($processingInstructions));
        $additionalOptions = array();
        if (!file_exists('resource://' . $uniqueHash)) {
            $originalResourcePath = 'resource://' . $image->getResource()->getResourcePointer()->getHash();
            if (!file_exists($originalResourcePath)) {
                throw new MissingResourceException('Image resource file could not be found.', 1418243434);
            }

            /** @var \Imagine\Image\ImagineInterface $imagine */
            $imagine = $this->objectManager->get('Imagine\Image\ImagineInterface');
            $imageContent = file_get_contents($originalResourcePath);
            $imagineImage = $imagine->load($imageContent);
            if ($imagine instanceof \Imagine\Imagick\Imagine &&  $image->getFileExtension() === 'gif' && $this->isAnimatedGif($imageContent) === true) {
                $imagineImage->layers()->coalesce();
                foreach ($imagineImage->layers() as $imagineFrame) {
                    $this->applyProcessingInstructions($imagineFrame, $processingInstructions);
                }
                $additionalOptions['animated'] = true;
            } else {
                $imagineImage = $this->applyProcessingInstructions($imagineImage, $processingInstructions);
            }
            file_put_contents('resource://' . $uniqueHash, $imagineImage->get($image->getFileExtension(), $this->getDefaultOptions($additionalOptions)));
        }
        $resource = new Resource();
        $resource->setFilename($image->getResource()->getFilename());
        $resource->setResourcePointer(new ResourcePointer($uniqueHash));

        return $resource;
    }

    /**
     * @param array $additionalOptions
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function getDefaultOptions(array $additionalOptions = array())
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
     * @param Resource $resource
     * @return array width, height and image type
     * @throws ImageFileException
     */
    public function getImageSize(Resource $resource)
    {
        $cacheIdentifier = $resource->getResourcePointer()->getHash();
        if ($this->imageSizeCache->has($cacheIdentifier)) {
            return $this->imageSizeCache->get($cacheIdentifier);
        }
        $imageSize = getimagesize($resource->getUri());
        if ($imageSize === false) {
            throw new ImageFileException('The given resource was not a valid image file', 1336662898);
        }
        $imageSize = array(
            (integer)$imageSize[0],
            (integer)$imageSize[1],
            (integer)$imageSize[2]
        );
        $this->imageSizeCache->set($cacheIdentifier, $imageSize);
        return $imageSize;
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array $processingInstructions
     * @return \Imagine\Image\ImageInterface
     * @throws \InvalidArgumentException
     */
    protected function applyProcessingInstructions(\Imagine\Image\ImageInterface $image, array $processingInstructions)
    {
        foreach ($processingInstructions as $processingInstruction) {
            $commandName = $processingInstruction['command'];
            $commandMethodName = sprintf('%sCommand', $commandName);
            if (!is_callable(array($this, $commandMethodName))) {
                throw new \InvalidArgumentException('Invalid command "' . $commandName . '"', 1316613563);
            }
            $image = call_user_func(array($this, $commandMethodName), $image, $processingInstruction['options']);
        }
        return $image;
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array $commandOptions array('size' => ('width' => 123, 'height => 456), 'mode' => 'outbound')
     * @return \Imagine\Image\ImageInterface
     */
    protected function thumbnailCommand(\Imagine\Image\ImageInterface $image, array $commandOptions)
    {
        if (!isset($commandOptions['size'])) {
            throw new \InvalidArgumentException('The thumbnailCommand needs a "size" option.', 1393510202);
        }
        $dimensions = $this->parseBox($commandOptions['size']);
        if (isset($commandOptions['mode']) && $commandOptions['mode'] === ImageInterface::RATIOMODE_OUTBOUND) {
            $mode = \Imagine\Image\ManipulatorInterface::THUMBNAIL_OUTBOUND;
        } else {
            $mode = \Imagine\Image\ManipulatorInterface::THUMBNAIL_INSET;
        }
        return $image->thumbnail($dimensions, $mode);
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array $commandOptions array('size' => ('width' => 123, 'height => 456))
     * @return \Imagine\Image\ImageInterface
     */
    protected function resizeCommand(\Imagine\Image\ImageInterface $image, array $commandOptions)
    {
        if (!isset($commandOptions['size'])) {
            throw new \InvalidArgumentException('The resizeCommand needs a "size" option.', 1393510215);
        }
        $dimensions = $this->parseBox($commandOptions['size']);
        return $image->resize($dimensions);
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array $commandOptions array('start' => array('x' => 123, 'y' => 456), 'size' => array('width' => 123, 'height => 456))
     * @return \Imagine\Image\ImageInterface
     */
    protected function cropCommand(\Imagine\Image\ImageInterface $image, array $commandOptions)
    {
        if (!isset($commandOptions['start'])) {
            throw new \InvalidArgumentException('The cropCommand needs a "start" option.', 1393510229);
        }
        if (!isset($commandOptions['size'])) {
            throw new \InvalidArgumentException('The cropCommand needs a "size" option.', 1393510231);
        }
        $startPoint = $this->parsePoint($commandOptions['start']);
        $dimensions = $this->parseBox($commandOptions['size']);
        return $image->crop($startPoint, $dimensions);
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array $commandOptions
     * @return \Imagine\Image\ImageInterface
     */
    protected function drawCommand(\Imagine\Image\ImageInterface $image, array $commandOptions)
    {
        $drawer = $image->draw();
        foreach ($commandOptions as $drawCommandName => $drawCommandOptions) {
            if ($drawCommandName === 'ellipse') {
                $drawer = $this->drawEllipse($drawer, $drawCommandOptions);
            } elseif ($drawCommandName === 'text') {
                $drawer = $this->drawText($drawer, $drawCommandOptions);
            } else {
                throw new \InvalidArgumentException('Invalid draw command "' . $drawCommandName . '"', 1316613593);
            }
        }
        return $image;
    }

    /**
     * @param \Imagine\Draw\DrawerInterface $drawer
     * @param array $commandOptions
     * @return \Imagine\Draw\DrawerInterface
     */
    protected function drawEllipse(\Imagine\Draw\DrawerInterface $drawer, array $commandOptions)
    {
        $center = $this->parsePoint($commandOptions['center']);
        $size = $this->parseBox($commandOptions['size']);
        $color = $this->parseColor($commandOptions['color']);
        $fill = isset($commandOptions['fill']) ? (boolean)$commandOptions['fill'] : false;
        return $drawer->ellipse($center, $size, $color, $fill);
    }

    /**
     * @param \Imagine\Draw\DrawerInterface $drawer
     * @param array $commandOptions
     * @return \Imagine\Draw\DrawerInterface
     */
    protected function drawText(\Imagine\Draw\DrawerInterface $drawer, array $commandOptions)
    {
        $string = $commandOptions['string'];
        $font = $this->parseFont($commandOptions['font']);
        $position = $this->parsePoint($commandOptions['position']);
        $angle = (integer)$commandOptions['angle'];
        return $drawer->text($string, $font, $position, $angle);
    }

    /**
     * @param array $coordinates
     * @return \Imagine\Image\Point
     */
    protected function parsePoint($coordinates)
    {
        return $this->objectManager->get('Imagine\Image\Point', $coordinates['x'], $coordinates['y']);
    }

    /**
     * @param array $dimensions
     * @return \Imagine\Image\Box
     */
    protected function parseBox($dimensions)
    {
        return $this->objectManager->get('Imagine\Image\Box', $dimensions['width'], $dimensions['height']);
    }

    /**
     * @param array $color
     * @return \Imagine\Image\Color
     */
    protected function parseColor($color)
    {
        $alpha = isset($color['alpha']) ? (integer)$color['alpha'] : null;
        if ($alpha > 100) {
            $alpha = 100;
        }
        if ($alpha < 0) {
            $alpha = 0;
        }
        return $this->objectManager->get('Imagine\Image\Color', $color['color'], $alpha);
    }

    /**
     * @param array $options
     * @return \Imagine\Image\FontInterface
     */
    protected function parseFont($options)
    {
        $file = $options['file'];
        $size = $options['size'];
        $color = $this->parseColor($options['color']);
        return $this->objectManager->get('Imagine\Image\FontInterface', $file, $size, $color);
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
