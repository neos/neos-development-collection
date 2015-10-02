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

/**
 * An asset interface
 */
interface AssetInterface
{
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
