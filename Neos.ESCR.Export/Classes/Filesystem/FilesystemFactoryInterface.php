<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Filesystem;

use League\Flysystem\Filesystem;

interface FilesystemFactoryInterface
{

    /**
     * @param array<string, mixed> $options
     * @return Filesystem
     */
    public function create(array $options): Filesystem;

}
