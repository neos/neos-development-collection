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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for rendering the current version identifier for the
 * configuration cache.
 */
class ConfigurationCacheVersionViewHelper extends AbstractViewHelper
{
    /**
     * @var \TYPO3\Neos\Cache\CacheManager
     * @Flow\Inject
     */
    protected $cacheManager;

    /**
     * @return string The current cache version identifier
     */
    public function render()
    {
        return $this->cacheManager->getConfigurationCacheVersion();
    }
}
