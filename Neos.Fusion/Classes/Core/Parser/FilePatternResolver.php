<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\Parser;

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
    public static function resolveFilesByPattern(string $filePattern, ?string $filePathForRelativeResolves = null, string $defaultFileEndForUnspecificGlobbing = '.fusion'): array
    {
        $filePattern = trim($filePattern);
        if ($filePattern === '') {
            throw new Fusion\Exception("cannot resolve empty pattern: '$filePattern'", 1636144288);
        }
        if ($filePattern[0] === '/') {
            throw new Fusion\Exception("cannot resolve absolute pattern: '$filePattern'", 1636144292);
        }
        if (self::isPatternStreamWrapper($filePattern) === false) {
            $filePattern = self::resolveRelativePath($filePattern, $filePathForRelativeResolves);
        }
        if (self::patternHasGlobbing($filePattern) === false) {
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
        return dirname($filePathForRelativeResolves) . '/' . $filePattern;
    }

    protected static function patternHasGlobbing(string $filePattern): bool
    {
        return strpos($filePattern, '*') !== false;
    }

    protected static function parseGlobPatternAndResolveFiles(string $filePattern, string $defaultFileNameEnd): array
    {
        switch (1) {
            // Match recursive wildcard globbing '<base>/**/*<end>?'
            case preg_match(self::RECURSIVE_GLOB_PATTERN, $filePattern, $matches):
                $fileIteratorCreator = static function (string $dir): \Iterator {
                    $recursiveDirectoryIterator = new \RecursiveDirectoryIterator($dir);
                    return new \RecursiveIteratorIterator($recursiveDirectoryIterator);
                };
                break;

            // Match simple wildcard globbing '<base>/*<end>?'
            case preg_match(self::SIMPLE_GLOB_PATTERN, $filePattern, $matches):
                $fileIteratorCreator = static function (string $dir): \Iterator {
                    return new \DirectoryIterator($dir);
                };
                break;

            default:
                throw new Fusion\Exception("The include glob pattern '$filePattern' is invalid. Only globbing with /**/* or /* is supported.", 1636144713);
        }

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
            if (self::pathEndsWith($pathAndFilename, $fileNameEnd)) {
                $files[] = $pathAndFilename;
            }
        }
        return $files;
    }

    protected static function pathEndsWith(string $filePath, string $fileNameEnd): bool
    {
        if ($filePath === '' || $fileNameEnd === '') {
            throw new \InvalidArgumentException('$filePath or $fileNameEnd must not be empty.');
        }
        return substr_compare($filePath, $fileNameEnd, -\strlen($fileNameEnd)) === 0;
    }
}
