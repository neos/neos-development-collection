<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Filesystem;

use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;

final class ZipArchiveFactory implements FilesystemFactoryInterface
{

    /**
     * @param array{filename?: string, localDirectoryPermissions?: int} $options
     * @return Filesystem
     */
    public function create(array $options): Filesystem
    {
        if (!isset($options['filename'])) {
            throw new \InvalidArgumentException('Missing option "filename"', 1646402533);
        }
        $provider = new FilesystemZipArchiveProvider($options['filename'], $options['localDirectoryPermissions'] ?? 0700);
        $adapter = new ZipArchiveAdapter($provider);
        return new Filesystem($adapter);
    }
}
