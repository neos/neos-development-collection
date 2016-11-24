<?php
namespace TYPO3\Neos\Tests\Functional\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use ReflectionMethod;
use Symfony\Component\Yaml\Parser as YamlParser;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Neos\Domain\Service\TypoScriptService;
use TYPO3\Neos\Tests\Functional\Domain\Service\Fixtures\TestablePrototypeGenerator;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Tests for the TypoScriptService
 */
class TypoScriptServiceTest extends FunctionalTestCase
{
    /**
     * @var string the Nodes fixture
     */
    protected $fixtureFileName = 'Fixtures/NodeTypes.yaml';

    /**
     * @var TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @var NodeTypeManager
     */
    protected $originalNodeTypeManager;

    /**
     * @var NodeTypeManager
     */
    protected $mockNodeTypeManager;

    /**
     * @var YamlParser
     */
    protected $yamlParser;

    /**
     * @var TestablePrototypeGenerator
     */
    protected $expectedPrototypeGenerator;


    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->typoScriptService = $this->objectManager->get(TypoScriptService::class);

        $this->expectedPrototypeGenerator = $this->objectManager->get(TestablePrototypeGenerator::class);

        $this->yamlParser = $this->objectManager->get(YamlParser::class);

        $this->originalNodeTypeManager = $this->objectManager->get(NodeTypeManager::class);

        $this->mockNodeTypeManager = clone ($this->originalNodeTypeManager);
        $this->mockNodeTypeManager->overrideNodeTypes($this->yamlParser->parse(file_get_contents(__DIR__ . '/Fixtures/NodeTypes.yaml')));

        $this->objectManager->setInstance(NodeTypeManager::class, $this->mockNodeTypeManager);
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        $this->objectManager->setInstance(NodeTypeManager::class, $this->originalNodeTypeManager);
        $this->expectedPrototypeGenerator->reset();
        parent::tearDown();
    }

    /**
     * @test
     * @expectedException \TYPO3\Neos\Domain\Exception
     */
    public function generateTypoScriptForNodeThrowsExceptionForInvalidFusionPrototypeGenerator()
    {
        $this->invokeGenerateTypoScriptForNodeType('TYPO3.Neos:NodeTypeWithInvalidFusionPrototypeGenerator');
    }

    /**
     * @test
     */
    public function generateTypoScriptForNodeDoesNotUseFusionPrototypeGeneratorWithoutConfiguration()
    {
        $this->invokeGenerateTypoScriptForNodeType('TYPO3.Neos:NodeTypeWithoutFusionPrototypeGenerator');
        $this->assertSame(0, $this->expectedPrototypeGenerator->getCallCount());
    }

    /**
     * @test
     */
    public function generateTypoScriptForNodeUsesDirectlyConfiguredFusionPrototypeGenerator()
    {
        $this->invokeGenerateTypoScriptForNodeType('TYPO3.Neos:NodeTypeWithPrototypeGenerator');
        $this->assertSame(1, $this->expectedPrototypeGenerator->getCallCount());
    }

    /**
     * @test
     */
    public function generateTypoScriptForNodeUsesInheritedFusionPrototypeGenerator()
    {
        $this->invokeGenerateTypoScriptForNodeType('TYPO3.Neos:NodeTypeWithInheritedPrototypeGenerator');
        $this->assertSame(1, $this->expectedPrototypeGenerator->getCallCount());
    }

    /**
     * @param $nodeTypeName
     * @return void
     */
    protected function invokeGenerateTypoScriptForNodeType($nodeTypeName)
    {
        $method = new ReflectionMethod(
            TypoScriptService::class, 'generateTypoScriptForNodeType'
        );

        $method->setAccessible(true);

        $method->invoke($this->typoScriptService, $this->mockNodeTypeManager->getNodeType($nodeTypeName));
    }
}
