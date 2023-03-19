<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\EventStore;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\EventStore\EventStoreInterface;

interface EventStoreFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $eventStorePreset): EventStoreInterface;
}
