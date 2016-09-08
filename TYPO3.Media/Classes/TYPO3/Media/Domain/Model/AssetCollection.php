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
use Doctrine\Common\Collections\Collection;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AssetCollection
 *
 * @Flow\Entity
 */
class AssetCollection
{
    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $title;

    /**
     * @var Collection<\TYPO3\Media\Domain\Model\Asset>
     * @ORM\ManyToMany(inversedBy="assetCollections", cascade={"persist"})
     * @Flow\Lazy
     */
    protected $assets;

    /**
     * @var Collection<\TYPO3\Media\Domain\Model\Tag>
     * @ORM\ManyToMany(inversedBy="assetCollections")
     * @ORM\OrderBy({"label"="ASC"})
     * @Flow\Lazy
     */
    protected $tags;

    /**
     * @param string $title
     */
    public function __construct($title)
    {
        $this->title = $title;
        $this->assets = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    /**
     * Returns the Title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the Title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the Assets
     *
     * @return ArrayCollection
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * Sets the Assets
     *
     * @param ArrayCollection $assets
     * @return void
     */
    public function setAssets(ArrayCollection $assets)
    {
        $this->assets = $assets;
    }

    /**
     * Add one asset to the asset collection
     *
     * @param Asset $asset
     * @return boolean
     */
    public function addAsset(Asset $asset)
    {
        if ($this->assets->contains($asset) === false) {
            $this->assets->add($asset);
            return true;
        }
        return false;
    }

    /**
     * Remove one asset from the asset collection
     *
     * @param Asset $asset
     * @return boolean
     */
    public function removeAsset(Asset $asset)
    {
        if ($this->assets->contains($asset) === true) {
            $this->assets->removeElement($asset);
            return true;
        }
        return false;
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
}
