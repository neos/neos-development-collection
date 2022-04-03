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

use Neos\Utility\Unicode\Functions as UnicodeFunctions;
use Neos\Utility\Files;

/**
 * Identifier for the ParsePartials Cache.
 */
trait ParserCacheIdentifierTrait
{

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

        $package = $this->packageManager->getPackage($resourceUriParts['host']);
        return Files::concatenatePaths([$package->getResourcesPath(), $resourceUriParts['path']]);
    }
}
