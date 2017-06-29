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
use Neos\Neos\Utility\BackendAssetsUtility;

/**
 * Returns a shortened md5 of the built JavaScript file
 */
class JavascriptBuiltVersionViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var BackendAssetsUtility
     */
    protected $backendAssetsUtility;

    /**
     * @return string
     */
    public function render()
    {
        return $this->backendAssetsUtility->getJavascriptBuiltVersion();
    }
}
