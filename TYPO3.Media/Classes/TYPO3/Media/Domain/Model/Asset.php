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
class Asset implements AssetInterface {

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=255 })
	 */
	protected $title = '';

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
	public function __construct(\TYPO3\Flow\Resource\Resource $resource) {
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
		$this->setResource($resource);
	}

	/**
	 * Override this to initialize upon instantiation.
	 *
	 * @return void
	 */
	protected function initialize() {
	}

	/**
	 * Sets the asset resource and (re-)initializes the asset.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource
	 * @return void
	 */
	public function setResource(\TYPO3\Flow\Resource\Resource $resource) {
		$this->resource = $resource;
		$this->initialize();
	}

	/**
	 * Resource of the original image file
	 *
	 * @return \TYPO3\Flow\Resource\Resource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Sets the title of this image (optional)
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * The title of this image
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
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
	 * Set the tags assigned to this asset
	 *
	 * @param \Doctrine\Common\Collections\Collection $tags
	 * @return void
	 */
	public function setTags(\Doctrine\Common\Collections\Collection $tags) {
		$this->tags = $tags;
	}

}

?>