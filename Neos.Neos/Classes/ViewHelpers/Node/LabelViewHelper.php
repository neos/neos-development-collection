<?php

namespace Neos\Neos\ViewHelpers\Node;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;

/**
 * Viewhelper to render a label for a given Node
 */
class LabelViewHelper extends AbstractViewHelper
{
    #[Flow\Inject()]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('node', Node::class, 'Node', true);
    }

    public function render(): string
    {
        /** @var Node $node */
        $node = $this->arguments['node'];
        return $this->nodeLabelGenerator->getLabel($node);
    }
}
