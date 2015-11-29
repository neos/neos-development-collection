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

use Imagine\Image\ImagineInterface;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Exception;

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
     * @var \TYPO3\Flow\Utility\Environment
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
     * @Flow\InjectConfiguration(path="thumbnailGenerators", package="TYPO3.Media")
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
     * @param $key
     * @return mixed
     * @throws Exception
     */
    protected function getOption($key)
    {
        $key = trim($key);
        if ($key === '') {
            throw new Exception('Please provide a non empty option key', 1447766457);
        }
        $options = is_array($this->options) ? $this->options : [];
        $value = Arrays::getValueByPath($options, [get_called_class(), $key]);
        if ($value === null) {
            throw new Exception(sprintf('Option "%s" is not defined for "%s"', $key, get_called_class()), 1447766458);
        }
        return $value;
    }

    /**
     * @param Thumbnail $thumbnail
     * @return boolean TRUE if this ThumbnailGenerator can convert the given thumbnail, FALSE otherwise.
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
