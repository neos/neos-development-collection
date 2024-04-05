<?php
namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterFactoryInterface;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * This factory
 */
class ContentGraphAdapterFactory implements ContentGraphAdapterFactoryInterface
{
    public function __construct(
        readonly Connection $dbalConnection
    ) {
    }

    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentGraphAdapterInterface
    {
        $tableNamePrefix = DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix(
            $contentRepositoryId
        );
        return new ContentGraphAdapter($this->dbalConnection, $tableNamePrefix);
    }
}
