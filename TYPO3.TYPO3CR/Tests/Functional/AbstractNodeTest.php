<?php
namespace TYPO3\TYPO3CR\Tests\Functional;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Base test case for nodes
 */
abstract class AbstractNodeTest extends \TYPO3\Flow\Tests\FunctionalTestCase
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
    protected $fixtureFileName;

    /**
     * @var string the context path of the node to load initially
     */
    protected $nodeContextPath = '/sites/example/home';

    /**
     * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
     */
    protected $node;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

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
        $this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
        $contentContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
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
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
     */
    protected function getNodeWithContextPath($contextPath)
    {
        /* @var $propertyMapper \TYPO3\Flow\Property\PropertyMapper */
        $propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');
        $node = $propertyMapper->convert($contextPath, 'TYPO3\TYPO3CR\Domain\Model\Node');
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
        $packageManager = $this->objectManager->get('TYPO3\Flow\Package\PackageManagerInterface');
        if (!$packageManager->isPackageActive('TYPO3.Neos.NodeTypes')) {
            $this->markTestSkipped('This test needs the TYPO3.Neos.NodeTypes package.');
        }
    }
}
