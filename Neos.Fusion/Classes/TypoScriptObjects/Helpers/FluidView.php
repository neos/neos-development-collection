<?php
namespace Neos\Fusion\TypoScriptObjects\Helpers;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;
use Neos\FluidAdaptor\View\StandaloneView;
use Neos\Fusion\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * Extended Fluid Template View for use in TypoScript.
 */
class FluidView extends StandaloneView implements TypoScriptAwareViewInterface
{
    /**
     * @var string
     */
    protected $resourcePackage;

    /**
     * @var AbstractTypoScriptObject
     */
    protected $typoScriptObject;

    /**
     * @param AbstractTypoScriptObject $typoScriptObject
     * @param ActionRequest $request The current action request. If none is specified it will be created from the environment.
     */
    public function __construct(AbstractTypoScriptObject $typoScriptObject, ActionRequest $request = null)
    {
        parent::__construct($request);
        $this->typoScriptObject = $typoScriptObject;
    }

    /**
     * @param string $resourcePackage
     */
    public function setResourcePackage($resourcePackage)
    {
        $this->resourcePackage = $resourcePackage;
    }

    /**
     * @return string
     */
    public function getResourcePackage()
    {
        return $this->resourcePackage;
    }

    /**
     * @return AbstractTypoScriptObject
     */
    public function getTypoScriptObject()
    {
        return $this->typoScriptObject;
    }
}
