<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\LegacyNodeInterfaceApi;

use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;

/**
 * @Flow\Aspect
 * @Flow\Introduce("class(Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode)", interfaceName="Neos\EventSourcedContentRepository\LegacyApi\LegacyNodeInterfaceApi\LegacyNodeInterfaceApi")
 */
class ImplementLegacyApiInTraversableNodeInterfaceAspect
{

    /**
     * @Flow\Inject
     * @var LegacyLoggerInterface
     */
    protected $legacyLogger;

    /**
     * @Flow\Around("method(Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode->getIdentifier())")
     */
    public function getIdentifier(\Neos\Flow\AOP\JoinPointInterface $joinPoint)
    {
        $this->legacyLogger->info('NodeInterface.getIdentifier() called', LogEnvironment::fromMethodName(LegacyNodeInterfaceApi::class . '::getIdentifier'));

        /* @var TraversableNode $traversableNode */
        $traversableNode = $joinPoint->getProxy();
        return $traversableNode->getNodeAggregateIdentifier()->jsonSerialize();
    }

    /**
     * @Flow\Around("method(Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode->getDepth())")
     */
    public function getDepth(\Neos\Flow\AOP\JoinPointInterface $joinPoint)
    {
        $this->legacyLogger->info('NodeInterface.getDepth() called', LogEnvironment::fromMethodName(LegacyNodeInterfaceApi::class . '::getDepth'));

        /* @var TraversableNode $traversableNode */
        $traversableNode = $joinPoint->getProxy();
        return $traversableNode->findNodePath()->getDepth();
    }
}
