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
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function checkContextForNodeInterface($context)
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
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function checkContextForTraversableNodeInterface($context)
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
