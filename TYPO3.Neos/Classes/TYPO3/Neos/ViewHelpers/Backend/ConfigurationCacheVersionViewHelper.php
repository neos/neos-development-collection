<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

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
use Neos\Flow\Cache\Frontend\StringFrontend;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for rendering the current version identifier for the
 * configuration cache.
 */
class ConfigurationCacheVersionViewHelper extends AbstractViewHelper
{
    /**
     * @var StringFrontend
     */
    protected $configurationCache;

    /**
     * @return string The current cache version identifier
     */
    public function render()
    {
        $version = $this->configurationCache->get('ConfigurationVersion');
        if ($version === false) {
            $version = time();
            $this->configurationCache->set('ConfigurationVersion', (string)$version);
        }
        return $version;
    }
}
