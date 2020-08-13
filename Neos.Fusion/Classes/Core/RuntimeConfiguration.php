<?php
declare(strict_types=1);
namespace Neos\Fusion\Core;

use Neos\Fusion\Exception;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * @Flow\Proxy(false)
 */
final class RuntimeConfiguration
{

    /**
     * The parsed Fusion configuration
     *
     * @var array
     */
    private $fusionConfiguration;

    /**
     * @var array
     */
    private $pathCache = [];

    /**
     * @var \Closure
     */
    private $simpleTypeToArrayClosure;

    /**
     * @var \Closure
     */
    private $shouldOverrideFirstClosure;

    public function __construct(array $fusionConfiguration)
    {
        $this->fusionConfiguration = $fusionConfiguration;

        $this->simpleTypeToArrayClosure = function ($simpleType) {
            return $simpleType === null ? null : [
                '__eelExpression' => null,
                '__value' => $simpleType,
                '__objectType' => null
            ];
        };

        $this->shouldOverrideFirstClosure = function ($key, $firstValue, $secondValue): bool {
            return is_array($secondValue) && isset($secondValue['__stopInheritanceChain']);
        };
    }

    /**
     * Get the expanded Fusion configuration for the given path
     *
     * @param string $fusionPath
     * @return array
     * @throws Exception
     */
    public function forPath(string $fusionPath): array
    {
        // Fast path if complete Fusion path is in configuration cache
        if (isset($this->pathCache[$fusionPath])) {
            return $this->pathCache[$fusionPath]['c'];
        }

        // Find longest prefix of path that already is in path cache
        $pathUntilNow = '';
        $fusionPathLength = strlen($fusionPath);
        $offset = $fusionPathLength;
        while (($offset = strrpos($fusionPath, '/', -($fusionPathLength - $offset + 1))) != false) {
            $pathPrefix = substr($fusionPath, 0, $offset);
            if (isset($this->pathCache[$pathPrefix])) {
                $pathUntilNow = $pathPrefix;
                $configuration = $this->pathCache[$pathPrefix]['c'];
                $currentPrototypeDefinitions = $this->pathCache[$pathUntilNow]['p'];
                break;
            }
        }

        // No prefix was found, build configuration for path from the root
        if ($pathUntilNow === '') {
            $configuration = $this->fusionConfiguration;
            $currentPrototypeDefinitions = [];
            if (isset($configuration['__prototypes'])) {
                $currentPrototypeDefinitions = $configuration['__prototypes'];
            }
        }

        // Build configuration for the remaining path parts
        $remainingPath = substr($fusionPath, $pathUntilNow === '' ? 0 : strlen($pathUntilNow) + 1);
        $pathParts = explode('/', $remainingPath);
        foreach ($pathParts as $pathPart) {
            if ($pathUntilNow === '') {
                $pathUntilNow = $pathPart;
            } else {
                $pathUntilNow .= '/' . $pathPart;
            }
            if (isset($this->pathCache[$pathUntilNow])) {
                $configuration = $this->pathCache[$pathUntilNow]['c'];
                $currentPrototypeDefinitions = $this->pathCache[$pathUntilNow]['p'];
                continue;
            }

            $configuration = $this->matchCurrentPathPart($pathPart, $configuration, $currentPrototypeDefinitions);
            $this->pathCache[$pathUntilNow]['c'] = $configuration;
            $this->pathCache[$pathUntilNow]['p'] = $currentPrototypeDefinitions;
        }

        return $configuration;
    }

    /**
     * Matches the current path segment and prepares the configuration.
     *
     * @param string $pathPart
     * @param array $previousConfiguration
     * @param array $currentPrototypeDefinitions
     * @return array
     * @throws Exception
     */
    private function matchCurrentPathPart(string $pathPart, array $previousConfiguration, array &$currentPrototypeDefinitions): array
    {
        if (preg_match('#^([^<]*)(<(.*?)>)?$#', $pathPart, $matches) !== 1) {
            throw new Exception('Path Part ' . $pathPart . ' not well-formed', 1332494645);
        }

        $currentPathSegment = $matches[1];
        $configuration = [];

        if (isset($previousConfiguration[$currentPathSegment])) {
            $configuration = is_array($previousConfiguration[$currentPathSegment]) ? $previousConfiguration[$currentPathSegment] : $this->simpleTypeToArrayClosure->__invoke($previousConfiguration[$currentPathSegment]);
        }

        if (isset($configuration['__prototypes'])) {
            $currentPrototypeDefinitions = Arrays::arrayMergeRecursiveOverruleWithCallback(
                $currentPrototypeDefinitions,
                $configuration['__prototypes'],
                $this->simpleTypeToArrayClosure,
                $this->shouldOverrideFirstClosure
            );
        }

        $currentPathSegmentType = null;
        if (isset($configuration['__objectType'])) {
            $currentPathSegmentType = $configuration['__objectType'];
        }
        if (isset($matches[3])) {
            $currentPathSegmentType = $matches[3];
        }

        if ($currentPathSegmentType !== null) {
            $configuration['__objectType'] = $currentPathSegmentType;
            $configuration = $this->mergePrototypesWithConfigurationForPathSegment(
                $configuration,
                $currentPrototypeDefinitions
            );
        }

        if (is_array($configuration) && !isset($configuration['__value']) && !isset($configuration['__eelExpression']) && !isset($configuration['__meta']['class']) && !isset($configuration['__objectType']) && isset($configuration['__meta']['process'])) {
            $configuration['__value'] = '';
        }

        return $configuration;
    }

    /**
     * Merges the prototype chain into the configuration.
     *
     * @param array $configuration
     * @param array $currentPrototypeDefinitions
     * @return array
     * @throws Exception
     */
    private function mergePrototypesWithConfigurationForPathSegment(array $configuration, array &$currentPrototypeDefinitions): array
    {
        $currentPathSegmentType = $configuration['__objectType'];

        if (isset($currentPrototypeDefinitions[$currentPathSegmentType])) {
            $prototypeMergingOrder = [$currentPathSegmentType];
            if (isset($currentPrototypeDefinitions[$currentPathSegmentType]['__prototypeChain'])) {
                $prototypeMergingOrder = array_merge(
                    $currentPrototypeDefinitions[$currentPathSegmentType]['__prototypeChain'],
                    $prototypeMergingOrder
                );
            }

            $currentPrototypeWithInheritanceTakenIntoAccount = [];

            foreach ($prototypeMergingOrder as $prototypeName) {
                if (!array_key_exists($prototypeName, $currentPrototypeDefinitions)) {
                    throw new Exception(sprintf(
                        'The Fusion object `%s` which you tried to inherit from does not exist. Maybe you have a typo on the right hand side of your inheritance statement for `%s`.',
                        $prototypeName,
                        $currentPathSegmentType
                    ), 1427134340);
                }

                $currentPrototypeWithInheritanceTakenIntoAccount = Arrays::arrayMergeRecursiveOverruleWithCallback(
                    $currentPrototypeWithInheritanceTakenIntoAccount,
                    $currentPrototypeDefinitions[$prototypeName],
                    $this->simpleTypeToArrayClosure,
                    $this->shouldOverrideFirstClosure
                );
            }

            // We merge the already flattened prototype with the current configuration (in that order),
            // to make sure that the current configuration (not being defined in the prototype) wins.
            $configuration = Arrays::arrayMergeRecursiveOverruleWithCallback(
                $currentPrototypeWithInheritanceTakenIntoAccount,
                $configuration,
                $this->simpleTypeToArrayClosure,
                $this->shouldOverrideFirstClosure
            );

            // If context-dependent prototypes are set (such as prototype("foo").prototype("baz")),
            // we update the current prototype definitions.
            if (isset($currentPrototypeWithInheritanceTakenIntoAccount['__prototypes'])) {
                $currentPrototypeDefinitions = Arrays::arrayMergeRecursiveOverruleWithCallback(
                    $currentPrototypeDefinitions,
                    $currentPrototypeWithInheritanceTakenIntoAccount['__prototypes'],
                    $this->simpleTypeToArrayClosure,
                    $this->shouldOverrideFirstClosure
                );
            }
        }

        return $configuration;
    }

    /**
     * No API, internal use for testing
     *
     * @param string $fusionPath
     * @return bool
     * @internal
     */
    public function isPathCached(string $fusionPath): bool
    {
        return isset($this->pathCache[$fusionPath]);
    }
}
