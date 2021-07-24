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
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Tests\Behavior\Fixtures\PostalAddress;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Assert;

/**
 * Custom context trait for read model instantiation
 */
trait ReadModelInstantiationTrait
{
    private ?ContentStreamIdentifier $contentStreamIdentifier = null;

    private ?DimensionSpacePoint $dimensionSpacePoint = null;

    private ContentGraphInterface $contentGraph;

    protected NodeInterface $currentReadModel;

    private ?\Exception $lastInstantiationException = null;

    abstract protected function getObjectManager(): ObjectManagerInterface;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    public function setupReadModelInstantiationTrait(): void
    {
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

        $this->currentReadModel = $node;
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
     * @param TableNode $payloadTable
     */
    public function iExpectThisReadModelToHaveTheProperties(TableNode $payloadTable): void
    {
        Assert::assertNotNull($this->currentReadModel, 'Current read model could not be found.');

        $expectedProperties = $this->readPayloadTable($payloadTable);

        $properties = $this->currentReadModel->getProperties();
        foreach ($expectedProperties as $propertyName => $expectedPropertyValue) {
            Assert::assertTrue(isset($properties[$propertyName]), 'Property "' . $propertyName . '" not found');
            if ($expectedPropertyValue === 'PostalAddress:dummy') {
                $expectedPropertyValue = PostalAddress::dummy();
            } elseif ($expectedPropertyValue === 'PostalAddress:anotherDummy') {
                $expectedPropertyValue = PostalAddress::anotherDummy();
            }
            if (is_string($expectedPropertyValue)) {
                if ($expectedPropertyValue === 'Date:now') {
                    // we accept 10s offset for the projector to be fine
                    $expectedPropertyValue = new \DateTimeImmutable();
                    $expectedDateInterval = new \DateInterval('PT10S');
                    Assert::assertLessThan($properties[$propertyName], $expectedPropertyValue->sub($expectedDateInterval), 'Node property ' . $propertyName . ' does not match. Expected: ' . json_encode($expectedPropertyValue) . '; Actual: ' . json_encode($properties[$propertyName]));
                    continue;
                } elseif (\mb_strpos($expectedPropertyValue, 'Date:') === 0) {
                    $expectedPropertyValue = \DateTimeImmutable::createFromFormat(\DateTimeInterface::W3C, \mb_substr($expectedPropertyValue, 5));
                } elseif (\mb_strpos($expectedPropertyValue, 'URI:') === 0) {
                    $expectedPropertyValue = new Uri(\mb_substr($expectedPropertyValue, 4));
                } elseif ($expectedPropertyValue === 'IMG:dummy') {
                    $expectedPropertyValue = $this->requireDummyImage();
                } elseif ($expectedPropertyValue === '[IMG:dummy]') {
                    $expectedPropertyValue = [$this->requireDummyImage()];
                }
            }
            Assert::assertEquals($expectedPropertyValue, $properties[$propertyName], 'Node property ' . $propertyName . ' does not match. Expected: ' . json_encode($expectedPropertyValue) . '; Actual: ' . json_encode($properties[$propertyName]));
        }
    }


    /**
     * @Then /^I expect the current Node to have the properties:$/
     * @param TableNode $expectedProperties
     */
    public function iExpectTheCurrentNodeToHaveTheProperties(TableNode $expectedProperties)
    {
        Assert::assertNotNull($this->currentNode, 'current node not found');
        $subgraph = $this->contentGraph
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints);
        $this->currentNode = $subgraph->findNodeByNodeAggregateIdentifier($this->currentNode->getNodeAggregateIdentifier());

        $this->currentReadModel = $this->readModelFactory->createReadModel($this->currentNode, $subgraph);

        $properties = $this->currentReadModel->getProperties();

        foreach ($expectedProperties->getHash() as $row) {
            Assert::assertTrue(isset($properties[$row['Key']]), 'Property "' . $row['Key'] . '" not found');
            if (isset($row['Type']) && $row['Type'] === 'DateTime') {
                $row['Value'] = \DateTime::createFromFormat(\DateTime::W3C, $row['Value']);
            }
            $actualProperty = $properties[$row['Key']];
            Assert::assertEquals($row['Value'], $actualProperty, 'Node property ' . $row['Key'] . ' does not match. Expected: ' . json_encode($row['Value']) . '; Actual: ' . json_encode($actualProperty));
        }
    }


}
