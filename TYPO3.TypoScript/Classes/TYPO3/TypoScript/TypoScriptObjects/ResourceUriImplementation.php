<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Service;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\TypoScript\Exception as TypoScriptException;

/**
 * A TypoScript object to create resource URIs
 *
 * The following TS properties are evaluated:
 *  * path
 *  * package
 *  * resource
 *  * localize
 *
 * See respective getters for descriptions
 */
class ResourceUriImplementation extends AbstractTypoScriptObject
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
        return $this->tsValue('path');
    }

    /**
     * Target package key (only required for relative paths)
     *
     * @return string
     */
    public function getPackage()
    {
        return $this->tsValue('package');
    }

    /**
     * If specified, this resource object is used instead of the path and package information
     *
     * @return Resource
     */
    public function getResource()
    {
        return $this->tsValue('resource');
    }

    /**
     * Whether resource localization should be attempted or not, defaults to TRUE
     *
     * @return boolean
     */
    public function isLocalize()
    {
        return (boolean)$this->tsValue('localize');
    }

    /**
     * Returns the absolute URL of a resource
     *
     * @return string
     * @throws TypoScriptException
     */
    public function evaluate()
    {
        $resource = $this->getResource();
        if ($resource !== null) {
            $uri = false;
            if ($resource instanceof Resource) {
                $uri = $this->resourceManager->getPublicPersistentResourceUri($resource);
            }
            if ($uri === false) {
                throw new TypoScriptException('The specified resource is invalid', 1386458728);
            }
            return $uri;
        }
        $path = $this->getPath();
        if ($path === null) {
            throw new TypoScriptException('Neither "resource" nor "path" were specified', 1386458763);
        }
        if (strpos($path, 'resource://') === 0) {
            $matches = array();
            if (preg_match('#^resource://([^/]+)/Public/(.*)#', $path, $matches) !== 1) {
                throw new TypoScriptException(sprintf('The specified path "%s" does not point to a public resource.', $path), 1386458851);
            }
            $package = $matches[1];
            $path = $matches[2];
        } else {
            $package = $this->getPackage();
            if ($package === null) {
                $controllerContext = $this->tsRuntime->getControllerContext();
                /** @var $actionRequest ActionRequest */
                $actionRequest = $controllerContext->getRequest();
                $package = $actionRequest->getControllerPackageKey();
            }
        }
        $localize = $this->isLocalize();
        if ($localize === true) {
            $resourcePath = 'resource://' . $package . '/Public/' . $path;
            $localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);
            $matches = array();
            if (preg_match('#resource://([^/]+)/Public/(.*)#', current($localizedResourcePathData), $matches) === 1) {
                $package = $matches[1];
                $path = $matches[2];
            }
        }

        return $this->resourceManager->getPublicPackageResourceUri($package, $path);
    }
}
