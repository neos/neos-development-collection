<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Asset;

interface ResourceLoaderInterface
{
    /**
     * @return resource
     */
    public function getStreamBySha1(string $sha1);
}
