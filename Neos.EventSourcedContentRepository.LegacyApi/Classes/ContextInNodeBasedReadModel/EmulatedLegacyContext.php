<?php

declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Security\Authorization\PrivilegeManager;
use Neos\Flow\Security\Exception;

class EmulatedLegacyContext
{
    /**
     * @var Node
     */
    protected $node;

    /**
     * @Flow\Inject
     * @var LegacyLoggerInterface
     */
    protected $legacyLogger;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var PrivilegeManager
     */
    protected $privilegeManager;

    public function __construct(Node $node)
    {
        $this->node = $node;
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

        return $this->getNodeAddressOfContextNode()->workspaceName->name;
    }

    public function getWorkspace(): EmulatedLegacyWorkspace
    {
        $this->legacyLogger->info('context.workspace called', LogEnvironment::fromMethodName(__METHOD__));

        return new EmulatedLegacyWorkspace(
            $this->node->subgraphIdentity->contentRepositoryIdentifier,
            $this->getNodeAddressOfContextNode()
        );
    }

    public function getCurrentSite(): EmulatedLegacySite
    {
        $this->legacyLogger->info('context.currentSite called', LogEnvironment::fromMethodName(__METHOD__));

        return new EmulatedLegacySite($this->node);
    }

    private function getNodeAddressOfContextNode(): NodeAddress
    {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $this->node->subgraphIdentity->contentRepositoryIdentifier
        );
        return NodeAddressFactory::create($contentRepository)->createFromNode($this->node);
    }

    private function hasAccessToBackend(): bool
    {
        try {
            return $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess');
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param string $methodName
     * @param array<int,string|mixed> $args
     * @return null
     */
    public function __call($methodName, $args)
    {
        $this->legacyLogger->warning(
            'Context method not implemented',
            LogEnvironment::fromMethodName(EmulatedLegacyContext::class . '::' . $methodName)
        );
        return null;
    }
}
