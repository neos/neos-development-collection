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
use Neos\Flow\Package\PackageManager;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionFile;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;
use Neos\Utility\Files;

/**
 * Helper around the ParsePartials Cache.
 * Connected in the boot to flush caches on file-change.
 * Caches partials when requested by the Fusion Parser.
 *
 */
class ParserCache
{
    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $parsePartialsCache;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\InjectConfiguration(path="enableParsePartialsCache")
     */
    protected $enableCache;

    public function cacheForFusionFile(?string $contextPathAndFilename, \Closure $generateValueToCache): FusionFile
    {
        if ($this->enableCache === false) {
            return $generateValueToCache();
        }
        if ($contextPathAndFilename === null) {
            return $generateValueToCache();
        }
        $identifier = $this->getCacheIdentifierForFile($contextPathAndFilename);
        return $this->cacheForIdentifier($identifier, $generateValueToCache);
    }

    public function cacheForDsl(string $identifier, string $code, \Closure $generateValueToCache): mixed
    {
        if ($this->enableCache === false) {
            return $generateValueToCache();
        }
        $identifier = $this->getCacheIdentifierForDslCode($identifier, $code);
        return $this->cacheForIdentifier($identifier, $generateValueToCache);
    }

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

    private function cacheForIdentifier(string $identifier, \Closure $generateValueToCache): mixed
    {
        if ($this->parsePartialsCache->has($identifier)) {
            return $this->parsePartialsCache->get($identifier);
        }
        $value = $generateValueToCache();
        $this->parsePartialsCache->set($identifier, $value);
        return $value;
    }

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
        if (str_contains($fusionFileName, '://')) {
            $fusionFileName = $this->getAbsolutePathForPackageRessourceUri($fusionFileName);
        }

        $realPath = realpath($fusionFileName);
        if ($realPath === false) {
            throw new \InvalidArgumentException("Couldn't resolve realpath for: '$fusionFileName'");
        }

        $realFusionFilePathWithoutRoot = str_replace(FLOW_PATH_ROOT, '', $realPath);
        return 'file_' . md5($realFusionFilePathWithoutRoot);
    }

    /**
     * Uses the same technic to resolve a package resource uri like flow.
     *
     * resource://My.Site/Private/Fusion/Foo/Bar.fusion
     * ->
     * FLOW_PATH_ROOT/Packages/Sites/My.Package/Resources/Private/Fusion/Foo/Bar.fusion
     *
     * {@see \Neos\Flow\ResourceManagement\Streams\ResourceStreamWrapper::evaluateResourcePath()}
     * {@link https://github.com/neos/flow-development-collection/issues/2687}
     *
     * @throws \InvalidArgumentException
     */
    private function getAbsolutePathForPackageRessourceUri(string $requestedPath): string
    {
        $resourceUriParts = UnicodeFunctions::parse_url($requestedPath);

        if ((isset($resourceUriParts['scheme']) === false
            || $resourceUriParts['scheme'] !== 'resource')) {
            throw new \InvalidArgumentException("Unsupported stream wrapper: '$requestedPath'");
        }

        $package = $this->packageManager->getPackage($resourceUriParts['host']);
        return Files::concatenatePaths([$package->getResourcesPath(), $resourceUriParts['path']]);
    }
}
