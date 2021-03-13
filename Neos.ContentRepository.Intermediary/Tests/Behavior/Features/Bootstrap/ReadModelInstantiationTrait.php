<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Intermediary\Domain\ReadModelFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Assert;

/**
 * Custom context trait for read model instantiation
 */
trait ReadModelInstantiationTrait
{
    private ?ContentStreamIdentifier $contentStreamIdentifier = null;

    private ?DimensionSpacePoint $dimensionSpacePoint = null;

    private ReadModelFactory $readModelFactory;

    private ContentGraphInterface $contentGraph;

    private NodeBasedReadModelInterface $currentReadModel;

    private \Exception $lastInstantiationException;

    abstract protected function getObjectManager(): ObjectManagerInterface;

    public function setupReadModelInstantiationTrait(): void
    {
        $this->readModelFactory = $this->getObjectManager()->get(ReadModelFactory::class);
    }

    /**
     * @When /^the read model with node aggregate identifier "([^"]*)" is instantiated and exceptions are caught$/
     * @param string $rawNodeAggregateIdentifier
     */
    public function theReadModelWithNodeAggregateIdentifierXIsInstantiatedAndExceptionsAreCaught(string $rawNodeAggregateIdentifier): void
    {
        try {
            $this->theReadModelWithNodeAggregateIdentifierXIsInstantiated($rawNodeAggregateIdentifier);
        } catch (\Exception $exception) {
            $this->lastInstantiationException = $exception;
        }
    }

    /**
     * @When /^the read model with node aggregate identifier "([^"]*)" is instantiated$/
     * @param string $rawNodeAggregateIdentifier
     */
    public function theReadModelWithNodeAggregateIdentifierXIsInstantiated(string $rawNodeAggregateIdentifier): void
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );

        $node = $subgraph->findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier));

        $this->currentReadModel = $this->readModelFactory->createReadModel($node, $subgraph);
    }

    /**
     * @Then /^I expect the instantiation to have thrown an exception of type "([^"]*)" with code (\d*)$/
     * @param string $expectedExceptionName
     */
    public function iExpectTheInstantiationToHaveThrownAnExceptionOfType(string $expectedExceptionName): void
    {
        Assert::assertNotNull($this->lastInstantiationException, 'Instantiation did not throw an exception');
        $lastInstantiationExceptionShortName = (new \ReflectionClass($this->lastInstantiationException))->getShortName();
        Assert::assertSame(
            $expectedExceptionName,
            $lastInstantiationExceptionShortName,
            sprintf(
                'Expected exception %s, actual exception: %s (%s): %s',
                $expectedExceptionName,
                get_class($this->lastInstantiationException),
                $this->lastInstantiationException->getCode(),
                $this->lastInstantiationException->getMessage()
            )
        );
    }

    /**
     * @Then /^I expect this read model to be an instance of "([^"]*)"$/
     * @param string $expectedClassName
     */
    public function iExpectThisReadModelToBeAnInstanceOfX(string $expectedClassName): void
    {
        Assert::assertInstanceOf(
            $expectedClassName,
            $this->currentReadModel,
            'The current read model is not of expected type "' . $expectedClassName . '" but of type "' . get_class($this->currentReadModel) . '"'
        );
    }

    /**
     * @Then /^I expect this read model to have the properties:$/
     * @param TableNode $expectedProperties
     */
    public function iExpectThisReadModelToHaveTheProperties(TableNode $expectedProperties): void
    {
        Assert::assertNotNull($this->currentReadModel, 'Current read model not found');

        $properties = $this->currentReadModel->getProperties();
        foreach ($expectedProperties->getHash() as $row) {
            $propertyName = $row['Key'];
            Assert::assertTrue(isset($properties[$propertyName]), 'Property "' . $propertyName . '" not found');
            Assert::assertEquals($row['Value'], $properties[$propertyName], 'Node property ' . $row['Key'] . ' does not match. Expected: ' . json_encode($row['Value']) . '; Actual: ' . json_encode($properties[$propertyName]));
        }
    }
}
