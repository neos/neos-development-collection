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
 * xliff cache.
 */
class XliffCacheVersionViewHelper extends AbstractViewHelper
{

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Service\XliffService
     */
    protected $xliffService;

    /**
     * @return string The current cache version identifier
     */
    public function render()
    {
        return $this->xliffService->getCacheVersion();
    }
}
