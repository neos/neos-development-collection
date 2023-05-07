<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Eel\FlowQueryOperations;

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;

trait CanEvaluateNodeContextTrait
{
    /**
     * @param $context
     * @return bool
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
     * @param $context
     * @return bool
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
