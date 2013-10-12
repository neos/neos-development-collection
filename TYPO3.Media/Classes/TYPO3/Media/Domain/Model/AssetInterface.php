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

/**
 * An asset interface
 */
interface AssetInterface {

	/**
	 * The title of this asset
	 *
	 * @return string Title of the asset
	 */
	public function getTitle();

	/**
	 * Sets the title of this asset
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title);

	/**
	 * Resource of the original image file
	 *
	 * @return \TYPO3\Flow\Resource\Resource
	 */
	public function getResource();

	/**
	 * Sets the asset resource
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource
	 * @return void
	 */
	public function setResource(\TYPO3\Flow\Resource\Resource $resource);

	/**
	 * Add a single tag to this asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @return void
	 */
	public function addTag(Tag $tag);

	/**
	 * Set the tags assigned to this asset
	 *
	 * @param \Doctrine\Common\Collections\Collection $tags
	 * @return void
	 */
	public function setTags(\Doctrine\Common\Collections\Collection $tags);

}
