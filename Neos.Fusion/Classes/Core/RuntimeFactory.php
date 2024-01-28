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
 * @internal The Fusion Runtime is considered internal.
 *           For interacting with Fusion from the outside a FusionView should be used.
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
     * @var array<string, string>
     */
    protected ?array $defaultContextConfiguration;

    /**
     * @param array<int|string, mixed> $fusionConfiguration
     * @deprecated with Neos 8.3 might be removed with Neos 9.0 use {@link createFromConfiguration} instead.
     */
    public function create(array $fusionConfiguration, ControllerContext $controllerContext = null): Runtime
    {
        $defaultContextVariables = EelUtility::getDefaultContextVariables(
            $this->defaultContextConfiguration ?? []
        );
        $runtime = new Runtime(
            FusionConfiguration::fromArray($fusionConfiguration),
            FusionGlobals::fromArray(
                [
                    'request' => $controllerContext?->getRequest() ?? ActionRequest::fromHttpRequest(ServerRequest::fromGlobals()),
                    ...$defaultContextVariables
                ]
            )
        );
        return $runtime;
    }

    public function createFromConfiguration(
        FusionConfiguration $fusionConfiguration,
        FusionGlobals $fusionGlobals
    ): Runtime {
        $fusionGlobalHelpers = FusionGlobals::fromArray(
            EelUtility::getDefaultContextVariables(
                $this->defaultContextConfiguration ?? []
            )
        );
        return new Runtime($fusionConfiguration, $fusionGlobalHelpers->merge($fusionGlobals));
    }

    public function createFromSourceCode(
        FusionSourceCodeCollection $sourceCode,
        FusionGlobals $fusionGlobals
    ): Runtime {
        return $this->createFromConfiguration(
            $this->fusionParser->parseFromSource($sourceCode),
            $fusionGlobals
        );
    }
}
