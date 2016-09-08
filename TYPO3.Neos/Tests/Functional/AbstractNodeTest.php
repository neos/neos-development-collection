<?php
namespace TYPO3\Neos\Tests\Functional;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Media\TypeConverter\AssetInterfaceConverter;
use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

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
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var string the Nodes fixture
     */
    protected $fixtureFileName = 'Fixtures/NodeStructure.xml';

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

    public function setUp()
    {
        parent::setUp();
        $this->markSkippedIfNodeTypesPackageIsNotInstalled();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contentContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/' . $this->fixtureFileName, $contentContext);
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
        /* @var $propertyMapper \TYPO3\Flow\Property\PropertyMapper */
        $propertyMapper = $this->objectManager->get(PropertyMapper::class);
        $node = $propertyMapper->convert($contextPath, Node::class);
        $this->assertFalse($propertyMapper->getMessages()->hasErrors(), 'There were errors converting ' . $contextPath);
        return $node;
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->inject($this->contextFactory, 'contextInstances', array());
        $this->inject($this->objectManager->get(AssetInterfaceConverter::class), 'resourcesAlreadyConvertedToAssets', array());
    }

    protected function markSkippedIfNodeTypesPackageIsNotInstalled()
    {
        $packageManager = $this->objectManager->get(PackageManagerInterface::class);
        if (!$packageManager->isPackageActive('TYPO3.Neos.NodeTypes')) {
            $this->markTestSkipped('This test needs the TYPO3.Neos.NodeTypes package.');
        }
    }
}
