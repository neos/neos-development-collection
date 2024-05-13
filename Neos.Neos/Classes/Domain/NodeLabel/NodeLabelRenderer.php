<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\NodeLabel;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * @internal please reference the interface {@see NodeLabelRendererInterface} instead.
 */
#[Flow\Scope('singleton')]
final readonly class NodeLabelRenderer implements NodeLabelRendererInterface
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry,
        private ObjectManagerInterface $objectManager
    ) {
    }

    public function renderNodeLabel(Node $node): NodeLabel
    {
        $nodeTypeManager = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId)->getNodeTypeManager();
        $nodeType = $nodeTypeManager->getNodeType($node->nodeTypeName)
            ?? $nodeTypeManager->getNodeType(NodeTypeNameFactory::forFallback());
        $generator = $this->getNodeLabelGeneratorForNodeType($nodeType);
        return $generator->getLabel($node);
    }

    private function getNodeLabelGeneratorForNodeType(?NodeType $nodeType): NodeLabelGeneratorInterface
    {
        if ($nodeType?->hasConfiguration('label.generatorClass')) {
            $nodeLabelGeneratorClassName = $nodeType->getConfiguration('label.generatorClass');
            $nodeLabelGenerator = $this->objectManager->get($nodeLabelGeneratorClassName);
            if (!$nodeLabelGenerator instanceof NodeLabelGeneratorInterface) {
                throw new \InvalidArgumentException(
                    'Configured class "' . $nodeLabelGeneratorClassName . '" does not implement the required '
                    . NodeLabelGeneratorInterface::class,
                    1682950942
                );
            }
        } elseif ($nodeType?->hasConfiguration('label') && is_string($nodeType->getConfiguration('label'))) {
            /** @var ExpressionBasedNodeLabelGenerator $nodeLabelGenerator */
            $nodeLabelGenerator = $this->objectManager->get(ExpressionBasedNodeLabelGenerator::class);
            $nodeLabelGenerator->setExpression($nodeType->getConfiguration('label'));
        } else {
            /** @var NodeLabelGeneratorInterface $nodeLabelGenerator */
            $nodeLabelGenerator = $this->objectManager->get(NodeLabelGeneratorInterface::class);
        }

        return $nodeLabelGenerator;
    }
}
