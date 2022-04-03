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
 * Helper around the ParsePartials Cache.
 * Connected in the boot to flush caches on file-change.
 * Caches partials when requested by the Fusion Parser.
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
     * @param array<string, int> $changedFiles
     */
    public function flushFileAstCacheOnFileChanges(array $changedFiles): void
    {
        foreach ($changedFiles as $changedFile => $status) {
            $identifier = $this->getCacheIdentifierForFile($changedFile);
            if ($this->parsePartialsCache->has($identifier)) {
                $this->parsePartialsCache->remove($identifier);
            }
        }
    }
}
