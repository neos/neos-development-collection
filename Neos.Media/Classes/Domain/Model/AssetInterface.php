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

use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\AssetSource\AssetSourceAwareInterface;

/**
 * A user-managed Asset which is stored in the Asset Repository
 *
 * @api
 */
interface AssetInterface extends ResourceBasedInterface, ThumbnailSupportInterface, AssetSourceAwareInterface
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
     * @param PersistentResource $resource
     * @return void
     * @api
     */
    public function setResource(PersistentResource $resource);

    /**
     * Returns the resource of this asset
     *
     * @return PersistentResource
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
