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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Resource\Resource as FlowResource;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Utility\MediaTypes;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Service\ThumbnailService;

/**
 * An Asset, the base for all more specific assets in this package.
 *
 * It can be used as is to represent any asset for which no better match is available.
 *
 * @Flow\Entity
 * @ORM\InheritanceType("JOINED")
 */
class Asset implements AssetInterface
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var \DateTime
     */
    protected $lastModified;

    /**
     * @var string
     * @Flow\Validate(type="StringLength", options={ "maximum"=255 })
     */
    protected $title = '';

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $caption = '';

    /**
     * @var FlowResource
     * @ORM\OneToOne(orphanRemoval=true, cascade={"all"})
     */
    protected $resource;

    /**
     * @var \Doctrine\Common\Collections\Collection<\TYPO3\Media\Domain\Model\Thumbnail>
     * @ORM\OneToMany(orphanRemoval=true, cascade={"all"}, mappedBy="originalAsset")
     */
    protected $thumbnails;

    /**
     * @var \Doctrine\Common\Collections\Collection<\TYPO3\Media\Domain\Model\Tag>
     * @ORM\ManyToMany
     * @ORM\OrderBy({"label"="ASC"})
     * @Flow\Lazy
     */
    protected $tags;

    /**
     * @var \Doctrine\Common\Collections\Collection<\TYPO3\Media\Domain\Model\AssetCollection>
     * @ORM\ManyToMany(mappedBy="assets", cascade={"persist"})
     * @ORM\OrderBy({"title"="ASC"})
     * @Flow\Lazy
     */
    protected $assetCollections;

    /**
     * Constructs an asset. The resource is set internally and then initialize()
     * is called.
     *
     * @param FlowResource $resource
     */
    public function __construct(FlowResource $resource)
    {
        $this->tags = new ArrayCollection();
        $this->thumbnails = new ArrayCollection();
        $this->resource = $resource;
        $this->lastModified = new \DateTime();
        $this->assetCollections = new ArrayCollection();
        $this->emitAssetCreated($this);
    }

    /**
     * @param integer $initializationCause
     * @return void
     */
    public function initializeObject($initializationCause)
    {
        // FIXME: This is a workaround for after the resource management changes that introduced the property.
        if ($this->thumbnails === null) {
            $this->thumbnails = new ArrayCollection();
        }
    }

    /**
     * Override this to initialize upon instantiation.
     *
     * @return void
     */
    protected function initialize()
    {
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->persistenceManager->getIdentifierByObject($this);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        if (empty($this->title)) {
            return $this->getResource()->getFilename() ?: $this->getIdentifier();
        }
        return $this->getTitle();
    }

    /**
     * Returns the last modification timestamp for this asset
     *
     * @return \DateTime The date and time of last modification.
     * @api
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Sets the asset resource and (re-)initializes the asset.
     *
     * @param FlowResource $resource
     * @return void
     */
    public function setResource(FlowResource $resource)
    {
        $this->lastModified = new \DateTime();
        $this->resource = $resource;
        $this->refresh();
    }

    /**
     * Resource of the original file
     *
     * @return FlowResource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Returns a file extension fitting to the media type of this asset
     *
     * @return string
     */
    public function getFileExtension()
    {
        return MediaTypes::getFilenameExtensionFromMediaType($this->resource->getMediaType());
    }

    /**
     * Returns the IANA media type of this asset
     *
     * @return string
     */
    public function getMediaType()
    {
        return $this->resource->getMediaType();
    }

    /**
     * Sets the title of this image (optional)
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->lastModified = new \DateTime();
        $this->title = $title;
    }

    /**
     * The title of this image
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the caption of this asset (optional)
     *
     * @param string $caption
     * @return void
     */
    public function setCaption($caption)
    {
        $this->lastModified = new \DateTime();
        $this->caption = $caption;
    }

    /**
     * The caption of this asset
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * Return the tags assigned to this asset
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Add a single tag to this asset
     *
     * @param Tag $tag The tag to add
     * @return boolean TRUE if the tag added was new, FALSE if it already existed
     */
    public function addTag(Tag $tag)
    {
        if (!$this->tags->contains($tag)) {
            $this->lastModified = new \DateTime();
            $this->tags->add($tag);
            return true;
        }

        return false;
    }

    /**
     * Returns a thumbnail of this asset
     *
     * If the maximum width / height is not specified or exceeds the original asset's dimensions, the width / height of
     * the original asset is used.
     *
     * @param integer $maximumWidth The thumbnail's maximum width in pixels
     * @param integer $maximumHeight The thumbnail's maximum height in pixels
     * @param string $ratioMode Whether the resulting image should be cropped if both edge's sizes are supplied that would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting image should be upscaled
     * @return Thumbnail
     * @api
     */
    public function getThumbnail($maximumWidth = null, $maximumHeight = null, $ratioMode = ImageInterface::RATIOMODE_INSET, $allowUpScaling = null)
    {
        $thumbnailConfiguration = new ThumbnailConfiguration(null, $maximumWidth, null, $maximumHeight, $ratioMode === ImageInterface::RATIOMODE_OUTBOUND, $allowUpScaling);
        return $this->thumbnailService->getThumbnail($this, $thumbnailConfiguration);
    }

    /**
     * An internal method which adds a thumbnail which was generated by the ThumbnailService.
     *
     * @param Thumbnail $thumbnail
     * @return mixed
     * @see getThumbnail()
     */
    public function addThumbnail(Thumbnail $thumbnail)
    {
        $this->thumbnails->add($thumbnail);
    }

    /**
     * Refreshes this asset after the Resource or any other parameters affecting thumbnails have been modified
     *
     * @return void
     */
    public function refresh()
    {
        $assetClassType = str_replace('TYPO3\Media\Domain\Model\\', '', get_class($this));
        $this->systemLogger->log(sprintf('%s: refresh() called, clearing all thumbnails. Filename: %s. Resource SHA1: %s', $assetClassType, $this->getResource()->getFilename(), $this->getResource()->getSha1()), LOG_DEBUG);

        // whitelist objects so they can be deleted (even during safe requests)
        $this->persistenceManager->whitelistObject($this);
        foreach ($this->thumbnails as $thumbnail) {
            $this->persistenceManager->whitelistObject($thumbnail);
        }

        $this->thumbnails->clear();
    }

    /**
     * Set the tags assigned to this asset
     *
     * @param \Doctrine\Common\Collections\Collection $tags
     * @return void
     */
    public function setTags(\Doctrine\Common\Collections\Collection $tags)
    {
        $this->lastModified = new \DateTime();
        $this->tags = $tags;
    }

    /**
     * Remove a single tag from this asset
     *
     * @param Tag $tag
     * @return boolean
     */
    public function removeTag(Tag $tag)
    {
        if ($this->tags->contains($tag)) {
            $this->lastModified = new \DateTime();
            $this->tags->removeElement($tag);

            return true;
        }

        return false;
    }

    /**
     * Return the asset collections this asset is included in
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssetCollections()
    {
        return $this->assetCollections;
    }

    /**
     * Set the asset collections that include this asset
     *
     * @param \Doctrine\Common\Collections\Collection $assetCollections
     * @return void
     */
    public function setAssetCollections(\Doctrine\Common\Collections\Collection $assetCollections)
    {
        $this->lastModified = new \DateTime();
        foreach ($this->assetCollections as $existingAssetCollection) {
            $existingAssetCollection->removeAsset($this);
        }
        foreach ($assetCollections as $newAssetCollection) {
            $newAssetCollection->addAsset($this);
        }
        foreach ($this->assetCollections as $assetCollection) {
            if (!$assetCollections->contains($assetCollection)) {
                $assetCollections->add($assetCollection);
            }
        }
        $this->assetCollections = $assetCollections;
    }

    /**
     * Signals that an asset was created.
     *
     * @Flow\Signal
     * @param AssetInterface $asset
     * @return void
     */
    protected function emitAssetCreated(AssetInterface $asset)
    {
    }
}
