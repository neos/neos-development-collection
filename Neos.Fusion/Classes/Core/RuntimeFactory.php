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

/**
 * @Flow\Scope("singleton")
 * @api
 */
class RuntimeFactory
{
    /**
     * @var Parser
     * @Flow\Inject
     */
    protected $fusionParser;

    public function create(FusionConfiguration|array $fusionConfiguration, ControllerContext $controllerContext = null): Runtime
    {
        if ($controllerContext === null) {
            $controllerContext = self::createControllerContextFromEnvironment();
        }
        return new Runtime($fusionConfiguration, $controllerContext);
    }

    public function createFromSourceCode(
        FusionSourceCodeCollection $sourceCode,
        ControllerContext $controllerContext
    ): Runtime {
        return new Runtime(
            $this->fusionParser->parseFromSource($sourceCode),
            $controllerContext
        );
    }

    private static function createControllerContextFromEnvironment(): ControllerContext
    {
        $httpRequest = ServerRequest::fromGlobals();

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
