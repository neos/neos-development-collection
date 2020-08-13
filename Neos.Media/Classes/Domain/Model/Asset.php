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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetSource\AssetNotFoundExceptionInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceConnectionExceptionInterface;
use Neos\Media\Domain\Repository\ImportedAssetRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\ThumbnailService;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
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
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject()
     * @var ImportedAssetRepository
     */
    protected $importedAssetRepository;

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
     * @var string
     * @ORM\Column(type="text")
     */
    protected $copyrightNotice = '';

    /**
     * @var PersistentResource
     * @ORM\OneToOne(orphanRemoval=true, cascade={"all"})
     */
    protected $resource;

    /**
     * @var Collection<\Neos\Media\Domain\Model\Thumbnail>
     * @ORM\OneToMany(orphanRemoval=true, cascade={"all"}, mappedBy="originalAsset")
     */
    protected $thumbnails;

    /**
     * @var Collection<\Neos\Media\Domain\Model\Tag>
     * @ORM\ManyToMany(cascade={"persist"})
     * @ORM\OrderBy({"label"="ASC"})
     * @Flow\Lazy
     */
    protected $tags;

    /**
     * @var Collection<\Neos\Media\Domain\Model\AssetCollection>
     * @ORM\ManyToMany(mappedBy="assets", cascade={"persist"})
     * @ORM\OrderBy({"title"="ASC"})
     * @Flow\Lazy
     */
    protected $assetCollections;

    /**
     * @var string
     */
    public $assetSourceIdentifier = 'neos';

    /**
     * @Flow\InjectConfiguration(path="assetSources")
     * @var array
     */
    protected $assetSourcesConfiguration;

    /**
     * @Flow\Transient()
     * @var AssetSourceInterface[]
     */
    protected $assetSources = [];

    /**
     * Constructs an asset. The resource is set internally and then initialize()
     * is called.
     *
     * @param PersistentResource $resource
     * @throws \Exception
     */
    public function __construct(PersistentResource $resource)
    {
        $this->tags = new ArrayCollection();
        $this->thumbnails = new ArrayCollection();
        $this->resource = $resource;
        $this->lastModified = new \DateTime();
        $this->assetCollections = new ArrayCollection();
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
     * @param PersistentResource $resource
     * @return void
     */
    public function setResource(PersistentResource $resource)
    {
        $this->lastModified = new \DateTime();
        $this->resource = $resource;
        $this->refresh();
    }

    /**
     * PersistentResource of the original file
     *
     * @return PersistentResource
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
        return $this->resource->getFileExtension();
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
     * @return string
     */
    public function getCopyrightNotice(): string
    {
        return $this->copyrightNotice;
    }

    /**
     * @param string $copyrightNotice
     */
    public function setCopyrightNotice(string $copyrightNotice): void
    {
        $this->copyrightNotice = $copyrightNotice;
    }

    /**
     * Return the tags assigned to this asset
     *
     * @return Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Add a single tag to this asset
     *
     * @param Tag $tag The tag to add
     * @return boolean true if the tag added was new, false if it already existed
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
     * @throws \Exception
     * @api
     * @throws \Exception
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
     * @return void
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
        $assetClassType = str_replace('Neos\Media\Domain\Model\\', '', get_class($this));
        $this->systemLogger->debug(sprintf('%s: refresh() called, clearing all thumbnails. Filename: %s. PersistentResource SHA1: %s', $assetClassType, $this->getResource()->getFilename(), $this->getResource()->getSha1()));

        // allow objects so they can be deleted (even during safe requests)
        $this->persistenceManager->allowObject($this);
        foreach ($this->thumbnails as $thumbnail) {
            $this->persistenceManager->allowObject($thumbnail);
        }

        $this->thumbnails->clear();
    }

    /**
     * Set the tags assigned to this asset
     *
     * @param Collection $tags
     * @return void
     */
    public function setTags(Collection $tags)
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
     * @return Collection
     */
    public function getAssetCollections()
    {
        return $this->assetCollections;
    }

    /**
     * Set the asset collections that include this asset
     *
     * @param Collection $assetCollections
     * @return void
     */
    public function setAssetCollections(Collection $assetCollections)
    {
        $this->lastModified = new \DateTime();
        foreach ($this->assetCollections as $existingAssetCollection) {
            if (!$assetCollections->contains($existingAssetCollection)) {
                $existingAssetCollection->removeAsset($this);
            }
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
     * Set the asset source identifier for this asset
     *
     * This is an internal method which allows Neos / Flow to keep track of assets which were imported from
     * external asset sources.
     *
     * @param string $assetSourceIdentifier
     */
    public function setAssetSourceIdentifier(string $assetSourceIdentifier): void
    {
        $this->assetSourceIdentifier = $assetSourceIdentifier;
    }

    /**
     * @return string
     */
    public function getAssetSourceIdentifier(): string
    {
        return $this->assetSourceIdentifier;
    }

    /**
     * @return AssetProxyInterface|null
     */
    public function getAssetProxy(): ?AssetProxyInterface
    {
        $assetSource = $this->getAssetSource();
        if ($assetSource === null) {
            $this->systemLogger->notice(sprintf('Asset %s: Invalid asset source "%s"', $this->getIdentifier(), $this->getAssetSourceIdentifier()), LogEnvironment::fromMethodName(__METHOD__));
            return null;
        }
        $importedAsset = $this->importedAssetRepository->findOneByLocalAssetIdentifier($this->getIdentifier());
        if ($importedAsset === null) {
            $this->systemLogger->notice(sprintf('Asset %s: Imported asset not found for asset source %s (%s)', $this->getIdentifier(), $assetSource->getIdentifier(), $assetSource->getLabel()), LogEnvironment::fromMethodName(__METHOD__));
            return null;
        }

        try {
            if ($importedAsset instanceof ImportedAsset) {
                return $assetSource->getAssetProxyRepository()->getAssetProxy($importedAsset->getRemoteAssetIdentifier());
            } else {
                return $assetSource->getAssetProxyRepository()->getAssetProxy($this->getIdentifier());
            }
        } catch (AssetNotFoundExceptionInterface $e) {
            $this->systemLogger->notice(sprintf('Asset %s: Not found in asset source %s (%s)', $this->getIdentifier(), $assetSource->getIdentifier(), $assetSource->getLabel()), LogEnvironment::fromMethodName(__METHOD__));
            return null;
        } catch (AssetSourceConnectionExceptionInterface $e) {
            $this->systemLogger->notice(sprintf('Asset %s: Failed connecting to asset source %s (%s): %s', $this->getIdentifier(), $assetSource->getIdentifier(), $assetSource->getLabel(), $e->getMessage()), LogEnvironment::fromMethodName(__METHOD__));
            return null;
        }
    }

    /**
     * Returns true if the asset is still in use.
     *
     * @return boolean
     * @api
     */
    public function isInUse()
    {
        return $this->assetService->isInUse($this);
    }

    /**
     * Returns the number of times the asset is in use.
     *
     * @return integer
     * @api
     */
    public function getUsageCount()
    {
        return $this->assetService->getUsageCount($this);
    }

    /**
     * @return AssetSourceInterface|null
     */
    private function getAssetSource(): ?AssetSourceInterface
    {
        if ($this->assetSources === []) {
            foreach ($this->assetSourcesConfiguration as $assetSourceIdentifier => $assetSourceConfiguration) {
                if (is_array($assetSourceConfiguration)) {
                    $this->assetSources[$assetSourceIdentifier] = $assetSourceConfiguration['assetSource']::createFromConfiguration($assetSourceIdentifier, $assetSourceConfiguration['assetSourceOptions']);
                }
            }
        }

        $assetSourceIdentifier = $this->getAssetSourceIdentifier();
        if ($assetSourceIdentifier === null) {
            return null;
        }

        return $this->assetSources[$assetSourceIdentifier] ?? null;
    }
}
