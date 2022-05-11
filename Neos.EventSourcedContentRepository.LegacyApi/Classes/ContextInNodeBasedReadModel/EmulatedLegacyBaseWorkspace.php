<?php

declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;

class EmulatedLegacyBaseWorkspace
{
    /**
     * @Flow\Inject
     * @var LegacyLoggerInterface
     */
    protected $legacyLogger;

    /**
     * @var Workspace
     */
    protected $childWorkspace;

    /**
     * EmulatedLegacyBaseWorkspace constructor.
     * @param Workspace $childWorkspace
     */
    public function __construct(Workspace $childWorkspace)
    {
        $this->childWorkspace = $childWorkspace;
    }

    public function getName(): string
    {
        $this->legacyLogger->info(
            'context.workspace.baseWorkspace.name called',
            LogEnvironment::fromMethodName(__METHOD__)
        );

        return (string)$this->childWorkspace->getBaseWorkspaceName();
    }

    /**
     * @param string $methodName
     * @param array<int|string,mixed> $args
     * @return null
     */
    public function __call($methodName, $args)
    {
        $this->legacyLogger->warning(
            'context.workspace.baseWorkspace.* method not implemented',
            LogEnvironment::fromMethodName(EmulatedLegacyContext::class . '::' . $methodName)
        );
        return null;
    }
}
