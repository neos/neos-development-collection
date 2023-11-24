<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\EventStore;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\EventStore\EventStoreInterface;
use Psr\Clock\ClockInterface;

interface EventStoreFactoryInterface
{
    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options, ClockInterface $clock): EventStoreInterface;
}
