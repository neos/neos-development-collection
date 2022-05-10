<?php

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Testing;

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Http\Client\InternalRequestEngine;
use Neos\Flow\Mvc\FlashMessage\FlashMessageService;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Session\SessionManager;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Flow\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CustomizedInternalRequestEngine extends InternalRequestEngine
{
    public function sendRequest(RequestInterface $httpRequest): ResponseInterface
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        /** @phpstan-ignore-next-line */
        if (!$requestHandler instanceof FunctionalTestRequestHandler) {
            throw new Http\Exception(
                'The browser\'s internal request engine has only been designed for use within functional tests.',
                1335523749
            );
        }
        /** @phpstan-ignore-next-line */
        $requestHandler->setHttpRequest($httpRequest);
        // TODO: THE FOLLOWING LINE THIS IS THE ONLY CHANGE NEEDED!!!
        //$this->securityContext->clearContext();
        $this->validatorResolver->reset();

        $objectManager = $this->bootstrap->getObjectManager();
        /** @var Http\Middleware\MiddlewaresChain $middlewaresChain */
        $middlewaresChain = $objectManager->get(Http\Middleware\MiddlewaresChain::class);

        try {
            /** @phpstan-ignore-next-line */
            $response = $middlewaresChain->handle($httpRequest);
        } catch (\Throwable $throwable) {
            $response = $this->prepareErrorResponse($throwable, new Response());
        }
        /** @var SessionInterface $session */
        $session = $objectManager->get(SessionInterface::class);
        if ($session->isStarted()) {
            $session->close();
        }
        // FIXME: ObjectManager should forget all instances created during the request
        $objectManager->forgetInstance(SessionManager::class);
        $objectManager->forgetInstance(FlashMessageService::class);
        $this->persistenceManager->clearState();
        return $response;
    }
}
