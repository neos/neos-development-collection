<?php
declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Command;

/*
 * This file is part of the Neos.ContentRepository.BehavioralTests package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjector;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\Flow\Cli\CommandController;

final class ContentStreamCommandController extends CommandController
{
    private GraphProjector $graphProjector;

    private HypergraphProjector $hypergraphProjector;

    private ContentStreamIdentifier $contentStreamIdentifier;

    private DimensionSpacePointSet $dimensionSpacePoints;

    public function __construct(GraphProjector $graphProjector, HypergraphProjector $hypergraphProjector)
    {
        $this->graphProjector = $graphProjector;
        $this->hypergraphProjector = $hypergraphProjector;
        $this->contentStreamIdentifier = ContentStreamIdentifier::fromString('cs-identifier');
        $this->dimensionSpacePoints = new DimensionSpacePointSet([
            new DimensionSpacePoint(['language' => 'mul']),
            new DimensionSpacePoint(['language' => 'de']),
            new DimensionSpacePoint(['language' => 'gsw']),
            new DimensionSpacePoint(['language' => 'en']),
            new DimensionSpacePoint(['language' => 'fr'])
        ]);
        parent::__construct();
    }

    /**
     * @throws \Throwable
     */
    public function preparePerformanceTestCommand(int $nodesPerLevel, int $levels): void
    {
        $this->graphProjector->reset();
        $this->hypergraphProjector->reset();
        $rootNodeAggregateIdentifier = NodeAggregateIdentifier::fromString('lady-eleonode-rootford');
        $rootNodeAggregateWasCreated = new RootNodeAggregateWithNodeWasCreated(
            $this->contentStreamIdentifier,
            $rootNodeAggregateIdentifier,
            NodeTypeName::fromString('Neos.ContentRepository:Root'),
            $this->dimensionSpacePoints,
            NodeAggregateClassification::root(),
            UserIdentifier::forSystemUser()
        );
        $this->graphProjector->whenRootNodeAggregateWithNodeWasCreated($rootNodeAggregateWasCreated);
        $this->hypergraphProjector->whenRootNodeAggregateWithNodeWasCreated($rootNodeAggregateWasCreated);
        #$time = microtime(true);
        $this->createHierarchy($rootNodeAggregateIdentifier, 1, $levels, $nodesPerLevel);
        #$this->outputLine(microtime(true) - $time . ' elapsed');
    }

    /**
     * @throws \Throwable
     */
    private function createHierarchy(NodeAggregateIdentifier $parentNodeAggregateIdentifier, int $currentLevel, int $maximumLevel, int $numberOfNodes): void
    {
        if ($currentLevel <= $maximumLevel) {
            for ($i = 0; $i < $numberOfNodes; $i++) {
                $nodeAggregateIdentifier = NodeAggregateIdentifier::create();
                $nodeAggregateWasCreated = new NodeAggregateWithNodeWasCreated(
                    $this->contentStreamIdentifier,
                    $nodeAggregateIdentifier,
                    NodeTypeName::fromString('Neos.ContentRepository:Testing'),
                    new OriginDimensionSpacePoint(['language' => 'mul']),
                    $this->dimensionSpacePoints,
                    $parentNodeAggregateIdentifier,
                    null,
                    SerializedPropertyValues::fromArray([]),
                    NodeAggregateClassification::regular(),
                    UserIdentifier::forSystemUser()
                );
                $this->graphProjector->whenNodeAggregateWithNodeWasCreated($nodeAggregateWasCreated);
                $this->hypergraphProjector->whenNodeAggregateWithNodeWasCreated($nodeAggregateWasCreated);
                $this->createHierarchy($nodeAggregateIdentifier, $currentLevel+1, $maximumLevel, $numberOfNodes);
            }
        }
    }

    /**
     * @throws \Throwable
     */
    public function testPerformanceCommand(string $projectorName): void
    {
        $contentStreamWasForked = new ContentStreamWasForked(
            ContentStreamIdentifier::create(),
            $this->contentStreamIdentifier,
            1,
            UserIdentifier::forSystemUser()
        );
        $time = microtime(true);
        if ($projectorName === 'graph') {
            $this->graphProjector->whenContentStreamWasForked($contentStreamWasForked);
        } elseif ($projectorName === 'hypergraph') {
            $this->hypergraphProjector->whenContentStreamWasForked($contentStreamWasForked);
        }
        $timeElapsed = microtime(true) - $time;
        $this->outputLine($projectorName . ': ' . $timeElapsed);
    }
}
