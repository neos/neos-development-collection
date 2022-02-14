<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Security\Authorization\PrivilegeManager;
use Neos\Flow\Security\Exception;

class EmulatedLegacyContext
{
    /**
     * @var NodeInterface
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

    public function __construct(NodeInterface $traversableNode)
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
            return $workspaceName->name;
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

    public function getCurrentSite(): EmulatedLegacySite
    {
        $this->legacyLogger->info('context.currentSite called', LogEnvironment::fromMethodName(__METHOD__));

        return new EmulatedLegacySite($this->traversableNode);
    }

    private function getNodeAddressOfContextNode(): NodeAddress
    {
        return $this->nodeAddressFactory->createFromNode($this->traversableNode);
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
