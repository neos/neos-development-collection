<?php
declare(strict_types=1);


use Neos\ContentRepository\Intermediary\Domain\AbstractReadModel;
use Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel\EmulatedLegacyContext;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\AOP\JoinPointInterface;

/**
 * @Flow\Aspect
 * @Flow\Introduce("class(Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Node)", interfaceName="Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel\ContextInNodeBasedReadModelInterface")
 */
class AddContextToNodeBasedReadModelInterfaceAspect
{
    /**
     * @Flow\Around("method(Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface->getContext())")
     */
    public function newMethodImplementation(JoinPointInterface $joinPoint): EmulatedLegacyContext
    {
        /* @var \Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface $node */
        $node = $joinPoint->getProxy();

        return new EmulatedLegacyContext($node);
    }
}
