<?php
namespace Neos\Neos\Tests\Functional\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use ReflectionMethod;
use Symfony\Component\Yaml\Parser as YamlParser;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Tests\Functional\Domain\Service\Fixtures\TestablePrototypeGenerator;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Tests for the TypoFusionService
 */
class FusionServiceTest extends FunctionalTestCase
{
    const FIXTURE_FILE_NAME = 'Fixtures/NodeTypes.yaml';

    /**
     * @var FusionService
     */
    protected $fusionService;

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

        $this->fusionService = $this->objectManager->get(FusionService::class);
        $this->expectedPrototypeGenerator = $this->objectManager->get(TestablePrototypeGenerator::class);
        $this->yamlParser = $this->objectManager->get(YamlParser::class);
        $this->originalNodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->mockNodeTypeManager = clone ($this->originalNodeTypeManager);
        $this->mockNodeTypeManager->overrideNodeTypes($this->yamlParser->parse(file_get_contents(__DIR__ . '/' . self::FIXTURE_FILE_NAME)));
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
     * @expectedException \Neos\Neos\Domain\Exception
     */
    public function generateFusionForNodeThrowsExceptionForInvalidFusionPrototypeGenerator()
    {
        $this->invokeGenerateFusionForNodeType('Neos.Neos:NodeTypeWithInvalidFusionPrototypeGenerator');
    }

    /**
     * @test
     */
    public function generateFusionForNodeDoesNotUseFusionPrototypeGeneratorWithoutConfiguration()
    {
        $this->invokeGenerateFusionForNodeType('Neos.Neos:NodeTypeWithoutFusionPrototypeGenerator');
        $this->assertSame(0, $this->expectedPrototypeGenerator->getCallCount());
    }

    /**
     * @test
     */
    public function generateFusionForNodeUsesDirectlyConfiguredFusionPrototypeGenerator()
    {
        $this->invokeGenerateFusionForNodeType('Neos.Neos:NodeTypeWithPrototypeGenerator');
        $this->assertSame(1, $this->expectedPrototypeGenerator->getCallCount());
    }

    /**
     * @test
     */
    public function generateFusionForNodeUsesInheritedFusionPrototypeGenerator()
    {
        $this->invokeGenerateFusionForNodeType('Neos.Neos:NodeTypeWithInheritedPrototypeGenerator');
        $this->assertSame(1, $this->expectedPrototypeGenerator->getCallCount());
    }

    /**
     * @param $nodeTypeName
     * @return void
     */
    protected function invokeGenerateFusionForNodeType($nodeTypeName)
    {
        $method = new ReflectionMethod(
            FusionService::class,
            'generateFusionForNodeType'
        );

        $method->setAccessible(true);

        $method->invoke($this->fusionService, $this->mockNodeTypeManager->getNodeType($nodeTypeName));
    }
}
