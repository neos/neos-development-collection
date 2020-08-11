<?php
namespace Neos\ContentRepository\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Exception;
use Neos\ContentRepository\Exception\NodeConfigurationException;
use Neos\ContentRepository\Exception\NodeTypeIsFinalException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;

/**
 * Manager for node types
 *
 * @Flow\Scope("singleton")
 * @api
 */
class NodeTypeManager
{
    /**
     * Node types, indexed by name
     *
     * @var array
     */
    protected $cachedNodeTypes = [];

    /**
     * Node types, indexed by supertype (also including abstract node types)
     *
     * @var array
     */
    protected $cachedSubNodeTypes = [];

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\InjectConfiguration(path="fallbackNodeType")
     * @var string
     */
    protected $fallbackNodeTypeName;

    /**
     * @Flow\Inject
     * @var StringFrontend
     */
    protected $fullConfigurationCache;

    /**
     * @var array
     */
    protected $fullNodeTypeConfigurations;

    /**
     * Return all registered node types.
     *
     * @param boolean $includeAbstractNodeTypes Whether to include abstract node types, defaults to true
     * @return array<NodeType> All node types registered in the system, indexed by node type name
     * @api
     */
    public function getNodeTypes($includeAbstractNodeTypes = true)
    {
        if ($this->cachedNodeTypes === []) {
            $this->loadNodeTypes();
        }
        if ($includeAbstractNodeTypes) {
            return $this->cachedNodeTypes;
        }

        $nonAbstractNodeTypes = array_filter($this->cachedNodeTypes, function ($nodeType) {
            return !$nodeType->isAbstract();
        });

        return $nonAbstractNodeTypes;
    }

    /**
     * Return all non-abstract node types which have a certain $superType, without
     * the $superType itself.
     *
     * @param string $superTypeName
     * @param boolean $includeAbstractNodeTypes Whether to include abstract node types, defaults to true
     * @return array<NodeType> Sub node types of the given super type, indexed by node type name
     * @api
     */
    public function getSubNodeTypes($superTypeName, $includeAbstractNodeTypes = true)
    {
        if ($this->cachedNodeTypes === []) {
            $this->loadNodeTypes();
        }

        if (!isset($this->cachedSubNodeTypes[$superTypeName])) {
            $filteredNodeTypes = [];
            /** @var NodeType $nodeType */
            foreach ($this->cachedNodeTypes as $nodeTypeName => $nodeType) {
                if ($nodeType->isOfType($superTypeName) && $nodeTypeName !== $superTypeName) {
                    $filteredNodeTypes[$nodeTypeName] = $nodeType;
                }
            }
            $this->cachedSubNodeTypes[$superTypeName] = $filteredNodeTypes;
        }

        if ($includeAbstractNodeTypes === false) {
            return array_filter($this->cachedSubNodeTypes[$superTypeName], function (NodeType $nodeType) {
                return !$nodeType->isAbstract();
            });
        }

        return $this->cachedSubNodeTypes[$superTypeName];
    }

    /**
     * Returns the specified node type (which could be abstract)
     *
     * @param string $nodeTypeName
     * @return NodeType or NULL
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function getNodeType($nodeTypeName)
    {
        if ($this->cachedNodeTypes === []) {
            $this->loadNodeTypes();
        }
        if (isset($this->cachedNodeTypes[$nodeTypeName])) {
            return $this->cachedNodeTypes[$nodeTypeName];
        }

        if ($this->fallbackNodeTypeName === null) {
            throw new NodeTypeNotFoundException(sprintf('The node type "%s" is not available and no fallback NodeType is configured.', $nodeTypeName), 1316598370);
        }

        if (!$this->hasNodeType($this->fallbackNodeTypeName)) {
            throw new NodeTypeNotFoundException(sprintf('The node type "%s" is not available and the configured fallback NodeType "%s" is not available.', $nodeTypeName, $this->fallbackNodeTypeName), 1438166322);
        }
        return $this->getNodeType($this->fallbackNodeTypeName);
    }

    /**
     * Checks if the specified node type exists
     *
     * @param string $nodeTypeName Name of the node type
     * @return boolean true if it exists, otherwise false
     * @api
     */
    public function hasNodeType($nodeTypeName)
    {
        if ($this->cachedNodeTypes === []) {
            $this->loadNodeTypes();
        }
        return isset($this->cachedNodeTypes[$nodeTypeName]);
    }

    /**
     * Creates a new node type
     *
     * @param string $nodeTypeName Unique name of the new node type. Example: "Neos.Neos:Page"
     * @return NodeType
     * @throws Exception
     */
    public function createNodeType($nodeTypeName)
    {
        throw new Exception('Creation of node types not supported so far; tried to create "' . $nodeTypeName . '".', 1316449432);
    }

    /**
     * Loads all node types into memory.
     *
     * @return void
     */
    protected function loadNodeTypes()
    {
        $this->fullNodeTypeConfigurations = $this->fullConfigurationCache->get('fullNodeTypeConfigurations');
        $fillFullConfigurationCache = !is_array($this->fullNodeTypeConfigurations);

        $completeNodeTypeConfiguration = $this->configurationManager->getConfiguration('NodeTypes');

        foreach (array_keys($completeNodeTypeConfiguration) as $nodeTypeName) {
            if (!is_array($completeNodeTypeConfiguration[$nodeTypeName])) {
                continue;
            }
            $nodeType = $this->loadNodeType($nodeTypeName, $completeNodeTypeConfiguration, (isset($this->fullNodeTypeConfigurations[$nodeTypeName]) ? $this->fullNodeTypeConfigurations[$nodeTypeName] : null));
            if ($fillFullConfigurationCache) {
                $this->fullNodeTypeConfigurations[$nodeTypeName] = $nodeType->getFullConfiguration();
            }
        }

        if ($fillFullConfigurationCache) {
            $this->fullConfigurationCache->set('fullNodeTypeConfigurations', $this->fullNodeTypeConfigurations);
        }
        $this->fullNodeTypeConfigurations = null;
    }

    /**
     * This method can be used by Functional of Behavioral Tests to completely
     * override the node types known in the system.
     *
     * In order to reset the node type override, an empty array can be passed in.
     * In this case, the system-node-types are used again.
     *
     * @param array $completeNodeTypeConfiguration
     * @return void
     */
    public function overrideNodeTypes(array $completeNodeTypeConfiguration)
    {
        $this->cachedNodeTypes = [];
        foreach (array_keys($completeNodeTypeConfiguration) as $nodeTypeName) {
            $this->loadNodeType($nodeTypeName, $completeNodeTypeConfiguration);
        }
    }

    /**
     * Load one node type, if it is not loaded yet.
     *
     * @param string $nodeTypeName
     * @param array $completeNodeTypeConfiguration the full node type configuration for all node types
     * @param array $fullNodeTypeConfigurationForType
     * @return NodeType
     * @throws NodeConfigurationException
     * @throws NodeTypeIsFinalException
     * @throws Exception
     */
    protected function loadNodeType($nodeTypeName, array &$completeNodeTypeConfiguration, array $fullNodeTypeConfigurationForType = null)
    {
        if (isset($this->cachedNodeTypes[$nodeTypeName])) {
            return $this->cachedNodeTypes[$nodeTypeName];
        }

        if (!isset($completeNodeTypeConfiguration[$nodeTypeName])) {
            throw new Exception('Node type "' . $nodeTypeName . '" does not exist', 1316451800);
        }

        $nodeTypeConfiguration = $completeNodeTypeConfiguration[$nodeTypeName];
        try {
            $superTypes = isset($nodeTypeConfiguration['superTypes']) ? $this->evaluateSuperTypesConfiguration($nodeTypeConfiguration['superTypes'], $completeNodeTypeConfiguration) : [];
        } catch (NodeConfigurationException $exception) {
            throw new NodeConfigurationException('Node type "' . $nodeTypeName . '" sets super type with a non-string key to NULL.', 1416578395);
        } catch (NodeTypeIsFinalException $exception) {
            throw new NodeTypeIsFinalException('Node type "' . $nodeTypeName . '" has a super type "' . $exception->getMessage() . '" which is final.', 1316452423);
        }

        // Remove unset properties
        $properties = [];
        if (isset($nodeTypeConfiguration['properties']) && is_array($nodeTypeConfiguration['properties'])) {
            $properties = $nodeTypeConfiguration['properties'];
        }

        $nodeTypeConfiguration['properties'] = array_filter($properties, function ($propertyConfiguration) {
            return $propertyConfiguration !== null;
        });

        if ($nodeTypeConfiguration['properties'] === []) {
            unset($nodeTypeConfiguration['properties']);
        }

        $nodeType = new NodeType($nodeTypeName, $superTypes, $nodeTypeConfiguration);

        $this->cachedNodeTypes[$nodeTypeName] = $nodeType;
        return $nodeType;
    }

    /**
     * Evaluates the given superTypes configuation and returns the array of effective superTypes.
     *
     * @param array $superTypesConfiguration
     * @param array $completeNodeTypeConfiguration
     * @return array
     */
    protected function evaluateSuperTypesConfiguration(array $superTypesConfiguration, &$completeNodeTypeConfiguration)
    {
        $superTypes = [];
        foreach ($superTypesConfiguration as $superTypeName => $enabled) {
            $superTypes[$superTypeName] = $this->evaluateSuperTypeConfiguration($superTypeName, $enabled, $completeNodeTypeConfiguration);
        }

        return $superTypes;
    }

    /**
     * Evaluates a single superType configuration and returns the NodeType if enabled.
     *
     * @param string $superTypeName
     * @param boolean $enabled
     * @param array $completeNodeTypeConfiguration
     * @return NodeType
     * @throws NodeConfigurationException
     * @throws NodeTypeIsFinalException
     */
    protected function evaluateSuperTypeConfiguration($superTypeName, $enabled, &$completeNodeTypeConfiguration)
    {
        // Skip unset node types
        if ($enabled === false || $enabled === null) {
            return null;
        }

        // Make this setting backwards compatible with old array schema (deprecated since 2.0)
        if (!is_bool($enabled)) {
            $superTypeName = $enabled;
        }

        // when removing super types by setting them to null, only string keys can be overridden
        if ($superTypeName === null) {
            throw new NodeConfigurationException('Node type sets super type with a non-string key to NULL.', 1444944152);
        }

        $superType = $this->loadNodeType($superTypeName, $completeNodeTypeConfiguration);
        if ($superType->isFinal() === true) {
            throw new NodeTypeIsFinalException($superType->getName(), 1444944148);
        }

        return $superType;
    }
}
