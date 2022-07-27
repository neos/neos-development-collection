<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\EventStore;

use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\EventStore\EventStoreInterface;

interface EventStoreFactoryInterface
{
    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $eventStoreSettings): EventStoreInterface;
}
