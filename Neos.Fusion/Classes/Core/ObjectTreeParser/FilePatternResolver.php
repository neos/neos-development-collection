<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion;
use Neos\Utility\Files;

/**
 * Resolve files after a pattern.
 * The returned files will not be checked for recursion and for readability.
 */
class FilePatternResolver
{
    protected const RECURSIVE_GLOB_PATTERN = <<<'REGEX'
    `^(?P<base>
        [^*/]*          # simple case: path segment without '*' stop at '/'
        (?:
            /[^*]       # special case '/' no followed '*' is matched
            [^*/]*      # simple case again - unrolled loop following Jeffrey Friedl
        )*
    )
    /\*\*/\*            # the recursive include /**/*
    (?P<end>
        [^*/]*          # optional file end like .fusion or even -special.fusion but no further globbing or folders allowed
    )$`x
    REGEX;

    protected const SIMPLE_GLOB_PATTERN = <<<'REGEX'
    `^(?P<base>
        [^*/]*          # simple case: path segment without '*' stop at '/'
        (?:
            /[^*]       # special case '/' no followed '*' is matched
            [^*/]*      # simple case again - unrolled loop following Jeffrey Friedl
        )*
    )
    /\*                 # the simple glob include /*
    (?P<end>
        [^*/]*          # optional file end like .fusion or even -special.fusion but no further globbing or folders allowed
    )$`x
    REGEX;

    /**
     * @param string $filePattern
     * @param string|null $filePathForRelativeResolves
     * @param string $defaultFileEndForUnspecificGlobbing
     * @return array|string[]
     * @throws Fusion\Exception
     */
    public static function resolveFilesByPattern(string $filePattern, ?string $filePathForRelativeResolves, string $defaultFileEndForUnspecificGlobbing): array
    {
        $filePattern = Files::getUnixStylePath(trim($filePattern));
        if ($filePattern === '') {
            throw new Fusion\Exception("cannot resolve empty pattern: '$filePattern'", 1636144288);
        }
        $isAbsoluteWindowsPath = str_contains($filePattern, ':') && preg_match('`^[a-zA-Z]:/[^/]`', $filePattern) === 1;
        if ($filePattern[0] === '/' || $isAbsoluteWindowsPath) {
            throw new Fusion\Exception("cannot resolve absolute pattern: '$filePattern'", 1636144292);
        }
        if (self::isPatternStreamWrapper($filePattern) === false) {
            $filePattern = self::resolveRelativePath($filePattern, $filePathForRelativeResolves);
        }
        if (str_contains($filePattern, '*') === false) {
            return [$filePattern];
        }
        return self::parseGlobPatternAndResolveFiles($filePattern, $defaultFileEndForUnspecificGlobbing);
    }

    protected static function isPatternStreamWrapper(string $filePattern): bool
    {
        if (preg_match('`^(?P<protocol>[^:]+)://`', $filePattern, $matches) !== 1) {
            return false;
        }
        $streamWrapper = $matches['protocol'];
        if (in_array($streamWrapper, stream_get_wrappers(), true) === false) {
            throw new Fusion\Exception("Unable to find the stream wrapper '$streamWrapper' while resolving the pattern: '$filePattern'", 1636144734);
        }
        return true;
    }

    protected static function resolveRelativePath(string $filePattern, ?string $filePathForRelativeResolves): string
    {
        if ($filePathForRelativeResolves === null) {
            throw new Fusion\Exception('Relative file resolves are only possible with the argument $filePathForRelativeResolves passed.', 1636144731);
        }
        return Files::concatenatePaths([dirname($filePathForRelativeResolves), $filePattern]);
    }

    protected static function parseGlobPatternAndResolveFiles(string $filePattern, string $defaultFileNameEnd): array
    {
        $fileIteratorCreator = match (1) {
            // We use the flag SKIP_DOTS, as it might not be allowed to access `..` and we only are interested in files
            // We use the flag UNIX_PATHS, so that stream wrapper paths are always valid on windows https://github.com/neos/neos-development-collection/issues/4358

            // Match recursive wildcard globbing '<base>/**/*<end>?'
            preg_match(self::RECURSIVE_GLOB_PATTERN, $filePattern, $matches) => static function (string $dir): \Iterator {
                $recursiveDirectoryIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS);
                return new \RecursiveIteratorIterator($recursiveDirectoryIterator);
            },

            // Match simple wildcard globbing '<base>/*<end>?'
            preg_match(self::SIMPLE_GLOB_PATTERN, $filePattern, $matches) => static function (string $dir): \Iterator {
                return new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS);
            },

            default => throw new Fusion\Exception("The include glob pattern '$filePattern' is invalid. Only globbing with /**/* or /* is supported.", 1636144713),
        };

        $basePath = $matches['base'];
        $fileNameEnd = $matches['end'] === '' ? $defaultFileNameEnd : $matches['end'];

        if (is_dir($basePath) === false) {
            throw new Fusion\Exception("The path '$basePath' of the glob pattern '$filePattern' does not point to a directory.", 1636144717);
        }

        $fileIterator = $fileIteratorCreator($basePath);
        return self::iterateOverFilesAndSelectByFileEnding($fileIterator, $fileNameEnd);
    }

    /**
     * @param \Iterator|\SplFileInfo[] $fileIterator
     * @param string $fileNameEnd when file matches this ending it will be included.
     * @return array
     */
    protected static function iterateOverFilesAndSelectByFileEnding(\Iterator $fileIterator, string $fileNameEnd): array
    {
        $files = [];
        foreach ($fileIterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                continue;
            }
            $pathAndFilename = $fileInfo->getPathname();

            if (str_ends_with($pathAndFilename, $fileNameEnd)) {
                $files[] = Files::getUnixStylePath($pathAndFilename);
            }
        }
        return $files;
    }
}
