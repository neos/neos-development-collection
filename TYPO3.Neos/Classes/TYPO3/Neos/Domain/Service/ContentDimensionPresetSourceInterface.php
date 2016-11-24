<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * An interface for a Content Dimension Preset source
 *
 * It allows to resolve a Content Dimension Preset for a given dimension and urlSegment or find a matching
 * preset for a list of dimension values.
 *
 * Content Dimension Preset
 * ========================
 *
 * A Content Dimension Preset assigns an identifier to a list of dimension values. It has UI properties for a label and
 * icon and further options for routing.
 *
 * The default implementation ConfigurationContentDimensionPresetSource will read the available presets from settings.
 */
interface ContentDimensionPresetSourceInterface extends \TYPO3\TYPO3CR\Domain\Service\ContentDimensionPresetSourceInterface
{
    /**
     * Find a dimension preset by URI identifier
     *
     * @param string $dimensionName The dimension name where the preset should be searched
     * @param string $uriSegment The URI segment for a Content Dimension Preset
     * @return array The preset configuration, including the identifier as key "identifier" or NULL if none was found
     */
    public function findPresetByUriSegment($dimensionName, $uriSegment);
}
