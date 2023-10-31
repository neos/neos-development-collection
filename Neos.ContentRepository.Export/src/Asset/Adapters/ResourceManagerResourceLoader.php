<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset\Adapters;

use Neos\ContentRepository\Export\Asset\ResourceLoaderInterface;
use Neos\Flow\ResourceManagement\ResourceManager;

final class ResourceManagerResourceLoader implements ResourceLoaderInterface
{

    public function __construct(
        private readonly ResourceManager $resourceManager,
    ) {}

    public function getStreamBySha1(string $sha1)
    {
        $resource = $this->resourceManager->getResourceBySha1($sha1);
        if ($resource === null) {
            throw new \InvalidArgumentException(sprintf('Failed to find resource for SHA1 "%s"', $sha1), 1658583711);
        }
        $stream = $this->resourceManager->getStreamByResource($resource);
        if (!is_resource($stream)) {
            throw new \RuntimeException(sprintf('Failed to load file for persistent resource with SHA1 "%s"', $sha1), 1658583763);
        }
        return $stream;
    }
}
