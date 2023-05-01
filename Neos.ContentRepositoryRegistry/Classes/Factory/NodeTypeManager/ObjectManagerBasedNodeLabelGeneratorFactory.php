<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\NodeTypeManager;

use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorFactoryInterface;
use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorInterface;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepositoryRegistry\NodeLabel\ExpressionBasedNodeLabelGenerator;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

class ObjectManagerBasedNodeLabelGeneratorFactory implements NodeLabelGeneratorFactoryInterface
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    public function create(NodeType $nodeType): NodeLabelGeneratorInterface
    {

        if ($nodeType->hasConfiguration('label.generatorClass')) {
            $nodeLabelGeneratorClassName = $nodeType->getConfiguration('label.generatorClass');
            $nodeLabelGenerator = $this->objectManager->get($nodeLabelGeneratorClassName);
            if (!$nodeLabelGenerator instanceof NodeLabelGeneratorInterface) {
                throw new \InvalidArgumentException(
                    'Configured class "' . $nodeLabelGeneratorClassName . '" does not implement the required '
                    . NodeLabelGeneratorInterface::class,
                    1682950942
                );
            }
        } elseif ($nodeType->hasConfiguration('label') && is_string($nodeType->getConfiguration('label'))) {
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
