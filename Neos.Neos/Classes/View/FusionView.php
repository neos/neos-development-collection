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

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Flow\Security\Context;
use Neos\Fusion\Core\FusionGlobals;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\RenderingModeService;
use Neos\Neos\Exception;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * A Fusion view for Neos
 */
class FusionView extends AbstractView
{
    use FusionViewI18nTrait;
    use NodeTypeWithFallbackProvider;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected RuntimeFactory $runtimeFactory;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected RenderingModeService $renderingModeService;

    /**
     * Via {@see assign} request using the "request" key,
     * will be available also as Fusion global in the runtime.
     */
    protected ?ActionRequest $assignedActionRequest = null;

    /**
     * Renders the view
     *
     * @return ResponseInterface The rendered view
     * @throws \Exception if no node is given
     * @api
     */
    public function render(): ResponseInterface
    {
        $currentNode = $this->getCurrentNode();

        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($currentNode);
        $currentSiteNode = $subgraph->findClosestNode($currentNode->nodeAggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));

        if (!$currentSiteNode) {
            throw new \RuntimeException('No site node found!', 1697053346);
        }

        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        $this->setFallbackRuleFromDimension($currentNode->subgraphIdentity->dimensionSpacePoint);

        return $fusionRuntime->renderResponse($this->fusionPath, [
            'node' => $currentNode,
            'documentNode' => $this->getClosestDocumentNode($currentNode) ?: $currentNode,
            'site' => $currentSiteNode
        ]);
    }

    /**
     * This contains the supported options, their default values, descriptions and types.
     *
     * @var array<string,array<int,mixed>>
     */
    protected $supportedOptions = [
        'enableContentCache' => [
            null,
            'Flag to enable content caching inside Fusion (overriding the global setting).',
            'boolean'
        ],
        'renderingModeName' => [
            RenderingMode::FRONTEND,
            'Name of the user interface mode to use',
            'string'
        ]
    ];

    /**
     * @Flow\Inject
     * @var FusionService
     */
    protected $fusionService;

    /**
     * The Fusion path to use for rendering the node given in "value", defaults to "page".
     *
     * @var string
     */
    protected $fusionPath = 'root';

    protected ?Runtime $fusionRuntime;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * Is it possible to render $node with $his->fusionPath?
     *
     * @return boolean true if $node can be rendered at fusionPath
     *
     * @throws Exception
     */
    public function canRenderWithNodeAndPath()
    {
        $currentSiteNode = $this->getCurrentSiteNode();
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        return $fusionRuntime->canRender($this->fusionPath);
    }

    /**
     * Set the Fusion path to use for rendering the node given in "value"
     *
     * @param string $fusionPath
     * @return void
     */
    public function setFusionPath($fusionPath)
    {
        $this->fusionPath = $fusionPath;
    }

    /**
     * @return string
     */
    public function getFusionPath()
    {
        return $this->fusionPath;
    }

    protected function getClosestDocumentNode(Node $node): ?Node
    {
        return $this->contentRepositoryRegistry->subgraphForNode($node)
            ->findClosestNode($node->nodeAggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT));
    }

    /**
     * @return Node
     * @throws Exception
     */
    protected function getCurrentSiteNode(): Node
    {
        $currentNode = $this->variables['site'] ?? null;
        if (!$currentNode instanceof Node) {
            throw new Exception('FusionView needs a variable \'site\' set with a Node object.', 1538996432);
        }
        return $currentNode;
    }

    /**
     * @return Node
     * @throws Exception
     */
    protected function getCurrentNode(): Node
    {
        $currentNode = $this->variables['value'] ?? null;
        if (!$currentNode instanceof Node) {
            throw new Exception('FusionView needs a variable \'value\' set with a Node object.', 1329736456);
        }
        return $currentNode;
    }

    /**
     * @param Node $currentSiteNode
     * @return \Neos\Fusion\Core\Runtime
     */
    protected function getFusionRuntime(Node $currentSiteNode)
    {
        if ($this->fusionRuntime === null) {
            $site = $this->siteRepository->findSiteBySiteNode($currentSiteNode);
            $fusionConfiguration = $this->fusionService->createFusionConfigurationFromSite($site);

            $renderingMode = $this->renderingModeService->findByName($this->getOption('renderingModeName'));

            $fusionGlobals = FusionGlobals::fromArray(array_filter([
                'request' => $this->assignedActionRequest,
                'renderingMode' => $renderingMode
            ]));
            $this->fusionRuntime = $this->runtimeFactory->createFromConfiguration(
                $fusionConfiguration,
                $fusionGlobals
            );

            if (isset($this->options['enableContentCache']) && $this->options['enableContentCache'] !== null) {
                $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
            }
        }
        return $this->fusionRuntime;
    }

    /**
     * Clear the cached runtime instance on assignment of variables
     *
     * @param string $key
     * @param mixed $value
     */
    public function assign($key, $value): AbstractView
    {
        if ($key === 'request') {
            // the request cannot be used as "normal" fusion variable and must be treated as FusionGlobal
            // to for example not cache it accidentally
            // additionally we need it for special request based handling in the view
            $this->assignedActionRequest = $value;
            return $this;
        }
        $this->fusionRuntime = null;
        return parent::assign($key, $value);
    }

    /**
     * Legacy layer to set the request for this view if not set already.
     *
     * Please use {@see assign} with "request" instead
     *
     *     $view->assign('request"', $this->request)
     *
     * @deprecated with Neos 9
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        if (!$this->assignedActionRequest) {
            $this->assignedActionRequest = $controllerContext->getRequest();
        }
    }
}
