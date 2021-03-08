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

    /**
     * @var NodeAddress
     */
    protected $nodeAddressOfContextNode;
    /**
     * @var Workspace
     */
    protected $workspace;

    /**
     * EmulatedLegacyWorkspace constructor.
     * @param NodeAddress $nodeAddressOfContextNode
     */
    public function __construct(NodeAddress $nodeAddressOfContextNode)
    {
        $this->nodeAddressOfContextNode = $nodeAddressOfContextNode;
    }

    public function initializeObject()
    {
        $this->workspace = $this->workspaceFinder->findOneByName($this->nodeAddressOfContextNode->getWorkspaceName());
    }

    public function getBaseWorkspace(): EmulatedLegacyBaseWorkspace
    {
        $this->legacyLogger->info('context.workspace.baseWorkspace called', LogEnvironment::fromMethodName(__METHOD__));

        return new EmulatedLegacyBaseWorkspace($this->workspace);
    }

    public function __call($methodName, $args)
    {
        $this->legacyLogger->warning('context.workspace.* method not implemented', LogEnvironment::fromMethodName(EmulatedLegacyContext::class . '::' . $methodName));
        return null;
    }
}
