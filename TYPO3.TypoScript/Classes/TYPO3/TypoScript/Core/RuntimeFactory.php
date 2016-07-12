<?php
namespace TYPO3\TypoScript\Core;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\Routing\UriBuilder;

/**
 * This runtime factory takes care of instantiating a TypoScript runtime.
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
        $request = $httpRequest->createActionRequest();

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        return new \TYPO3\Flow\Mvc\Controller\ControllerContext(
            $request,
            new Response(),
            new Arguments(array()),
            $uriBuilder
        );
    }
}
