<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\EventStore;

use Neos\ContentRepository\Core\Factory\ContentRepositoryIdentifier;
use Neos\EventStore\EventStoreInterface;

interface EventStoreFactoryInterface
{
    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings, array $eventStorePreset): EventStoreInterface;
}
