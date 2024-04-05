<?php
namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterProviderFactoryInterface;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterProviderInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * This factory resolves low level dependencies for the ContentGraphAdapterProvider implementation
 */
class ContentGraphAdapterProviderFactory implements ContentGraphAdapterProviderFactoryInterface
{
    public function __construct(
        readonly Connection $dbalConnection
    ) {
    }

    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentGraphAdapterProviderInterface
    {
        $tableNamePrefix = DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix(
            $contentRepositoryId
        );
        return new ContentGraphAdapterProvider($this->dbalConnection, $tableNamePrefix);
    }
}
