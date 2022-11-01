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

use Neos\Flow\Cache\CacheManager;

/**
 * Helper around the ParsePartials Cache.
 * Connected in the boot to flush caches on file-change.
 *
 */
class ParserCacheFlusher
{
    use ParserCacheIdentifierTrait;

    /**
     * @var CacheManager
     */
    protected $flowCacheManager;

    /**
     * @param CacheManager $flowCacheManager
     */
    public function __construct(CacheManager $flowCacheManager)
    {
        $this->flowCacheManager = $flowCacheManager;
    }

    /**
     * @param $fileMonitorIdentifier
     * @param array $changedFiles
     * @return void
     */
    public function flushPartialCacheOnFileChanges($fileMonitorIdentifier, array $changedFiles)
    {
        if ($fileMonitorIdentifier !== 'Fusion_Files') {
            return;
        }

        $identifiersToFlush = [];
        foreach ($changedFiles as $changedFile => $status) {
            $identifiersToFlush[] = $this->getCacheIdentifierForFile($changedFile);
        }

        if ($identifiersToFlush !== []) {
            $partialsCache = $this->flowCacheManager->getCache('Neos_Fusion_ParsePartials');
            foreach ($identifiersToFlush as $identifierToFlush) {
                if ($partialsCache->has($identifierToFlush)) {
                    $partialsCache->remove($identifierToFlush);
                }
            }
        }
    }
}
