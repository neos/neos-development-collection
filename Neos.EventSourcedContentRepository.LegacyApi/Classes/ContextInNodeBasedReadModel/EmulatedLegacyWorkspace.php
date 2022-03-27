<?php


namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\Workspace;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;

class EmulatedLegacyWorkspace
{
    /**
     * @Flow\Inject
     * @var LegacyLoggerInterface
     */
    protected $legacyLogger;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    protected NodeAddress $nodeAddressOfContextNode;

    protected ?Workspace $workspace;

    public function __construct(NodeAddress $nodeAddressOfContextNode)
    {
        $this->nodeAddressOfContextNode = $nodeAddressOfContextNode;
    }

    public function initializeObject(): void
    {
        $this->workspace = $this->nodeAddressOfContextNode->workspaceName
            ? $this->workspaceFinder->findOneByName($this->nodeAddressOfContextNode->workspaceName)
            : null;
    }

    public function getBaseWorkspace(): ?EmulatedLegacyBaseWorkspace
    {
        $this->legacyLogger->info('context.workspace.baseWorkspace called', LogEnvironment::fromMethodName(__METHOD__));

        return !is_null($this->workspace)
            ? new EmulatedLegacyBaseWorkspace($this->workspace)
            : null;
    }

    /**
     * @param string $methodName
     * @param array<int|string,mixed> $args
     * @return null
     */
    public function __call($methodName, $args)
    {
        $this->legacyLogger->warning(
            'context.workspace.* method not implemented',
            LogEnvironment::fromMethodName(EmulatedLegacyContext::class . '::' . $methodName)
        );
        return null;
    }
}
