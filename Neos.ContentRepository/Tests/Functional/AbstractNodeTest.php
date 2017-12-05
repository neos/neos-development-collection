<?php
namespace Neos\ContentRepository\Tests\Functional;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\PackageManager;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

/**
 * Base test case for nodes
 */
abstract class AbstractNodeTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * If enabled, this test case will modify the behavior of the security framework
     * in a way which allows for easy simulation of roles and authentication.
     *
     * Note: this will implicitly enable testable HTTP as well.
     *
     * @var boolean
     * @api
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var string the Nodes fixture
     */
    protected $fixtureFileName;

    /**
     * @var string the context path of the node to load initially
     */
    protected $nodeContextPath = '/sites/example/home';

    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var Workspace
     */
    protected $liveWorkspace;

    /**
     * @param string $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->fixtureFileName = __DIR__ . '/Fixtures/NodeStructure.xml';
    }

    public function setUp()
    {
        parent::setUp();
        $this->markSkippedIfNodeTypesPackageIsNotInstalled();

        if ($this->liveWorkspace === null) {
            $this->liveWorkspace = new Workspace('live');
            $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
            $this->workspaceRepository->add($this->liveWorkspace);
            $this->workspaceRepository->add(new Workspace('test', $this->liveWorkspace));
            $this->persistenceManager->persistAll();
        }

        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contentContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile($this->fixtureFileName, $contentContext);
        $this->persistenceManager->persistAll();

        if ($this->nodeContextPath !== null) {
            $this->node = $this->getNodeWithContextPath($this->nodeContextPath);
        }
    }

    /**
     * Retrieve a node through the property mapper
     *
     * @param $contextPath
     * @return NodeInterface
     */
    protected function getNodeWithContextPath($contextPath)
    {
        /* @var $propertyMapper PropertyMapper */
        $propertyMapper = $this->objectManager->get(PropertyMapper::class);
        $node = $propertyMapper->convert($contextPath, Node::class);
        $this->assertFalse($propertyMapper->getMessages()->hasErrors(), 'There were errors converting ' . $contextPath);
        return $node;
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->inject($this->contextFactory, 'contextInstances', array());
    }

    protected function markSkippedIfNodeTypesPackageIsNotInstalled()
    {
        $packageManager = $this->objectManager->get(PackageManagerInterface::class);
        if (!$packageManager->isPackageActive('Neos.NodeTypes')) {
            $this->markTestSkipped('This test needs the Neos.NodeTypes package.');
        }
    }
}
