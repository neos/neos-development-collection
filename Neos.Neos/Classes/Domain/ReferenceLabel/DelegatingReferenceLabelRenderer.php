<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\ReferenceLabel;

use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * @internal please reference the interface {@see ReferenceLabelGeneratorInterface} instead.
 */
#[Flow\Scope('singleton')]
final readonly class DelegatingReferenceLabelRenderer implements ReferenceLabelGeneratorInterface
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry,
        private ObjectManagerInterface $objectManager
    ) {
    }

    public function getLabel(Reference $reference): string
    {
        $referenceTypeGraph = $this->contentRepositoryRegistry->get($reference->node->contentRepositoryId)->getReferenceTypeGraph();
        $referenceType = $referenceTypeGraph->getReferenceType($reference->referenceTypeName);
        $generator = $this->getDelegatedGenerator($referenceType);
        if ($generator instanceof self) {
            throw new \RuntimeException(
                'Recursion detected, cannot specify DelegatingReferenceLabelRenderer as generatorClass for reference as this is the default.',
                1795596688
            );
        }
        return $generator->getLabel($reference);
    }

    private function getDelegatedGenerator(?ReferenceType $referenceType): ReferenceLabelGeneratorInterface
    {
        /** @var ?ReferenceLabelMetadata $labelMetadata */
        $labelMetadata = $referenceType?->metadata->getItem(ReferenceLabelMetadata::class);
        if ($labelMetadata?->generatorClassName) {
            $referenceLabelGenerator = $this->objectManager->get($labelMetadata->generatorClassName);
            if (!$referenceLabelGenerator instanceof ReferenceLabelGeneratorInterface) {
                throw new \InvalidArgumentException(
                    'Configured class "' . $labelMetadata->generatorClassName . '" does not implement the required '
                        . ReferenceLabelGeneratorInterface::class,
                    1795619184
                );
            }
        } elseif ($labelMetadata?->label) {
            $referenceLabelGenerator = $this->objectManager->get(ExpressionBasedReferenceLabelGenerator::class);
            $referenceLabelGenerator->setExpression($labelMetadata->label);
        } else {
            $referenceLabelGenerator = new class () implements ReferenceLabelGeneratorInterface {
                public function getLabel(Reference $reference): string
                {
                    return $reference->referenceTypeName->value;
                }
            };
        }

        return $referenceLabelGenerator;
    }
}
