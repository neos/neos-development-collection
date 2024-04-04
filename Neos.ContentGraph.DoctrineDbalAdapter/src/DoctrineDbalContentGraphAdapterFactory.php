<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\ContentGraphAdapter\ContentGraphAdapterFactoryInterface;

final class DoctrineDbalContentGraphAdapterFactory implements ContentGraphAdapterFactoryInterface
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient
    ) {
    }

    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $options): DoctrineDbalContentGraphAdapter
    {
        // TODO: Implement build() method.
    }
}
