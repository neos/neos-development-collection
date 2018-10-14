<?php
namespace Neos\EventSourcedNeosAdjustments\Ui\Controller;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\ContextParameters;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Session\SessionInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Neos\Controller\Backend\MenuHelper;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Service\BackendRedirectionService;
use Neos\Neos\Service\UserService;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Fusion\View\FusionView;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Neos\Ui\Domain\Service\StyleAndJavascriptInclusionService;

class BackendController extends ActionController
{

    /**
     * @var string
     */
    protected $defaultViewObjectName = 'Neos\Neos\Ui\View\BackendFusionView';

    /**
     * @var FusionView
     */
    protected $view;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var MenuHelper
     */
    protected $menuHelper;

    /**
     * @Flow\Inject(lazy=false)
     * @var BackendRedirectionService
     */
    protected $backendRedirectionService;

    /**
     * @Flow\Inject
     * @var ContentGraph
     */
    protected $contentGraph;


    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;


    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var StyleAndJavascriptInclusionService
     */
    protected $styleAndJavascriptInclusionService;

    public function initializeView(ViewInterface $view)
    {
        $view->setFusionPath('backend');
    }

    /**
     * Displays the backend interface
     *
     * @param NodeAddress $node The node that will be displayed on the first tab
     * @return void
     */
    public function indexAction(NodeAddress $node = null)
    {
        $nodeAddress = $node;
        unset($node);
        $this->session->start();
        $this->session->putData('__neosLegacyUiEnabled__', false);
        $user = $this->userService->getBackendUser();

        if ($user === null) {
            $this->redirectToUri($this->uriBuilder->uriFor('index', [], 'Login', 'Neos.Neos'));
        }

        if ($nodeAddress === null) {
            $nodeAddress = $this->findNodeToEdit();
        }

        $workspaceName = $this->userService->getPersonalWorkspaceName();
        $workspace = $this->workspaceFinder->findOneByName(new WorkspaceName($workspaceName));
        $subgraph = $this->contentGraph->getSubgraphByIdentifier($workspace->getCurrentContentStreamIdentifier(), $this->findDefaultDimensionSpacePoint());
        $siteNode = $subgraph->findChildNodeConnectedThroughEdgeName($this->getRootNodeIdentifier(), new NodeName($this->siteRepository->findDefault()->getNodeName()));
        $siteNode = new TraversableNode($siteNode, $subgraph, new ContextParameters(new \DateTimeImmutable(), [], true, false));


        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->getNodeAggregateIdentifier());
        $node = new TraversableNode($node, $subgraph, new ContextParameters(new \DateTimeImmutable(), [], true, false));


        $this->view->assign('user', $user);
        $this->view->assign('documentNode', $node);
        $this->view->assign('site', $siteNode);
        $this->view->assign('headScripts', $this->styleAndJavascriptInclusionService->getHeadScripts());
        $this->view->assign('headStylesheets', $this->styleAndJavascriptInclusionService->getHeadStylesheets());
        $this->view->assign('sitesForMenu', $this->menuHelper->buildSiteList($this->getControllerContext()));

        $this->view->assignMultiple([
            'subgraph' => $subgraph,
            'contextParameters' => new ContextParameters(new \DateTimeImmutable(), [], true, true)
        ]);

        $this->view->assign('interfaceLanguage', $this->userService->getInterfaceLanguage());
    }

    /**
     * Deactivates the new UI and redirects back to the old one
     *
     * @param NodeInterface|null $node
     * @return void
     */
    public function deactivateAction(NodeInterface $node = null)
    {
        if ($node === null) {
            $node = $this->findNodeToEdit();
        }

        $this->session->start();
        $this->session->putData('__neosLegacyUiEnabled__', true);

        $this->redirect('show', 'Frontend\Node', 'Neos.Neos', ['node' => $node]);
    }

    /**
     * @param NodeAddress $node
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */
    public function redirectToAction(NodeAddress $node)
    {
        $this->response->getHeaders()->setCacheControlDirective('no-cache');
        $this->response->getHeaders()->setCacheControlDirective('no-store');
        $this->redirect('show', 'Frontend\Node', 'Neos.Neos', ['node' => $node]);
    }

    /**
     * @return NodeInterface|null
     */
    protected function getSiteNodeForLoggedInUser()
    {
        // TODO FIXME
        return null;
        $user = $this->userService->getBackendUser();
        if ($user === null) {
            return null;
        }

        $workspaceName = $this->userService->getPersonalWorkspaceName();
        $contentContext = $this->createContext($workspaceName);

        return $contentContext->getCurrentSiteNode();
    }

    /**
     * @Flow\Inject
     * @var ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    protected function findDefaultDimensionSpacePoint(): DimensionSpacePoint {
        $coordinates = [];
        foreach ($this->contentDimensionSource->getContentDimensionsOrderedByPriority() as $dimension) {
            $coordinates[(string)$dimension->getIdentifier()] = (string)$dimension->getDefaultValue();
        }

        return new DimensionSpacePoint($coordinates);
    }


    /**
     * @return NodeInterface|null
     */
    protected function findNodeToEdit()
    {
        $siteNode = $this->getSiteNodeForLoggedInUser();
        return $siteNode; // TODO fixme
        $reflectionMethod = new \ReflectionMethod($this->backendRedirectionService, 'getLastVisitedNode');
        $reflectionMethod->setAccessible(true);
        $node = $reflectionMethod->invoke($this->backendRedirectionService, $siteNode->getContext()->getWorkspaceName());

        if ($node === null) {
            $node = $siteNode;
        }

        return $node;
    }

    /**
     * @return \Neos\ContentRepository\Domain\ValueObject\NodeIdentifier
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    protected function getRootNodeIdentifier(): \Neos\ContentRepository\Domain\ValueObject\NodeIdentifier
    {
        return $this->contentGraph->findRootNodeByType(new NodeTypeName('Neos.Neos:Sites'))->getNodeIdentifier();
    }
}
