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

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * This runtime factory takes care of instantiating a Fusion runtime.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class RuntimeFactory
{
    /**
     * @Flow\Inject
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    /**
     * @param array $fusionConfiguration
     * @param ControllerContext $controllerContext
     * @return Runtime
     */
    public function create($fusionConfiguration, ControllerContext $controllerContext = null)
    {
        if ($controllerContext === null) {
            $controllerContext = $this->createControllerContextFromEnvironment();
        }

        return new Runtime($fusionConfiguration, $controllerContext);
    }

    /**
     * @return ControllerContext
     */
    protected function createControllerContextFromEnvironment()
    {
        $httpRequest = ServerRequest::fromGlobals();

        /** @var ActionRequest $request */
        $request = ActionRequest::fromHttpRequest($httpRequest);

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        return new ControllerContext(
            $request,
            new ActionResponse(),
            new Arguments([]),
            $uriBuilder
        );
    }
}
