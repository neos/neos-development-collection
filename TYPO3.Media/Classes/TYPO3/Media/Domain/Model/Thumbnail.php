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
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use TYPO3\Media\Exception;

/**
 * A system-generated preview version of an Asset
 *
 * @Flow\Entity
 * @ORM\Table(
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="originalasset_configurationhash",columns={"originalasset", "configurationhash"})
 *  },
 *  indexes={
 *      @ORM\Index(name="originalasset_configurationhash",columns={"originalasset", "configurationhash"})
 *  }
 * )
 */
class Thumbnail implements ImageInterface
{
    use DimensionsTrait;

    /**
     * @var \TYPO3\Media\Domain\Service\ImageService
     * @Flow\Inject
     */
    protected $imageService;

    /**
     * @var Asset
     * @ORM\ManyToOne(cascade={"persist", "merge"}, inversedBy="thumbnails")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $originalAsset;

    /**
     * @var \TYPO3\Flow\Resource\Resource
     * @ORM\OneToOne(orphanRemoval = true, cascade={"all"})
     * @ORM\JoinColumn(nullable=true)
     */
    protected $resource;

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
    protected $async;

    /**
     * Constructs a new Thumbnail
     *
     * @param AssetInterface $originalAsset The original asset this variant is derived from
     * @param ThumbnailConfiguration $configuration
     * @throws \TYPO3\Media\Exception
     */
    public function __construct(AssetInterface $originalAsset, ThumbnailConfiguration $configuration, $async = false)
    {
        if (!$originalAsset instanceof ImageInterface) {
            throw new Exception(sprintf('Support for creating thumbnails of other than Image assets has not been implemented yet (given asset was a %s)', get_class($originalAsset)), 1378132300);
        }
        $this->originalAsset = $originalAsset;
        $this->setConfiguration($configuration);
        $this->async = $async;
        $this->emitThumbnailCreated($this);
    }

    /**
     * Initializes this thumbnail
     *
     * @param integer $initializationCause
     * @return void
     */
    public function initializeObject($initializationCause)
    {
        if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED && $this->async === false) {
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
    protected function getConfigurationValue($value)
    {
        return Arrays::getValueByPath($this->configuration, $value);
    }

    /**
     * Refreshes this asset after the Resource has been modified
     *
     * @return void
     */
    public function refresh()
    {
        $adjustments = array(
            new ResizeImageAdjustment(
                array(
                    'width' => $this->getConfigurationValue('width'),
                    'maximumWidth' => $this->getConfigurationValue('maximumWidth'),
                    'height' => $this->getConfigurationValue('height'),
                    'maximumHeight' => $this->getConfigurationValue('maximumHeight'),
                    'ratioMode' => $this->getConfigurationValue('ratioMode'),
                    'allowUpScaling' => $this->getConfigurationValue('allowUpScaling')
                )
            )
        );

        $processedImageInfo = $this->imageService->processImage($this->originalAsset->getResource(), $adjustments);

        $this->resource = $processedImageInfo['resource'];
        $this->width = $processedImageInfo['width'];
        $this->height = $processedImageInfo['height'];
    }

    /**
     * Signals that a thumbnail was created.
     *
     * @Flow\Signal
     * @param Thumbnail $thumbnail
     * @return void
     */
    protected function emitThumbnailCreated(Thumbnail $thumbnail)
    {
    }
}
