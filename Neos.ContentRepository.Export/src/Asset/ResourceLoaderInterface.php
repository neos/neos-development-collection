<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset;

interface ResourceLoaderInterface
{
    /**
     * @param string $sha1
     * @return resource
     */
    public function getStreamBySha1(string $sha1);
}
