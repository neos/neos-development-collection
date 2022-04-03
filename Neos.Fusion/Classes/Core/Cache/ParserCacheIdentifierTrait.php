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
     * creates a comparable hash of the dsl type and content to be used as cache identifier
     */
    private function getCacheIdentifierForDslCode(string $identifier, string $code): string
    {
        return 'dsl_' . $identifier . '_' . md5($code);
    }

    /**
     * creates a comparable hash of the absolute, resolved $fusionFileName
     *
     * @throws \InvalidArgumentException
     */
    private function getCacheIdentifierForFile(string $fusionFileName): string
    {
        $realPath = realpath($fusionFileName);
        if ($realPath === false) {
            throw new \InvalidArgumentException("Couldn't resolve realpath for: '$fusionFileName'");
        }

        $realFusionFilePathWithoutRoot = str_replace(FLOW_PATH_ROOT, '', $realPath);
        return 'file_' . md5($realFusionFilePathWithoutRoot);
    }
}
