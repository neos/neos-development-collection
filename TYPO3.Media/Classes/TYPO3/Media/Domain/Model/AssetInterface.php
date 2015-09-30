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

use TYPO3\Flow\Resource\Resource;

/**
 * A user-managed Asset which is stored in the Asset Repository
 *
 * @api
 */
interface AssetInterface extends ResourceBasedInterface, ThumbnailSupportInterface
{
    /**
     * The title of this asset
     *
     * @return string Title of the asset
     * @api
     */
    public function getTitle();

    /**
     * Sets the title of this asset
     *
     * @param string $title
     * @return void
     * @api
     */
    public function setTitle($title);

    /**
     * Sets the resource and possibly triggers a refresh of dependent behavior
     *
     * @param Resource $resource
     * @return void
     * @api
     */
    public function setResource(Resource $resource);

    /**
     * Returns the resource of this asset
     *
     * @return \TYPO3\Flow\Resource\Resource
     * @api
     */
    public function getResource();

    /**
     * Returns the IANA media type of this asset
     *
     * @return string
     */
    public function getMediaType();

    /**
     * Returns a file extension fitting to the media type of this asset
     *
     * @return string
     */
    public function getFileExtension();
}
