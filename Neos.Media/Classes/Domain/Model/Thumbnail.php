<?php
namespace Neos\Media\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Utility\Arrays;
use Neos\Media\Domain\Strategy\ThumbnailGeneratorStrategy;

/**
 * A system-generated preview version of an Asset
 *
 * @Flow\Entity
 * @ORM\Table(
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="originalasset_configurationhash",columns={"originalasset", "configurationhash"})
 *  }
 * )
 */
class Thumbnail implements ImageInterface
{
    use DimensionsTrait;
    use QualityTrait;

    /**
     * @var ThumbnailGeneratorStrategy
     * @Flow\Inject
     */
    protected $generatorStrategy;

    /**
     * @var ThumbnailService
     * @Flow\Inject
     */
    protected $thumbnailService;

    /**
     * @var Asset
     * @ORM\ManyToOne(cascade={"persist", "merge"}, inversedBy="thumbnails")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $originalAsset;

    /**
     * @var PersistentResource
     * @ORM\OneToOne(orphanRemoval = true, cascade={"all"})
     * @ORM\JoinColumn(nullable=true)
     */
    protected $resource;

    /**
     * @var string Supports the 'resource://Package.Key/Public/File' format
     * @ORM\Column(nullable=true)
     */
    protected $staticResource;

    /**
     * @var array<string>
     * @ORM\Column(type="flow_json_array")
     */
    protected $configuration;

    /**
     * @var string
     * @ORM\Column(length=32)
     */
    protected $configurationHash;

    /**
     * @var bool
     * @Flow\Transient
     */
    protected $async;

    /**
     * Constructs a new Thumbnail
     *
     * @param AssetInterface $originalAsset The original asset this variant is derived from
     * @param ThumbnailConfiguration $configuration
     * @throws \Neos\Media\Exception
     */
    public function __construct(AssetInterface $originalAsset, ThumbnailConfiguration $configuration)
    {
        $this->originalAsset = $originalAsset;
        $this->setConfiguration($configuration);
        $this->async = $configuration->isAsync();
    }

    /**
     * Initializes this thumbnail
     *
     * @param integer $initializationCause
     * @return void
     */
    public function initializeObject($initializationCause)
    {
        if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
            if ($this->async === false) {
                $this->refresh();
            }
        }
    }

    /**
     * Post persistence lifecycle callback
     *
     * @ORM\PostPersist
     */
    public function onPostPersist()
    {
        $this->thumbnailService->emitThumbnailPersisted($this);
    }

    /**
     * Returns the Asset this thumbnail is derived from
     *
     * @return ImageInterface
     */
    public function getOriginalAsset()
    {
        return $this->originalAsset;
    }

    /**
     * @param ThumbnailConfiguration $configuration
     * @return void
     */
    protected function setConfiguration(ThumbnailConfiguration $configuration)
    {
        $this->configuration = $configuration->toArray();
        $this->configurationHash = $configuration->getHash();
    }

    /**
     * @param string $value
     * @return mixed
     */
    public function getConfigurationValue($value)
    {
        return Arrays::getValueByPath($this->configuration, $value);
    }

    /**
     * PersistentResource of this thumbnail
     *
     * @return PersistentResource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param PersistentResource $resource
     * @return void
     */
    public function setResource(PersistentResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getStaticResource()
    {
        return $this->staticResource;
    }

    /**
     * @param string $staticResource
     * @return void
     */
    public function setStaticResource($staticResource)
    {
        $this->staticResource = $staticResource;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setWidth($width)
    {
        $this->width = (integer)$width;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height)
    {
        $this->height = (integer)$height;
    }

    /**
     * @param integer $quality
     * @return void
     */
    public function setQuality($quality)
    {
        $this->quality = (integer)$quality;
    }

    /**
     * Refreshes this asset after the PersistentResource has been modified
     *
     * @return void
     */
    public function refresh()
    {
        $this->generatorStrategy->refresh($this);
    }
}
