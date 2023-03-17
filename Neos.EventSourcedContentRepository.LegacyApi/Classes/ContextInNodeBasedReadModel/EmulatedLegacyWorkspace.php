<?php

declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
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
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    protected ?Workspace $workspace;

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeAddress $nodeAddressOfContextNode
    ) {
    }

    public function getBaseWorkspace(): ?EmulatedLegacyBaseWorkspace
    {
        $this->legacyLogger->info('context.workspace.baseWorkspace called', LogEnvironment::fromMethodName(__METHOD__));

        if ($this->workspace === null) {
            $contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryId);
            $this->workspace = $contentRepository->getWorkspaceFinder()
                ->findOneByName($this->nodeAddressOfContextNode->workspaceName);
        }

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
