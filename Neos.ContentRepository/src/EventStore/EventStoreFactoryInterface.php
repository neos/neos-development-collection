<?php
declare(strict_types=1);
namespace Neos\ContentRepository\EventStore;

use Neos\ContentRepository\ValueObject\ContentRepositoryId;
use Neos\EventStore\EventStoreInterface;

interface EventStoreFactoryInterface
{
    /**
     * @param ContentRepositoryId $contentRepositoryId
     * @param array<mixed> $options
     * @return EventStoreInterface
     */
    public function create(ContentRepositoryId $contentRepositoryId, array $options): EventStoreInterface;
}
