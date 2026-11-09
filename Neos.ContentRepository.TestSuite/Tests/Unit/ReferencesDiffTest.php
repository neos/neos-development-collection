<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Tests\Unit;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\NodeDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\ReferenceDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\ReferenceDiscriminator;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\ReferenceDiscriminators;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\ReferencesDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\SampleNodeFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ReferencesDiffTest extends TestCase
{
    /**
     * @dataProvider referencesComparisonProvider
     */
    public function testFromNodesComparison(
        References $referenceReferences,
        References $referencesToCompare,
        ?ReferencesDiff $expectedReferencesDiff,
    ): void {
        Assert::assertEquals(
            $expectedReferencesDiff,
            ReferencesDiff::tryFromReferencesComparison($referencesToCompare, $referenceReferences, null)
        );
    }

    public static function referencesComparisonProvider(): iterable
    {
        yield 'emptyReferences' => [
            'referenceReferences' => References::fromArray([]),
            'referencesToCompare' => References::fromArray([]),
            'expectedReferencesDiff' => null,
        ];

        $sampleReference = SampleNodeFactory::createSampleReference();

        yield 'identicalReferences' => [
            'referenceReferences' => References::fromArray([$sampleReference]),
            'referencesToCompare' => References::fromArray([$sampleReference]),
            'expectedReferencesDiff' => null,
        ];

        $anotherReference = SampleNodeFactory::modifyReferenceWith(
            reference: $sampleReference,
            node: SampleNodeFactory::modifyNodeWith(
                $sampleReference->node,
                aggregateId: NodeAggregateId::fromString('another'),
            ),
        );

        yield 'onlyReorderedReferences' => [
            'referenceReferences' => References::fromArray([$sampleReference, $anotherReference]),
            'referencesToCompare' => References::fromArray([$anotherReference, $sampleReference]),
            'expectedReferencesDiff' => ReferencesDiff::tryCreate(
                references: [
                    ReferenceDiscriminator::fromReference($anotherReference),
                    ReferenceDiscriminator::fromReference($sampleReference),
                ],
            ),
        ];

        $referenceToModify = SampleNodeFactory::modifyReferenceWith(
            reference: $sampleReference,
            node: SampleNodeFactory::modifyNodeWith(
                node: $sampleReference->node,
                aggregateId: NodeAggregateId::fromString('modify'),
            ),
        );
        $modifiedReference = SampleNodeFactory::modifyReferenceWith(
            reference: $referenceToModify,
            node: SampleNodeFactory::modifyNodeWith(
                node: $referenceToModify->node,
                nodeTypeName: NodeTypeName::fromString('Neos.ContentRepositry:OtherTesting'),
            ),
        );

        yield 'onlyDifferingReferences' => [
            'referenceReferences' => References::fromArray([
                $sampleReference,
                $anotherReference,
                $referenceToModify,
            ]),
            'referencesToCompare' => References::fromArray([
                $anotherReference,
                $sampleReference,
                $modifiedReference,
            ]),
            'expectedReferencesDiff' => ReferencesDiff::tryCreate(
                references: [
                    ReferenceDiscriminator::fromReference($anotherReference),
                    ReferenceDiscriminator::fromReference($sampleReference),
                    ReferenceDiff::tryCreate(
                        node: NodeDiff::tryCreate(
                            discriminator: $modifiedReference->node->aggregateId,
                            nodeTypeName: NodeTypeName::fromString('Neos.ContentRepositry:OtherTesting'),
                        )
                    ),
                ],
            )
        ];

        $referenceToRemove = SampleNodeFactory::modifyReferenceWith(
            reference: $sampleReference,
            node: SampleNodeFactory::modifyNodeWith(
                node: $sampleReference->node,
                aggregateId: NodeAggregateId::fromString('remove'),
            )
        );

        yield 'differingAndRemovedReferences' => [
            'referenceReferences' => References::fromArray([
                $sampleReference,
                $anotherReference,
                $referenceToRemove,
                $referenceToModify,
            ]),
            'referencesToCompare' => References::fromArray([
                $anotherReference,
                $sampleReference,
                $modifiedReference,
            ]),
            'expectedReferencesDiff' => ReferencesDiff::tryCreate(
                references: [
                    ReferenceDiscriminator::fromReference($anotherReference),
                    ReferenceDiscriminator::fromReference($sampleReference),
                    ReferenceDiff::tryCreate(
                        node: NodeDiff::tryCreate(
                            discriminator: $modifiedReference->node->aggregateId,
                            nodeTypeName: NodeTypeName::fromString('Neos.ContentRepositry:OtherTesting'),
                        )
                    ),
                ],
                removedReferences: ReferenceDiscriminators::list(
                    ReferenceDiscriminator::fromReference($referenceToRemove),
                )
            )
        ];
    }
}
