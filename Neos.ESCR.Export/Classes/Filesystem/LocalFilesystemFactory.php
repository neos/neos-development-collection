<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Filesystem;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

final class LocalFilesystemFactory implements FilesystemFactoryInterface
{

    /**
     * @param array{location?: string} $options
     * @return Filesystem
     */
    public function create(array $options): Filesystem
    {
        if (!isset($options['location'])) {
            throw new \InvalidArgumentException('Missing option "location"', 1646389794);
        }
        $adapter = new LocalFilesystemAdapter($options['location']);
        return new Filesystem($adapter);
    }
}
