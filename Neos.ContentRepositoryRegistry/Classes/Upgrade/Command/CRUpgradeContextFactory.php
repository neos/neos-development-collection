<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\Command;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\CRUpgradeContext;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\Flow\Annotations as Flow;

/**
 * @implements ContentRepositoryServiceFactoryInterface<CRUpgradeContext>
 * @internal CR upgrade internals
 */
#[Flow\Scope("singleton")]
final class CRUpgradeContextFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        if (!($serviceFactoryDependencies->eventStore instanceof DoctrineEventStore)) {
            throw new \RuntimeException('CR Upgrade only works with DoctrineEventStore, ' . get_class($serviceFactoryDependencies->eventStore) . ' given');
        }

        return new CRUpgradeContext(
            $serviceFactoryDependencies->contentRepositoryId,
            $serviceFactoryDependencies->eventStore,
            $this->connection
        );
    }
}
