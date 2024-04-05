<?php
namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * Factory used to prepare a ContentGraphAdapterProvider for a content repository.
 * Any further dependencies can be injected via constructor injection in the implementation of this factory.
 */
interface ContentGraphAdapterProviderFactoryInterface
{
    /**
     * @param ContentRepositoryId $contentRepositoryId
     * @param array<mixed, mixed> $options
     * @return ContentGraphAdapterProviderInterface
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentGraphAdapterProviderInterface;
}
