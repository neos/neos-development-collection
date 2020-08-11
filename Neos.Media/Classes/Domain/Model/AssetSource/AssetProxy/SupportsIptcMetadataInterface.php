<?php
namespace Neos\Media\Domain\Model\AssetSource\AssetProxy;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Interface for an Asset Proxy which supports IPTC Metadata
 *
 * See https://iptc.org/standards/iim/
 *
 * Note that the property names are UpperCamelCase and as defined in the IPTC IIM specification.
 * Examples: "Title", "Keywords", "CopyrightNotice"
 */
interface SupportsIptcMetadataInterface
{
    /**
     * Returns true, if the given IPTC metadata property is available, ie. is supported and is not empty.
     *
     * @param string $propertyName
     * @return bool
     */
    public function hasIptcProperty(string $propertyName): bool;

    /**
     * Returns the given IPTC metadata property if it exists, or an empty string otherwise.
     *
     * @param string $propertyName
     * @return string
     */
    public function getIptcProperty(string $propertyName): string;

    /**
     * Returns all known IPTC metadata properties as key => value (e.g. "Title" => "My Photo")
     *
     * @return array
     */
    public function getIptcProperties(): array;
}
