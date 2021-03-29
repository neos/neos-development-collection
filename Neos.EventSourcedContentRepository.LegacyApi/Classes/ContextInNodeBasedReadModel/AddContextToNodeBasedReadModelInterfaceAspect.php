<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\ContentRepository\Intermediary\Domain\AbstractReadModel;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\AOP\JoinPointInterface;

/**
 * @Flow\Aspect
 * @Flow\Introduce("class(Neos\ContentRepository\Intermediary\Domain\AbstractReadModel)", interfaceName="Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel\ContextInNodeBasedReadModelInterface")
 */
class AddContextToNodeBasedReadModelInterfaceAspect
{
    /**
     * @Flow\Around("method(Neos\ContentRepository\Intermediary\Domain\AbstractReadModel->getContext())")
     */
    public function newMethodImplementation(JoinPointInterface $joinPoint): EmulatedLegacyContext
    {
        /* @var AbstractReadModel $traversableNode */
        $traversableNode = $joinPoint->getProxy();

        return new EmulatedLegacyContext($traversableNode);
    }
}
