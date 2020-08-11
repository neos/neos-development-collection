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
use Neos\Neos\Utility\BackendAssetsUtility;

/**
 * Returns true if the minified Neos JavaScript sources should be loaded, false otherwise.
 */
class ShouldLoadMinifiedJavascriptViewHelper extends AbstractViewHelper
{
    /**
     * @see AbstractViewHelper::isOutputEscapingEnabled()
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @Flow\Inject
     * @var BackendAssetsUtility
     */
    protected $backendAssetsUtility;

    /**
     * @return boolean
     */
    public function render()
    {
        return $this->backendAssetsUtility->shouldLoadMinifiedJavascript();
    }
}
