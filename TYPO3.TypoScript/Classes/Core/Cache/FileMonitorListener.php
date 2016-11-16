<?php
namespace TYPO3\TypoScript\Core\Cache;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheManager;

/**
 * Listener to clear TypoScript caches if important files have changed
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
        $fileMonitorsThatTriggerContentCacheFlush = array(
            'TYPO3CR_NodeTypesConfiguration',
            'TypoScript_Files',
            'Fluid_TemplateFiles',
            'Flow_ClassFiles',
            'Flow_ConfigurationFiles',
            'Flow_TranslationFiles'
        );

        if (in_array($fileMonitorIdentifier, $fileMonitorsThatTriggerContentCacheFlush)) {
            $this->flowCacheManager->getCache('TYPO3_TypoScript_Content')->flush();
        }
    }
}
