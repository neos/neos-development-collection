<?php
namespace Neos\EventSourcedNeosAdjustments\Testing;

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Client\InternalRequestEngine;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Flow\Http;
use Neos\Flow\Http\Component\ComponentContext;

class CustomizedInternalRequestEngine extends InternalRequestEngine
{
    public function sendRequest(Http\Request $httpRequest)
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if (!$requestHandler instanceof FunctionalTestRequestHandler) {
            throw new Http\Exception('The browser\'s internal request engine has only been designed for use within functional tests.', 1335523749);
        }

        // TODO: THE FOLLOWING LINE THIS IS THE ONLY CHANGE NEEDED!!!
        //$this->securityContext->clearContext();

        $this->validatorResolver->reset();

        $response = new Http\Response();
        $componentContext = new ComponentContext($httpRequest, $response);
        $requestHandler->setComponentContext($componentContext);

        $objectManager = $this->bootstrap->getObjectManager();
        $baseComponentChain = $objectManager->get(\Neos\Flow\Http\Component\ComponentChain::class);
        $componentContext = new ComponentContext($httpRequest, $response);

        try {
            $baseComponentChain->handle($componentContext);
        } catch (\Throwable $throwable) {
            $this->prepareErrorResponse($throwable, $componentContext->getHttpResponse());
        } catch (\Exception $exception) {
            $this->prepareErrorResponse($exception, $componentContext->getHttpResponse());
        }
        $session = $this->bootstrap->getObjectManager()->get(SessionInterface::class);
        if ($session->isStarted()) {
            $session->close();
        }
        $this->persistenceManager->clearState();
        return $componentContext->getHttpResponse();
    }
}
