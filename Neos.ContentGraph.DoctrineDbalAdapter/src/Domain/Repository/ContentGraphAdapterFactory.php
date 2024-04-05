<?php
namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjectionFactory;
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

    public function build(ContentRepositoryId $contentRepositoryId): ContentGraphAdapterInterface
    {
        $tableNamePrefix = DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix(
            $contentRepositoryId
        );
        return new ContentGraphAdapter($this->dbalConnection, $tableNamePrefix);
    }
}
