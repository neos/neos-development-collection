<?php
namespace Neos\Fusion\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;

/**
 * This runtime factory takes care of instantiating a Fusion runtime.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class RuntimeFactory
{
    /**
     * @param array $typoScriptConfiguration
     * @param ControllerContext $controllerContext
     * @return Runtime
     */
    public function create($typoScriptConfiguration, ControllerContext $controllerContext = null)
    {
        if ($controllerContext === null) {
            $controllerContext = $this->createControllerContextFromEnvironment();
        }

        return new Runtime($typoScriptConfiguration, $controllerContext);
    }

    /**
     * @return ControllerContext
     */
    protected function createControllerContextFromEnvironment()
    {
        $httpRequest = Request::createFromEnvironment();

        /** @var ActionRequest $request */
        $request = new ActionRequest($httpRequest);

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        return new ControllerContext(
            $request,
            new Response(),
            new Arguments(array()),
            $uriBuilder
        );
    }
}
