<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepositoryRegistry\Command\SubprocessProjectionCatchUpCommandController;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;

/**
 * See {@see SubprocessProjectionCatchUpCommandController} for the inner part
 */
class SubprocessProjectionCatchUpTriggerFactory implements ProjectionCatchUpTriggerFactoryInterface
{
    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $options): ProjectionCatchUpTriggerInterface
    {
        return new SubprocessProjectionCatchUpTrigger($contentRepositoryIdentifier);
    }
}
