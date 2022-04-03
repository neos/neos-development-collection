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
use Psr\Log\LoggerInterface;

/**
 * Helper around the ParsePartials Cache.
 * Connected in the boot to flush caches on file-change.
 *
 */
class ParserCacheFlusher
{
    use ParserCacheIdentifierTrait;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $parsePartialsCache;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @var string[]
     */
    protected $identifiersToFlush = [];

    /**
     * @param array<string, int> $changedFiles
     */
    public function registerFileChanges(array $changedFiles): void
    {
        foreach ($changedFiles as $changedFile => $status) {
            $this->identifiersToFlush[] = $this->getCacheIdentifierForFile($changedFile);
        }
        // only call cache flushing immediately if the dependencies are already injected
        // otherwise the commit is triggered on initializeObject or shutdownObject
        if ($this->parsePartialsCache) {
            $this->commit();
        }
    }

    public function initializeObject(): void
    {
        $this->commit();
    }

    public function shutdownObject(): void
    {
        $this->commit();
    }

    /**
     * Flush caches according to the previously registered identifiers.
     */
    protected function commit(): void
    {
        $affectedEntries = 0;
        if ($this->identifiersToFlush !== []) {
            foreach ($this->identifiersToFlush as $identifierToFlush) {
                if ($this->parsePartialsCache->has($identifierToFlush)) {
                    $this->parsePartialsCache->remove($identifierToFlush);
                    $affectedEntries ++;
                }
            }
            $this->systemLogger->debug(sprintf('Fusion parser partials cache: Removed %s entries', $affectedEntries));
            $this->identifiersToFlush = [];
        }
    }
}
