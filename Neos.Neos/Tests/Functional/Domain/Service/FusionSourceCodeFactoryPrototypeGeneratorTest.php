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

use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Service\FusionSourceCodeFactory;
use Symfony\Component\Yaml\Parser as YamlParser;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Tests\Functional\Domain\Service\Fixtures\TestablePrototypeGenerator;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Tests for the TypoFusionService
 */
class FusionSourceCodeFactoryPrototypeGeneratorTest extends FunctionalTestCase
{
    private const FIXTURE_FILE_NAME = 'Fixtures/NodeTypes.yaml';

    private FusionSourceCodeFactory $factory;

    private NodeTypeManager $originalNodeTypeManager;

    private NodeTypeManager $mockNodeTypeManager;

    private NodeTypeManager $fixtureNodeTypeManager;

    private TestablePrototypeGenerator $testablePrototypeGenerator;

    public function setUp(): void
    {
        parent::setUp();

        $yamlParser = $this->objectManager->get(YamlParser::class);
        $this->originalNodeTypeManager = $this->objectManager->get(NodeTypeManager::class);

        $this->fixtureNodeTypeManager = clone ($this->originalNodeTypeManager);
        $this->fixtureNodeTypeManager->overrideNodeTypes($yamlParser->parse(file_get_contents(__DIR__ . '/' . self::FIXTURE_FILE_NAME)));

        $this->mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["getNodeTypes"])
            ->getMock();

        $this->objectManager->setInstance(NodeTypeManager::class, $this->mockNodeTypeManager);

        $this->testablePrototypeGenerator = $this->objectManager->get(TestablePrototypeGenerator::class);

        $this->factory = $this->objectManager->get(FusionSourceCodeFactory::class);
    }

    public function tearDown(): void
    {
        $this->objectManager->setInstance(NodeTypeManager::class, $this->originalNodeTypeManager);
        $this->objectManager->forgetInstance(FusionSourceCodeFactory::class);
        $this->objectManager->forgetInstance(TestablePrototypeGenerator::class);
        parent::tearDown();
    }

    /** @test */
    public function generateFusionForNodeThrowsExceptionForInvalidFusionPrototypeGenerator()
    {
        $this->expectException(Exception::class);
        $this->mockNodeTypeManagerToOnlyReturnNodeType('Neos.Neos:NodeTypeWithInvalidFusionPrototypeGenerator');
        $this->factory->createFromNodeTypeDefinitions();
    }

    /** @test */
    public function generateFusionForNodeDoesNotUseFusionPrototypeGeneratorWithoutConfiguration()
    {
        $this->mockNodeTypeManagerToOnlyReturnNodeType('Neos.Neos:NodeTypeWithoutFusionPrototypeGenerator');
        $this->factory->createFromNodeTypeDefinitions();
        self::assertSame(0, $this->testablePrototypeGenerator->getCallCount());
    }

    /** @test */
    public function generateFusionForNodeUsesDirectlyConfiguredFusionPrototypeGenerator()
    {
        $this->mockNodeTypeManagerToOnlyReturnNodeType('Neos.Neos:NodeTypeWithPrototypeGenerator');
        $this->factory->createFromNodeTypeDefinitions();
        self::assertSame(1, $this->testablePrototypeGenerator->getCallCount());
    }

    /** @test */
    public function generateFusionForNodeUsesInheritedFusionPrototypeGenerator()
    {
        $this->mockNodeTypeManagerToOnlyReturnNodeType('Neos.Neos:NodeTypeWithInheritedPrototypeGenerator');
        $this->factory->createFromNodeTypeDefinitions();
        self::assertSame(1, $this->testablePrototypeGenerator->getCallCount());
    }

    private function mockNodeTypeManagerToOnlyReturnNodeType(string $nodeTypeName): void
    {
        $nodeType = $this->fixtureNodeTypeManager->getNodeType($nodeTypeName);
        $this->mockNodeTypeManager->expects(self::once())->method("getNodeTypes")->willReturn([$nodeType]);
    }
}
