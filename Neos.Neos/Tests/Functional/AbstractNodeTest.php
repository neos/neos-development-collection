<?php
namespace Neos\Neos\Tests\Functional;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Media\TypeConverter\AssetInterfaceConverter;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\ContentRepository\Domain\Model\Node;

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

    public function setUp(): void
    {
        parent::setUp();

        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contentContext = $this->contextFactory->create(['workspaceName' => 'live']);
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
        /* @var $propertyMapper \Neos\Flow\Property\PropertyMapper */
        $propertyMapper = $this->objectManager->get(PropertyMapper::class);
        $node = $propertyMapper->convert($contextPath, Node::class);
        self::assertFalse($propertyMapper->getMessages()->hasErrors(), 'There were errors converting ' . $contextPath);
        return $node;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->inject($this->contextFactory, 'contextInstances', []);
        $this->inject($this->objectManager->get(AssetInterfaceConverter::class), 'resourcesAlreadyConvertedToAssets', []);
    }
}
