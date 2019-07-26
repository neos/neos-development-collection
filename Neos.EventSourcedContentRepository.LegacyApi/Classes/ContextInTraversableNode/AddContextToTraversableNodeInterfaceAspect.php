<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInTraversableNode;

use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Aspect
 * @Flow\Introduce("class(Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode)", interfaceName="Neos\EventSourcedContentRepository\LegacyApi\ContextInTraversableNode\ContextInTraversableNodeInterface")
 */
class AddContextToTraversableNodeInterfaceAspect
{
    /**
     * @Flow\Around("method(Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode->getContext())")
     */
    public function newMethodImplementation(\Neos\Flow\AOP\JoinPointInterface $joinPoint)
    {
        /* @var TraversableNode $traversableNode */
        $traversableNode = $joinPoint->getProxy();
        return new EmulatedLegacyContext($traversableNode);
    }
}
