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
use Neos\Flow\Package\FlowPackageInterface;
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
    use ParserCacheIdentifierTrait;

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
     * @var boolean
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
        if (str_contains($contextPathAndFilename, 'resource://')) {
            $contextPathAndFilename = $this->getAbsolutePathForPackageRessourceUri($contextPathAndFilename);
        }
        if (str_contains($contextPathAndFilename, 'nodetypes://')) {
            $contextPathAndFilename = $this->getAbsolutePathForNodeTypesUri($contextPathAndFilename);
        }
        $fusionFileRealPath = realpath($contextPathAndFilename);
        if ($fusionFileRealPath === false) {
            // should not happen as the file would not been able to be read in the first place.
            throw new \RuntimeException("Couldn't resolve realpath for: '$contextPathAndFilename'", 1705409467);
        }
        $identifier = $this->getCacheIdentifierForAbsoluteUnixStyleFilePathWithoutDirectoryTraversal($fusionFileRealPath);
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

    private function cacheForIdentifier(string $identifier, \Closure $generateValueToCache): mixed
    {
        $value = $this->parsePartialsCache->get($identifier);
        if ($value !== false) {
            return $value;
        }
        $value = $generateValueToCache();
        if ($value !== false) {
            // in the rare edge-case of a fusion dsl returning `false` we cannot cache it,
            // as the above get would be ignored. This is an acceptable compromise.
            $this->parsePartialsCache->set($identifier, $value);
        }
        return $value;
    }

    /**
     * Uses the same technique to resolve a package resource URI like Flow.
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

        /** @var FlowPackageInterface $package */
        $package = $this->packageManager->getPackage($resourceUriParts['host']);
        return Files::concatenatePaths([$package->getResourcesPath(), $resourceUriParts['path']]);
    }

    /**
     * Uses the same technique to resolve a package nodetypes URI like Neos.
     *
     * nodetypes://My.Site/Foo/Bar.fusion
     * ->
     * FLOW_PATH_ROOT/Packages/Sites/My.Package/NodeTypes/Foo/Bar.fusion
     *
     * {@see \Neos\Neos\ResourceManagement\NodeTypesStreamWrapper::evaluateNodeTypesPath}
     *
     * @throws \InvalidArgumentException
     */
    private function getAbsolutePathForNodeTypesUri(string $requestedPath): string
    {
        $nodeTypeUriParts = UnicodeFunctions::parse_url($requestedPath);

        if ((isset($nodeTypeUriParts['scheme']) === false
            || $nodeTypeUriParts['scheme'] !== 'nodetypes')) {
            throw new \InvalidArgumentException("Unsupported stream wrapper: '$requestedPath'");
        }

        $package = $this->packageManager->getPackage($nodeTypeUriParts['host']);
        return Files::concatenatePaths([$package->getPackagePath(), 'NodeTypes', $nodeTypeUriParts['path']]);
    }
}
