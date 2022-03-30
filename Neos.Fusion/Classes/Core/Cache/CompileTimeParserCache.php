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

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\VariableFrontend;

/**
 * Holds the logic to generate the cache identifier.
 * Listener to clear ParsePartialsCache if Fusion files have changed.
 *
 * It's used in the Package bootstrap as an early instance,
 * so only the DI of the `Compile Time Object Manager` is available
 * and at a later point no DI at all -> since no build proxy can be used as this class was already loaded.
 */
#[Flow\Proxy(false)]
class CompileTimeParserCache
{
    public function __construct(
        protected VariableFrontend $parsePartialsCache
    ) {
    }

    /**
     * @param array<string, int> $changedFiles
     */
    public function flushParsePartialsOnFileChanges(string $identifier, array $changedFiles): void
    {
        if ($identifier !== 'Fusion_Files') {
            return;
        }

        foreach ($changedFiles as $changedFile => $status) {
            $identifier = $this->getCacheIdentifierForFile($changedFile);
            if ($this->parsePartialsCache->has($identifier)) {
                $this->parsePartialsCache->remove($identifier);
            }
        }
    }

    /**
     * creates a comparable hash of the dsl type and content to be used as cache identifier
     */
    public static function getCacheIdentifierForDslCode(string $identifier, string $code): string
    {
        return 'dsl_' . $identifier . '_' . md5($code);
    }

    /**
     * creates a comparable hash of the absolute $fusionFileName
     *
     * @throws \InvalidArgumentException
     */
    public static function getCacheIdentifierForFile(string $fusionFileName): string
    {
        $realPath = realpath($fusionFileName);
        if ($realPath === false) {
            throw new \InvalidArgumentException("Couldn't resolve realpath for: '$fusionFileName'");
        }

        $realFusionFilePathWithoutRoot = str_replace(FLOW_PATH_ROOT, '', $realPath);
        return 'file_' . md5($realFusionFilePathWithoutRoot);
    }
}
