<?php
declare(strict_types=1);

namespace Neos\Neos\View;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\ServerRequest;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\RequestHandler as HttpRequestHandler;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Fusion\Core\Runtime as FusionRuntime;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Controller\Arguments;

class FusionExceptionView extends AbstractView
{
    use FusionViewI18nTrait;

    /**
     * This contains the supported options, their default values, descriptions and types.
     * @var array<string,mixed>
     */
    protected $supportedOptions = [
        'enableContentCache' => ['defaultValue', true, 'boolean'],
    ];

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var FusionService
     * @Flow\Inject
     */
    protected $fusionService;

    /**
     * @var FusionRuntime
     */
    protected $fusionRuntime;

    /**
     * @var SiteRepository
     * @Flow\Inject
     */
    protected $siteRepository;

    /**
     * @var DomainRepository
     * @Flow\Inject
     */
    protected $domainRepository;

    /**
     * @return string
     * @throws \Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function render()
    {
        $domain = $this->domainRepository->findOneByActiveRequest();

        if ($domain) {
            $site = $domain->getSite();
        } else {
            $site = $this->siteRepository->findDefault();
        }

        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        $httpRequest = $requestHandler instanceof HttpRequestHandler
            ? $requestHandler->getHttpRequest()
            : ServerRequest::fromGlobals();
        $request = ActionRequest::fromHttpRequest($httpRequest);
        $request->setControllerPackageKey('Neos.Neos');
        $request->setFormat('html');
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);
        $controllerContext = new ControllerContext(
            $request,
            new ActionResponse(),
            new Arguments([]),
            $uriBuilder
        );

        /** @var SecurityContext $securityContext */
        $securityContext = $this->objectManager->get(SecurityContext::class);
        $securityContext->setRequest($request);

        #$contentContext = $this->contentContextFactory->create(['currentSite' => $site]);
        $domain = $this->domainRepository->findOneByActiveRequest();
        $site = $domain->getSite();

        /** @todo fetch me from an accessor */
        $currentSiteNode = null;

        $fusionRuntime = $this->getFusionRuntime($currentSiteNode, $controllerContext);

        $this->setFallbackRuleFromDimension($currentSiteNode);

        $fusionRuntime->pushContextArray(array_merge(
            $this->variables,
            [
                'node' => $currentSiteNode,
                'documentNode' => $currentSiteNode,
                'site' => $currentSiteNode,
                'editPreviewMode' => null
            ]
        ));

        try {
            $output = $fusionRuntime->render('error');
            $output = $this->extractBodyFromOutput($output);
        } catch (RuntimeException $exception) {
            throw $exception->getPrevious() ?: $exception;
        }
        $fusionRuntime->popContext();

        return $output;
    }

    /**
     * @param string $output
     * @return string The message body without the message head
     */
    protected function extractBodyFromOutput(string $output): string
    {
        if (substr($output, 0, 5) === 'HTTP/') {
            $endOfHeader = strpos($output, "\r\n\r\n");
            if ($endOfHeader !== false) {
                $output = substr($output, $endOfHeader + 4);
            }
        }
        return $output;
    }

    /**
     * @param NodeInterface $currentSiteNode
     * @param ControllerContext $controllerContext
     * @return FusionRuntime
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
     */
    protected function getFusionRuntime(
        NodeInterface $currentSiteNode,
        ControllerContext $controllerContext
    ): FusionRuntime {
        if ($this->fusionRuntime === null) {
            $this->fusionRuntime = $this->fusionService->createRuntime($currentSiteNode, $controllerContext);

            if (isset($this->options['enableContentCache']) && $this->options['enableContentCache'] !== null) {
                $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
            }
        }
        return $this->fusionRuntime;
    }
}
