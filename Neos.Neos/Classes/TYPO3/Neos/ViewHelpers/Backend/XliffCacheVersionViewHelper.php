<?php
namespace Neos\Neos\ViewHelpers\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Neos\Service\XliffService;

/**
 * ViewHelper for rendering the current version identifier for the
 * xliff cache.
 */
class XliffCacheVersionViewHelper extends AbstractViewHelper
{

    /**
     * @Flow\Inject
     * @var XliffService
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
