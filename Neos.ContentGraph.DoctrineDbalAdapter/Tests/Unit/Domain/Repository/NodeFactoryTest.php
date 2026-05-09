<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Tests\Domain\Repository;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

class NodeFactoryTest extends TestCase
{
    /** @test */
    public function nodeAggregatesAreBuiltOnlyWithTheActualOccupiedNodeRows(): void
    {
        $rows = <<<JSON
        [{
            "relationanchorpoint": 5,
            "nodeaggregateid": "nody-mc-nodeface",
            "origindimensionspacepointhash": "81e25df248aa99d536dba0be7ddf2ac5",
            "nodetypename": "Neos.ContentRepository.Testing:Document",
            "name": "child-document",
            "properties": "[]",
            "classification": "regular",
            "created": "2026-05-04 21:40:49",
            "originalcreated": "2026-05-04 21:40:49",
            "lastmodified": null,
            "originallastmodified": null,
            "contentstreamid": "cs-identifier",
            "subtreetags": "{}",
            "covereddimensionspacepoint": "{\"language\":\"de\"}"
        },
        {
            "relationanchorpoint": 5,
            "nodeaggregateid": "nody-mc-nodeface",
            "origindimensionspacepointhash": "81e25df248aa99d536dba0be7ddf2ac5",
            "nodetypename": "Neos.ContentRepository.Testing:Document",
            "name": "child-document",
            "properties": "[]",
            "classification": "regular",
            "created": "2026-05-04 21:40:49",
            "originalcreated": "2026-05-04 21:40:49",
            "lastmodified": null,
            "originallastmodified": null,
            "contentstreamid": "cs-identifier",
            "subtreetags": "{\"disabled\": null}",
            "covereddimensionspacepoint": "{\"language\":\"en\"}"
        },
        {
            "relationanchorpoint": 5,
            "nodeaggregateid": "nody-mc-nodeface",
            "origindimensionspacepointhash": "81e25df248aa99d536dba0be7ddf2ac5",
            "nodetypename": "Neos.ContentRepository.Testing:Document",
            "name": "child-document",
            "properties": "[]",
            "classification": "regular",
            "created": "2026-05-04 21:40:49",
            "originalcreated": "2026-05-04 21:40:49",
            "lastmodified": null,
            "originallastmodified": null,
            "contentstreamid": "cs-identifier",
            "subtreetags": "{}",
            "covereddimensionspacepoint": "{\"language\":\"ltz\"}"
        },
        {
            "relationanchorpoint": 5,
            "nodeaggregateid": "nody-mc-nodeface",
            "origindimensionspacepointhash": "81e25df248aa99d536dba0be7ddf2ac5",
            "nodetypename": "Neos.ContentRepository.Testing:Document",
            "name": "child-document",
            "properties": "[]",
            "classification": "regular",
            "created": "2026-05-04 21:40:49",
            "originalcreated": "2026-05-04 21:40:49",
            "lastmodified": null,
            "originallastmodified": null,
            "contentstreamid": "cs-identifier",
            "subtreetags": "{\"disabled\": null}",
            "covereddimensionspacepoint": "{\"language\":\"mul\"}"
        },
        {
            "relationanchorpoint": 5,
            "nodeaggregateid": "nody-mc-nodeface",
            "origindimensionspacepointhash": "81e25df248aa99d536dba0be7ddf2ac5",
            "nodetypename": "Neos.ContentRepository.Testing:Document",
            "name": "child-document",
            "properties": "[]",
            "classification": "regular",
            "created": "2026-05-04 21:40:49",
            "originalcreated": "2026-05-04 21:40:49",
            "lastmodified": null,
            "originallastmodified": null,
            "contentstreamid": "cs-identifier",
            "subtreetags": "{}",
            "covereddimensionspacepoint": "{\"language\":\"gsw\"}"
        }]
        JSON;

        $dspRepository = $this->getMockBuilder(DimensionSpacePointsRepository::class)->onlyMethods(['getOriginDimensionSpacePointByHash'])->disableOriginalConstructor()->disableAutoReturnValueGeneration()->getMock();

        $dspRepository->method('getOriginDimensionSpacePointByHash')->with('81e25df248aa99d536dba0be7ddf2ac5')->willReturn(OriginDimensionSpacePoint::fromArray(['language' => 'mul']));

        $nodeFactory = new NodeFactory(
            ContentRepositoryId::fromString('testing'),
            new PropertyConverter(new Serializer()),
            $dspRepository,
        );

        $nodeAggregate = $nodeFactory->mapNodeRowsToNodeAggregate(
            json_decode($rows, true),
            WorkspaceName::forLive(),
            VisibilityConstraints::createEmpty()
        );

        self::assertSame('nody-mc-nodeface', $nodeAggregate->nodeAggregateId->value);

        // As subtree tags reside on edges we must pick the correct node row to get this occupied node.
        // If we don't use the row where origindimensionspacepoint equals covereddimensionspacepoint we use subtree tags from a random other edge which is not expected.
        $mulVariant = $nodeAggregate->getNodeByOccupiedDimensionSpacePoint(OriginDimensionSpacePoint::fromArray(['language' => 'mul']));
        self::assertSame([], $mulVariant->tags->withoutInherited()->toStringArray());
        self::assertSame(['disabled'], $mulVariant->tags->onlyInherited()->toStringArray());
    }
}
