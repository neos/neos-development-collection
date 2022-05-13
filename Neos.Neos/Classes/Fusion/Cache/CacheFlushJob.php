<?php

namespace Neos\Neos\Fusion\Cache;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\JobQueue\Common\Job\JobInterface;
use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Implementation detail of content cache flushing (async). Created by {@see CacheAwareGraphProjectorFactory}
 */
class CacheFlushJob implements JobInterface
{
    /**
     * @var array<int,array<string,mixed>>
     */
    protected array $cacheFlushes;

    /**
     * @Flow\Inject
     * @var ContentCacheFlusher
     */
    protected $contentCacheFlusher;

    /**
     * @param array<int,array<string,mixed>> $cacheFlushes
     */
    public function __construct(array $cacheFlushes)
    {
        $this->cacheFlushes = $cacheFlushes;
    }

    public function execute(QueueInterface $queue, Message $message): bool
    {
        foreach ($this->cacheFlushes as $entry) {
            // the array entries which we iterate on here are created in CacheAwareGraphProjectorFactory
            $this->contentCacheFlusher->flushNodeAggregate($entry['csi'], $entry['nai']);
        }

        return true;
    }

    public function getLabel(): string
    {
        return sprintf('Flush Cache for node aggregates: %s', json_encode($this->cacheFlushes));
    }
}
