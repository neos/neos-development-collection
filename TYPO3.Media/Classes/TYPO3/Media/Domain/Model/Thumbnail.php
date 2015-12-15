<?php
namespace TYPO3\Media\Domain\Model;

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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Media\Domain\Strategy\ThumbnailGeneratorStrategy;
use TYPO3\Media\Exception;

/**
 * A system-generated preview version of an Asset
 *
 * @Flow\Entity
 * @ORM\Table(
 *    indexes={
 * 		@ORM\Index(name="originalasset_configurationhash",columns={"originalasset", "configurationhash"})
 *    }
 * )
 */
class Thumbnail implements ImageInterface
{
    use DimensionsTrait;

    /**
     * @var ThumbnailGeneratorStrategy
     * @Flow\Inject
     */
    protected $generatorStrategy;

    /**
     * @var Asset
     * @ORM\ManyToOne(cascade={"persist", "merge"}, inversedBy="thumbnails")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $originalAsset;

    /**
     * @var Resource
     * @ORM\OneToOne(orphanRemoval = true, cascade={"all"})
     * @Flow\Validate(type = "NotEmpty")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $resource;

    /**
     * @var string
     * @Flow\Transient
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
     * @var boolean
     * @Flow\Transient
     */
    protected $transient = false;

    /**
     * Constructs a new Thumbnail
     *
     * @param AssetInterface $originalAsset The original asset this variant is derived from
     * @param ThumbnailConfiguration $configuration
     * @throws \TYPO3\Media\Exception
     */
    public function __construct(AssetInterface $originalAsset, ThumbnailConfiguration $configuration)
    {
        $this->originalAsset = $originalAsset;
        $this->setConfiguration($configuration);
    }

    /**
     * Initializes this thumbnail
     *
     * @param integer $initializationCause
     */
    public function initializeObject($initializationCause)
    {
        if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
            $this->refresh();
        }
    }

    /**
     * Returns the Asset this thumbnail is derived from
     *
     * @return \TYPO3\Media\Domain\Model\ImageInterface
     */
    public function getOriginalAsset()
    {
        return $this->originalAsset;
    }

    /**
     * Resource of this thumbnail
     *
     * @return Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param ThumbnailConfiguration $configuration
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
     * @param Resource $resource
     * @return void
     */
    public function setResource(Resource $resource)
    {
        $this->resource = $resource;
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
     * @return boolean
     */
    public function isTransient()
    {
        return $this->transient;
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
        $this->transient = true;
    }

    /**
     * Refreshes this asset after the Resource has been modified
     *
     * @return void
     */
    public function refresh()
    {
        $this->generatorStrategy->refresh($this);
    }
}
