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

use Imagine\Image\ImagineInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Utility\Environment;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Exception;

/**
 * Abstract Thumbnail Generator
 *
 * A Thumbnail Generator is used to generate thumbnail based on constraints, like priority, file extension, ... You
 * can implement your own Generator. The output of a Generator must be an image, check existing Generators for
 * inspiration.
 */
abstract class AbstractThumbnailGenerator implements ThumbnailGeneratorInterface
{
    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

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
     * The priority for this thumbnail generator.
     *
     * @var integer
     * @api
     */
    protected static $priority = 1;

    /**
     * @Flow\InjectConfiguration(path="thumbnailGenerators", package="Neos.Media")
     * @var array
     */
    protected $options;

    /**
     * @return integer
     * @api
     */
    public static function getPriority()
    {
        return static::$priority;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    protected function getOption($key)
    {
        $key = trim($key);
        if ($key === '') {
            throw new Exception('Please provide a non empty option key', 1447766457);
        }
        $className = static::class;
        $options = isset($this->options[$className]) ? $this->options[$className] : [];
        if (!isset($options[$key])) {
            throw new Exception(sprintf('Option "%s" is not defined for "%s"', $key, $className), 1447766458);
        }
        return $options[$key];
    }

    /**
     * @param Thumbnail $thumbnail
     * @return boolean true if this ThumbnailGenerator can convert the given thumbnail, false otherwise.
     * @api
     */
    public function canRefresh(Thumbnail $thumbnail)
    {
        return true;
    }

    /**
     * @param Thumbnail $thumbnail
     * @return boolean
     */
    protected function isExtensionSupported(Thumbnail $thumbnail)
    {
        $extension = $thumbnail->getOriginalAsset()->getResource()->getFileExtension();
        return in_array($extension, $this->getOption('supportedExtensions'));
    }
}
