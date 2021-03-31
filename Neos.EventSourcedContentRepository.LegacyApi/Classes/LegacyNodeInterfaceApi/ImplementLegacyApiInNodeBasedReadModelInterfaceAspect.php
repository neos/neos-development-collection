<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\LegacyNodeInterfaceApi;

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;

/**
 * @Flow\Aspect
 * @Flow\Introduce("class(Neos\ContentRepository\Intermediary\Domain\AbstractReadModel)", interfaceName="Neos\EventSourcedContentRepository\LegacyApi\LegacyNodeInterfaceApi\LegacyNodeInterfaceApi")
 */
class ImplementLegacyApiInNodeBasedReadModelInterfaceAspect
{
    /**
     * @Flow\Inject
     * @var LegacyLoggerInterface
     */
    protected $legacyLogger;

    /**
     * @Flow\Around("method(Neos\ContentRepository\Intermediary\Domain\AbstractReadModel->getIdentifier())")
     */
    public function getIdentifier(\Neos\Flow\AOP\JoinPointInterface $joinPoint)
    {
        $this->legacyLogger->info('NodeInterface.getIdentifier() called', LogEnvironment::fromMethodName(LegacyNodeInterfaceApi::class . '::getIdentifier'));

        /* @var NodeBasedReadModelInterface $traversableNode */
        $traversableNode = $joinPoint->getProxy();
        return $traversableNode->getNodeAggregateIdentifier()->jsonSerialize();
    }

    /**
     * @Flow\Around("method(Neos\ContentRepository\Intermediary\Domain\AbstractReadModel->getDepth())")
     */
    public function getDepth(\Neos\Flow\AOP\JoinPointInterface $joinPoint)
    {
        $this->legacyLogger->info('NodeInterface.getDepth() called', LogEnvironment::fromMethodName(LegacyNodeInterfaceApi::class . '::getDepth'));

        /* @var NodeBasedReadModelInterface $traversableNode */
        $traversableNode = $joinPoint->getProxy();
        return $traversableNode->findNodePath()->getDepth();
    }
}
