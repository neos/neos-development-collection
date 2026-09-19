<?php

declare(strict_types=1);

namespace Neos\Neos\Tests\Unit\CodeMigrations;

final readonly class MigrationFixtureIterator
{
    /**
     * @return iterable<string, array{string,string}>
     */
    public static function createForFilesInDirectory(string $pathToDirectory, string $fileExtension): iterable
    {
        $filePaths = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($pathToDirectory),
            ),
            sprintf('/\.%s$/', preg_quote($fileExtension, '/')),
            \RecursiveRegexIterator::GET_MATCH,
        );

        foreach ($filePaths as $filePath => $_) {
            $contents = file_get_contents($filePath);
            $parts = explode("\n-----\n", $contents);
            if (count($parts) !== 2) {
                throw new \RuntimeException(sprintf('Expect exactly two segments split by ----- in file %s', $filePath), 1759646552);
            }
            yield $filePath => [
                rtrim($parts[0]), // original content
                rtrim($parts[1]), // expectation for new
            ];
        }
    }
}
