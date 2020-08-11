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
    protected $fusionObject;

    /**
     * @param AbstractFusionObject $fusionObject
     * @param ActionRequest $request The current action request. If none is specified it will be created from the environment.
     * @throws \Neos\FluidAdaptor\Exception
     */
    public function __construct(AbstractFusionObject $fusionObject, ActionRequest $request = null)
    {
        parent::__construct($request);
        $this->fusionObject = $fusionObject;
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
     * @return AbstractFusionObject
     */
    public function getFusionObject()
    {
        return $this->fusionObject;
    }
}
