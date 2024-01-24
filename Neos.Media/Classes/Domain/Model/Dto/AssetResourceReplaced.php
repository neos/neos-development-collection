<?php
namespace Neos\Media\Domain\Model\Dto;

use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\AssetInterface;

/**
 *
 */
final readonly class AssetResourceReplaced
{
    public function __construct(
        public AssetInterface $asset,
        public PersistentResource $previousResource,
        public PersistentResource $newResource
    ) {
    }
}
