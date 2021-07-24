<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\AOP\JoinPointInterface;

/**
 * @Flow\Aspect
 * @Flow\Introduce("class(Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Node)", interfaceName="Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel\ContextInNodeBasedReadModelInterface")
 */
class AddContextToNodeBasedReadModelInterfaceAspect
{
    /**
     * @Flow\Around("method(Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Node->getContext())")
     */
    public function newMethodImplementation(JoinPointInterface $joinPoint): EmulatedLegacyContext
    {
        /* @var \Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface $node */
        $node = $joinPoint->getProxy();

        return new EmulatedLegacyContext($node);
    }
}
