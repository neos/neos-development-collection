<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Neos\Fusion\Cache\ContentCacheFlusher;

/**
 * Step implementations for tests inside Neos.Neos
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait ContentCacheTrait
{
    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;


    /**
     * @Given the ContentCacheFlusher flushes all collected tags
     */
    public function theContentCacheFlusherFlushesAllCollectedTags(): void
    {
        $this->getObject(ContentCacheFlusher::class)->flushCollectedTags();
    }
}
