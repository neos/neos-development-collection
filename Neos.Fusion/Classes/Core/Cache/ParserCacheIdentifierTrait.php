<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\Cache;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Identifier for the ParsePartials Cache.
 */
trait ParserCacheIdentifierTrait
{
    /**
     * Creates a comparable hash of the dsl type and content to be used as cache identifier
     */
    private function getCacheIdentifierForDslCode(string $identifier, string $code): string
    {
        return 'dsl_' . $identifier . '_' . md5($code);
    }

    /**
     * Creates a comparable hash of the absolute-unix-style-file-path-without-directory-traversal
     *
     * something like
     *  - /Users/marc/Code/neos-project/Packages/Neos
     *
     * its crucial that the path
     *  - is absolute (starting with /)
     *  - the path separator is in unix style: forward-slash /
     *  - doesn't contain directory traversal /../ or /./
     *  - is not a symlink
     *
     * to be absolutely sure the path matches the criteria, {@see realpath} can be used.
     *
     * if the path does not match the criteria, a different hash as expected will be generated and caching will break.
     */
    private function getCacheIdentifierForAbsoluteUnixStyleFilePathWithoutDirectoryTraversal(
        string $absoluteUnixStyleFilePathWithoutDirectoryTraversal
    ): string {
        $filePathWithoutRoot = str_replace(FLOW_PATH_ROOT, '', $absoluteUnixStyleFilePathWithoutDirectoryTraversal);
        return 'file_' . md5($filePathWithoutRoot);
    }
}
