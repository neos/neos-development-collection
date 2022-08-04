<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset\Adapters;

use Neos\ContentRepository\Export\Asset\ResourceLoaderInterface;
use Neos\Utility\Files;

final class FileSystemResourceLoader implements ResourceLoaderInterface
{

    public function __construct(
        private readonly string $path,
    ) {}

    public function getStreamBySha1(string $sha1)
    {
        if (strlen($sha1) < 5) {
            throw new \InvalidArgumentException(sprintf('Specified SHA1 "%s" is too short', $sha1), 1658583570);
        }
        $resourcePath = Files::concatenatePaths([$this->path, $sha1[0], $sha1[1], $sha1[2], $sha1[3], $sha1]);
        if (!is_readable($resourcePath)) {
            throw new \RuntimeException(sprintf('Resource file "%s" is not readable', $resourcePath), 1658583621);
        }
        return fopen($resourcePath, 'rb');
    }
}
