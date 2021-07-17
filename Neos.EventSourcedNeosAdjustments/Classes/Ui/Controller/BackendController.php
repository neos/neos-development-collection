<?php
declare(strict_types=1);
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

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedNeosAdjustments\Ui\Service\NodeClipboard;
use Neos\EventSourcedNeosAdjustments\Ui\View\BackendFusionView;
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
    protected $defaultViewObjectName = BackendFusionView::class;

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
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

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

    /**
     * @Flow\Inject
     * @var NodeClipboard
     */
    protected $clipboard;

    /**
     * @Flow\Inject
     * @var ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos.Ui", path="splashScreen.partial")
     * @var string
     */
    protected $splashScreenPartial;

    public function initializeView(ViewInterface $view)
    {
        $view->setFusionPath('backend');
    }

    /**
     * Displays the backend interface
     *
     * @param \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress $node The node that will be displayed on the first tab
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

        $workspaceName = $this->userService->getPersonalWorkspaceName();
        $workspace = $this->workspaceFinder->findOneByName(new WorkspaceName($workspaceName));
        $nodeAccessor = $this->nodeAccessorManager->accessorFor($workspace->getCurrentContentStreamIdentifier(), $this->findDefaultDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());

        // we assume that the ROOT node is always stored in the CR as "physical" node; so it is safe
        // to call the contentGraph here directly.
        $rootNodeAggregate = $this->contentGraph->findRootNodeAggregateByType($workspace->getCurrentContentStreamIdentifier(), NodeTypeName::fromString('Neos.Neos:Sites'));
        $rootNode = $rootNodeAggregate->getNodeByCoveredDimensionSpacePoint($this->findDefaultDimensionSpacePoint());
        $siteNode = $nodeAccessor->findChildNodeConnectedThroughEdgeName($rootNode, NodeName::fromString($this->siteRepository->findDefault()->getNodeName()));

        if (!$nodeAddress) {
            // TODO: fix resolving node address from session?
            $node = $siteNode;
        } else {
            $node = $nodeAccessor->findByIdentifier($nodeAddress->getNodeAggregateIdentifier());
        }

        $this->view->assign('user', $user);
        $this->view->assign('documentNode', $node);
        $this->view->assign('site', $siteNode);
        $this->view->assign('clipboardNode', $this->clipboard->getSerializedNodeAddresses());
        $this->view->assign('clipboardMode', $this->clipboard->getMode());
        $this->view->assign('headScripts', $this->styleAndJavascriptInclusionService->getHeadScripts());
        $this->view->assign('headStylesheets', $this->styleAndJavascriptInclusionService->getHeadStylesheets());
        $this->view->assign('splashScreenPartial', $this->splashScreenPartial);
        $this->view->assign('sitesForMenu', $this->menuHelper->buildSiteList($this->getControllerContext()));

        $this->view->assignMultiple([
            'subgraph' => $nodeAccessor
        ]);

        $this->view->assign('interfaceLanguage', $this->userService->getInterfaceLanguage());
    }

    /**
     * @param \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress $node
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */
    public function redirectToAction(NodeAddress $node)
    {
        $this->response->setHttpHeader('Cache-Control', [
            'no-cache',
            'no-store'
        ]);
        $this->redirect('show', 'Frontend\Node', 'Neos.Neos', ['node' => $node]);
    }

    protected function findDefaultDimensionSpacePoint(): DimensionSpacePoint
    {
        $coordinates = [];
        foreach ($this->contentDimensionSource->getContentDimensionsOrderedByPriority() as $dimension) {
            $coordinates[(string)$dimension->getIdentifier()] = (string)$dimension->getDefaultValue();
        }

        return new DimensionSpacePoint($coordinates);
    }
}
