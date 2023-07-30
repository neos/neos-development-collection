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
use Neos\Eel\Utility as EelUtility;
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

    /**
     * @Flow\InjectConfiguration(path="defaultContext", package="Neos.Fusion")
     */
    protected ?array $defaultContextConfiguration;

    /**
     * @deprecated with Neos 8.3 might be removed with Neos 9.0 use {@link createFromConfiguration} instead.
     */
    public function create(array $fusionConfiguration, ControllerContext $controllerContext = null): Runtime
    {
        if ($controllerContext === null) {
            $controllerContext = self::createControllerContextFromEnvironment();
        }

        return $this->createFromConfigurationAndControllerContext(
            FusionConfiguration::fromArray($fusionConfiguration),
            $controllerContext
        );
    }

    /**
     * Runtime for standalone usage in Flow. Independent of the current request.
     * Uri-building and other things requiring {@see Runtime::getControllerContext()} or the current request will not work.
     */
    public function createFromConfiguration(FusionConfiguration $fusionConfiguration, ControllerContext $controllerContext): Runtime
    {
        // TODO
        throw new \BadMethodCallException('Todo');
    }

    /**
     * Must be used in oder to allow plugins and "sub" request to function correctly.
     *
     * This instance of the runtime reflects in every case the legacy behaviour.
     *
     * @deprecated because the concept of {@see ControllerContext} is deprecated
     */
    public function createFromConfigurationAndControllerContext(
        FusionConfiguration $fusionConfiguration,
        ControllerContext $controllerContext
    ): Runtime {
        $defaultContextVariables = EelUtility::getDefaultContextVariables(
            $this->defaultContextConfiguration ?? []
        );
        $runtime = new Runtime(
            $fusionConfiguration,
            FusionDefaultContextVariables::fromRequestAndVariables(
                $controllerContext->getRequest(),
                $defaultContextVariables
            )
        );
        $runtime->setControllerContext($controllerContext);
        return $runtime;
    }

    public function createFromConfigurationAndDefaultContextVariables(
        FusionConfiguration $fusionConfiguration,
        FusionDefaultContextVariables $additionalDefaultContextVariables
    ): Runtime {
        $defaultContextVariables = EelUtility::getDefaultContextVariables(
            $this->defaultContextConfiguration ?? []
        );
        $runtime = new Runtime($fusionConfiguration, $additionalDefaultContextVariables->merge($defaultContextVariables));
        $runtime->setControllerContext(
            self::createControllerContextForActionRequest($additionalDefaultContextVariables->actionRequest)
        );
        return $runtime;
    }

    public function createFromSourceCode(
        FusionSourceCodeCollection $sourceCode,
        ActionRequest $actionRequest
    ): Runtime {
        // TODO
        throw new \BadMethodCallException('Todo');
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

    private static function createControllerContextForActionRequest(ActionRequest $actionRequest): ControllerContext
    {
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);

        return new ControllerContext(
            $actionRequest,
            new ActionResponse(),
            new Arguments([]),
            $uriBuilder
        );
    }
}
