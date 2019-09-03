<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInTraversableNode;

use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Security\Authorization\PrivilegeManager;
use Neos\Flow\Security\Exception;

class EmulatedLegacyContext
{

    /**
     * @var TraversableNode
     */
    protected $traversableNode;

    /**
     * @Flow\Inject
     * @var LegacyLoggerInterface
     */
    protected $legacyLogger;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var PrivilegeManager
     */
    protected $privilegeManager;

    /**
     * ContextPlaceholder constructor.
     * @param TraversableNode $traversableNode
     */
    public function __construct(TraversableNode $traversableNode)
    {
        $this->traversableNode = $traversableNode;
    }

    public function isInBackend(): bool
    {
        return $this->getInBackend();
    }

    public function getInBackend(): bool
    {
        $this->legacyLogger->info('context.inBackend called', LogEnvironment::fromMethodName(__METHOD__));
        $nodeAddress = $this->getNodeAddressOfContextNode();

        return (!$nodeAddress->isInLiveWorkspace() && $this->hasAccessToBackend());
    }


    public function isLive(): bool
    {
        return $this->getLive();
    }

    public function getLive(): bool
    {
        $this->legacyLogger->info('context.live called', LogEnvironment::fromMethodName(__METHOD__));
        $nodeAddress = $this->getNodeAddressOfContextNode();

        return $nodeAddress->isInLiveWorkspace();
    }


    public function getWorkspaceName(): ?string
    {
        $this->legacyLogger->info('context.workspaceName called', LogEnvironment::fromMethodName(__METHOD__));

        $workspaceName = $this->getNodeAddressOfContextNode()->getWorkspaceName();
        if ($workspaceName) {
            return $workspaceName->getName();
        }
        return null;
    }

    public function getWorkspace(): EmulatedLegacyWorkspace
    {
        $this->legacyLogger->info('context.workspace called', LogEnvironment::fromMethodName(__METHOD__));

        return new EmulatedLegacyWorkspace($this->getNodeAddressOfContextNode());
    }

    public function __call($methodName, $args)
    {
        $this->legacyLogger->warning('Context method not implemented', LogEnvironment::fromMethodName(EmulatedLegacyContext::class . '::' . $methodName));
        return null;
    }

    private function getNodeAddressOfContextNode(): NodeAddress
    {
        return $this->nodeAddressFactory->createFromTraversableNode($this->traversableNode);
    }

    private function hasAccessToBackend(): bool
    {
        try {
            return $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess');
        } catch (Exception $exception) {
            return false;
        }
    }
}
