<?php
namespace Neos\Fusion\FusionObjects;

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
use Neos\Flow\I18n\Service;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Fusion\Exception as FusionException;

/**
 * A Fusion object to create resource URIs
 *
 * The following TS properties are evaluated:
 *  * path
 *  * package
 *  * resource
 *  * localize
 *
 * See respective getters for descriptions
 */
class ResourceUriImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var Service
     */
    protected $i18nService;

    /**
     * The location of the resource, can be either a path relative to the Public resource directory of the package or a resource://... URI
     *
     * @return string
     */
    public function getPath()
    {
        return $this->fusionValue('path');
    }

    /**
     * Target package key (only required for relative paths)
     *
     * @return string
     */
    public function getPackage()
    {
        return $this->fusionValue('package');
    }

    /**
     * If specified, this resource object is used instead of the path and package information
     *
     * @return PersistentResource
     */
    public function getResource()
    {
        return $this->fusionValue('resource');
    }

    /**
     * Whether resource localization should be attempted or not, defaults to true
     *
     * @return boolean
     */
    public function isLocalize()
    {
        return (boolean)$this->fusionValue('localize');
    }

    /**
     * Returns the absolute URL of a resource
     *
     * @return string
     * @throws FusionException
     */
    public function evaluate()
    {
        $resource = $this->getResource();
        if ($resource !== null) {
            $uri = false;
            if ($resource instanceof PersistentResource) {
                $uri = $this->resourceManager->getPublicPersistentResourceUri($resource);
            }
            if ($uri === false) {
                throw new FusionException('The specified resource is invalid', 1386458728);
            }
            return $uri;
        }
        $path = $this->getPath();
        if ($path === null) {
            throw new FusionException('Neither "resource" nor "path" were specified', 1386458763);
        }
        if (strpos($path, 'resource://') === 0) {
            $matches = [];
            if (preg_match('#^resource://([^/]+)/Public/(.*)#', $path, $matches) !== 1) {
                throw new FusionException(sprintf('The specified path "%s" does not point to a public resource.', $path), 1386458851);
            }
            $package = $matches[1];
            $path = $matches[2];
        } else {
            $package = $this->getPackage();
            if ($package === null) {
                $controllerContext = $this->runtime->getControllerContext();
                /** @var $actionRequest ActionRequest */
                $actionRequest = $controllerContext->getRequest();
                $package = $actionRequest->getControllerPackageKey();
            }
        }
        $localize = $this->isLocalize();
        if ($localize === true) {
            $resourcePath = 'resource://' . $package . '/Public/' . $path;
            $localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);
            $matches = [];
            if (preg_match('#resource://([^/]+)/Public/(.*)#', current($localizedResourcePathData), $matches) === 1) {
                $package = $matches[1];
                $path = $matches[2];
            }
        }

        return $this->resourceManager->getPublicPackageResourceUri($package, $path);
    }
}
