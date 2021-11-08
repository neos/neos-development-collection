<?php

namespace Neos\Fusion\Core;

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
        if (self::patternIsStreamWrapperThrowOnInvalid($filePattern) === false) {
            $filePattern = self::resolveRelativePath($filePattern, $filePathForRelativeResolves);
        }
        if (self::patternHasGlobbing($filePattern) === false) {
            return [$filePattern];
        }
        return self::parseGlobPatternAndResolveFiles($filePattern, $defaultFileEndForUnspecificGlobbing);
    }

    protected static function patternIsStreamWrapperThrowOnInvalid(string $filePattern): bool
    {
        if (preg_match('`^(?P<protocol>[^:]+)://`', $filePattern, $matches) === 1) {
            $streamWrapper = $matches['protocol'];
            if (in_array($matches['protocol'], stream_get_wrappers(), true)) {
                return true;
            }
            throw new Fusion\Exception("Unable to find the stream wrapper '$streamWrapper' while resolving the pattern: '$filePattern'", 1636144734);
        }
        return false;
    }

    protected static function resolveRelativePath(string $filePattern, ?string $currentFilePath): string
    {
        if ($currentFilePath === null) {
            throw new Fusion\Exception('Relative file resolves are only possible with the argument $currentFilePath passed.', 1636144731);
        }
        return dirname($currentFilePath) . '/' . $filePattern;
    }

    protected static function patternHasGlobbing(string $filePattern): bool
    {
        return preg_match('/\*/', $filePattern) === 1;
    }

    protected static function parseGlobPatternAndResolveFiles(string $filePattern, string $defaultFileEnd): array
    {
        switch (1) {
            // Match recursive wildcard globbing '<base>/**/*<end>?'
            case preg_match(self::RECURSIVE_GLOB_PATTERN, $filePattern, $matches):
                $fileIteratorCreator = function ($dir) {
                    $recursiveDirectoryIterator = new \RecursiveDirectoryIterator($dir);
                    return new \RecursiveIteratorIterator($recursiveDirectoryIterator);
                };
                break;

            // Match simple wildcard globbing '<base>/*<end>?'
            case preg_match(self::SIMPLE_GLOB_PATTERN, $filePattern, $matches):
                $fileIteratorCreator = function ($dir) {
                    return new \DirectoryIterator($dir);
                };
                break;

            default:
                throw new Fusion\Exception(sprintf('The include glob pattern "%s" is invalid. Only globbing with /**/* or /* is supported.', $filePattern), 1636144713);
        }

        $basePath = $matches['base'];
        $fileNameEnd = $matches['end'] === '' ? $defaultFileEnd : $matches['end'];

        if (is_dir($basePath) === false) {
            throw new Fusion\Exception(sprintf('The path %s of the glob pattern "%s" does not point to a directory.', $basePath, $filePattern), 1636144717);
        }

        $iterator = $fileIteratorCreator($basePath);
        return self::iterateOverFilesAndSelectByFileEnding($iterator, $fileNameEnd);
    }

    /**
     * @param \Iterator $fileIterator
     * @param string $fileNameEnd will be transformed to regex pattern to match end of files to be included.
     * @return array
     */
    protected static function iterateOverFilesAndSelectByFileEnding(\Iterator $fileIterator, string $fileNameEnd): array
    {
        $fileNameEndRegex = '/.*' . preg_quote($fileNameEnd, '/') . '$/';
        $files = [];
        /** @var \SplFileInfo $fileInfo */
        foreach ($fileIterator as $fileInfo) {
            if ($fileInfo->isDir() === false) {
                $pathAndFilename = $fileInfo->getPathname();
                if (preg_match($fileNameEndRegex, $pathAndFilename) === 1) {
                    $files[] = $pathAndFilename;
                }
            }
        }
        return $files;
    }
}
