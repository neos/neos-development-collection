<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Collections\ArrayCollection;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AssetCollection
 *
 * @Flow\Entity
 */
class AssetCollection {

	/**
	 * @var string
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $title;

	/**
	 * @var \Doctrine\Common\Collections\Collection<\TYPO3\Media\Domain\Model\Asset>
	 * @ORM\ManyToMany(inversedBy="assetCollections", cascade={"persist"})
	 * @Flow\Lazy
	 */
	protected $assets;

	/**
	 * @var \Doctrine\Common\Collections\Collection<\TYPO3\Media\Domain\Model\Tag>
	 * @ORM\ManyToMany(inversedBy="assetCollections")
	 * @ORM\OrderBy({"label"="ASC"})
	 * @Flow\Lazy
	 */
	protected $tags;

	public function __construct($title) {
		$this->title = $title;
		$this->assets = new ArrayCollection();
		$this->tags = new ArrayCollection();
	}

	/**
	 * Returns the Title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the Title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the Assets
	 *
	 * @return ArrayCollection
	 */
	public function getAssets() {
		return $this->assets;
	}

	/**
	 * Sets the Assets
	 *
	 * @param ArrayCollection $assets
	 * @return void
	 */
	public function setAssets(ArrayCollection $assets) {
		$this->assets = $assets;
	}

	/**
	 * Add one asset to the asset collection
	 *
	 * @param Asset $asset
	 * @return boolean
	 */
	public function addAsset(Asset $asset) {
		if ($this->assets->contains($asset) === FALSE) {
			$this->assets->add($asset);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Remove one asset from the asset collection
	 *
	 * @param Asset $asset
	 * @return boolean
	 */
	public function removeAsset(Asset $asset) {
		if ($this->assets->contains($asset) === TRUE) {
			$this->assets->removeElement($asset);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Return the tags assigned to this asset
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getTags() {
		return $this->tags;
	}

	/**
	 * Add a single tag to this asset
	 *
	 * @param Tag $tag
	 * @return boolean
	 */
	public function addTag(Tag $tag) {
		if (!$this->tags->contains($tag)) {
			$this->lastModified = new \DateTime();
			$this->tags->add($tag);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Set the tags assigned to this asset
	 *
	 * @param \Doctrine\Common\Collections\Collection $tags
	 * @return void
	 */
	public function setTags(\Doctrine\Common\Collections\Collection $tags) {
		$this->lastModified = new \DateTime();
		$this->tags = $tags;
	}

	/**
	 * Remove a single tag from this asset
	 *
	 * @param Tag $tag
	 * @return boolean
	 */
	public function removeTag(Tag $tag) {
		if ($this->tags->contains($tag)) {
			$this->lastModified = new \DateTime();
			$this->tags->removeElement($tag);
			return TRUE;
		}
		return FALSE;
	}

}