<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Eel\FlowQueryOperations;

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;

trait CanEvaluateNodeContextTrait
{
    /**
     * {@inheritdoc}
     *
     * @param array|\Traversable (or array-like object) $context onto which this operation should be applied
     */
    public function checkContextForNodeInterface($context): bool
    {
        if (is_array($context)) {
            return count($context) > 0 ? reset($context) instanceof NodeInterface : true;
        } elseif ($context instanceof \Traversable) {
            foreach ($context as $item) {
                return $item instanceof NodeInterface;
            }
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param array|\Traversable (or array-like object) $context onto which this operation should be applied
     */
    public function checkContextForTraversableNodeInterface($context): bool
    {
        if (is_array($context)) {
            return count($context) > 0 ? reset($context) instanceof TraversableNodeInterface : true;
        } elseif ($context instanceof \Traversable) {
            foreach ($context as $item) {
                return $item instanceof TraversableNodeInterface;
            }
            return true;
        }
        return false;
    }
}
