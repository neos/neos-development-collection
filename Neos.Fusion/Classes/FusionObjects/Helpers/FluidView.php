<?php
namespace Neos\Fusion\FusionObjects\Helpers;

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
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Extended Fluid Template View for use in Fusion.
 */
class FluidView extends StandaloneView implements FusionAwareViewInterface
{
    /**
     * @var string
     */
    protected $resourcePackage;

    /**
     * @var AbstractFusionObject
     */
    protected $typoScriptObject;

    /**
     * @param AbstractFusionObject $typoScriptObject
     * @param ActionRequest $request The current action request. If none is specified it will be created from the environment.
     */
    public function __construct(AbstractFusionObject $typoScriptObject, ActionRequest $request = null)
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
     * @deprecated with 3.0 will be removed with 4.0
     * @return AbstractFusionObject
     */
    public function getTypoScriptObject()
    {
        return $this->getFusionObject();
    }

    /**
     * @return AbstractFusionObject
     */
    public function getFusionObject()
    {
        return $this->typoScriptObject;
    }
}
