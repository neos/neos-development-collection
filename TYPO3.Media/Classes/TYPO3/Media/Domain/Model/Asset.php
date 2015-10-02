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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

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
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

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
     * @var \TYPO3\Flow\Resource\Resource
     * @ORM\ManyToOne
     * @Flow\Validate(type="NotEmpty")
     */
    protected $resource;

    /**
     * @var \Doctrine\Common\Collections\Collection<\TYPO3\Media\Domain\Model\Tag>
     * @ORM\ManyToMany
     */
    protected $tags;

    /**
     * Constructs an asset. The resource is set internally and then initialize()
     * is called.
     *
     * @param \TYPO3\Flow\Resource\Resource $resource
     */
    public function __construct(\TYPO3\Flow\Resource\Resource $resource)
    {
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setResource($resource);
        $this->lastModified = new \DateTime();
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
     * @param \TYPO3\Flow\Resource\Resource $resource
     * @return void
     */
    public function setResource(\TYPO3\Flow\Resource\Resource $resource)
    {
        $this->lastModified = new \DateTime();
        $this->resource = $resource;
        $this->initialize();
    }

    /**
     * Resource of the original file
     *
     * @return \TYPO3\Flow\Resource\Resource
     */
    public function getResource()
    {
        return $this->resource;
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
     * @param Tag $tag
     * @return boolean
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
}
