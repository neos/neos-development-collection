<?php
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
use Neos\Flow\Cache\CacheManager;

/**
 * Listener to clear Fusion caches if important files have changed
 *
 * It's used in the Package bootstrap as an early instance, so no full dependency injection is available.
 *
 * @Flow\Proxy(false)
 */
class FileMonitorListener
{
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
    public function flushContentCacheOnFileChanges($fileMonitorIdentifier, array $changedFiles)
    {
        $fileMonitorsThatTriggerContentCacheFlush = [
            'ContentRepository_NodeTypesConfiguration',
            'Fusion_Files',
            'Fluid_TemplateFiles',
            'Flow_ClassFiles',
            'Flow_ConfigurationFiles',
            'Flow_TranslationFiles'
        ];

        if (in_array($fileMonitorIdentifier, $fileMonitorsThatTriggerContentCacheFlush)) {
            $this->flowCacheManager->getCache('Neos_Fusion_Content')->flush();
        }
    }
}
