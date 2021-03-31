<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Security\Authorization\PrivilegeManager;
use Neos\Flow\Security\Exception;

class EmulatedLegacyContext
{
    /**
     * @var NodeBasedReadModelInterface
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

    public function __construct(NodeBasedReadModelInterface $traversableNode)
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
        $nodeAddress = $this->traversableNode->getAddress();

        return (!$nodeAddress->isInLiveWorkspace() && $this->hasAccessToBackend());
    }


    public function isLive(): bool
    {
        return $this->getLive();
    }

    public function getLive(): bool
    {
        $this->legacyLogger->info('context.live called', LogEnvironment::fromMethodName(__METHOD__));
        $nodeAddress = $this->traversableNode->getAddress();

        return $nodeAddress->isInLiveWorkspace();
    }


    public function getWorkspaceName(): ?string
    {
        $this->legacyLogger->info('context.workspaceName called', LogEnvironment::fromMethodName(__METHOD__));

        $workspaceName = $this->traversableNode->getAddress()->getWorkspaceName();
        if ($workspaceName) {
            return $workspaceName->getName();
        }
        return null;
    }

    public function getWorkspace(): EmulatedLegacyWorkspace
    {
        $this->legacyLogger->info('context.workspace called', LogEnvironment::fromMethodName(__METHOD__));

        return new EmulatedLegacyWorkspace($this->traversableNode->getAddress());
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

    private function hasAccessToBackend(): bool
    {
        try {
            return $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess');
        } catch (Exception $exception) {
            return false;
        }
    }
}
