<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Processors;

use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;

/**
 * @internal
 */
final readonly class SubscriptionReplayProcessor implements ProcessorInterface
{
    public function __construct(
        private ContentRepositoryMaintainer $contentRepositoryMaintainer,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $this->contentRepositoryMaintainer->replayAllSubscriptions();
    }
}
