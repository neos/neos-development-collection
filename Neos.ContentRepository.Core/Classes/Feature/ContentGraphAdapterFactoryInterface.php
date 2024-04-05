<?php
namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * An implementation detail for the write side of the content repository, providing low level read operations
 * to facilitate constraint checks and similar.
 *
 * This needs to be bound to a content repository on creation, so the implementations constructor should
 */
interface ContentGraphAdapterFactoryInterface
{
    /**
     * @param ContentRepositoryId $contentRepositoryId
     * @param array<mixed, mixed> $options
     * @return ContentGraphAdapterInterface
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentGraphAdapterInterface;
}
