<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\View;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\RequestHandler as HttpRequestHandler;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Fusion\Core\FusionGlobals;
use Neos\Fusion\Core\Runtime as FusionRuntime;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Domain\Service\SiteNodeUtility;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

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

    #[Flow\Inject]
    protected RuntimeFactory $runtimeFactory;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected SiteNodeUtility $siteNodeUtility;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected DomainRepository $domainRepository;

    /**
     * @return string
     * @throws \Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function render()
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();

        if (!$requestHandler instanceof HttpRequestHandler) {
            throw new \RuntimeException('The FusionExceptionView only works in web requests.', 1695975353);
        }

        $httpRequest = $requestHandler->getHttpRequest();

        $siteDetectionResult = SiteDetectionResult::fromRequest($httpRequest);
        $contentRepository = $this->contentRepositoryRegistry->get($siteDetectionResult->contentRepositoryId);
        $fusionExceptionViewInternals = $this->contentRepositoryRegistry->buildService(
            $siteDetectionResult->contentRepositoryId,
            new FusionExceptionViewInternalsFactory()
        );
        $dimensionSpacePoint = $fusionExceptionViewInternals->getArbitraryDimensionSpacePoint();

        $liveWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());

        $currentSiteNode = null;
        $site = $this->siteRepository->findOneByNodeName($siteDetectionResult->siteNodeName);
        if ($liveWorkspace && $site) {
            $currentSiteNode = $this->siteNodeUtility->findSiteNodeBySite(
                $site,
                $liveWorkspace->currentContentStreamId,
                $dimensionSpacePoint,
                VisibilityConstraints::frontend()
            );
        }

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

        if ($currentSiteNode) {
            $fusionRuntime = $this->getFusionRuntime($currentSiteNode, $controllerContext);

            $this->setFallbackRuleFromDimension($dimensionSpacePoint);

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
                return $this->extractBodyFromOutput($output);
            } catch (RuntimeException $exception) {
                throw $exception->getPrevious() ?: $exception;
            } finally {
                $fusionRuntime->popContext();
            }
        }

        return '';
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
     * @param Node $currentSiteNode
     * @param ControllerContext $controllerContext
     * @return FusionRuntime
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
     */
    protected function getFusionRuntime(
        Node $currentSiteNode,
        ControllerContext $controllerContext
    ): FusionRuntime {
        if ($this->fusionRuntime === null) {
            $site = $this->siteRepository->findSiteBySiteNode($currentSiteNode);

            $fusionConfiguration = $this->fusionService->createFusionConfigurationFromSite($site);

            $fusionGlobals = FusionGlobals::fromArray([
                'request' => $controllerContext->getRequest(),
                'renderingModeName' => RenderingMode::FRONTEND
            ]);
            $this->fusionRuntime = $this->runtimeFactory->createFromConfiguration(
                $fusionConfiguration,
                $fusionGlobals
            );
            $this->fusionRuntime->setControllerContext($controllerContext);

            if (isset($this->options['enableContentCache']) && $this->options['enableContentCache'] !== null) {
                $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
            }
        }
        return $this->fusionRuntime;
    }
}
