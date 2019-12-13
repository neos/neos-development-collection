<?php
namespace Neos\Neos\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\ContentRepository\Domain\Model\NodeType;

/**
 * A plugin view definition
 *
 * @api
 */
class PluginViewDefinition
{
    /**
     * @var NodeType
     */
    protected $pluginNodeType;

    /**
     * Name of this plugin view. Example: "SomePluginView"
     *
     * @var string
     */
    protected $name;

    /**
     * Configuration for this node type, can be an arbitrarily nested array.
     *
     * @var array
     */
    protected $configuration;

    /**
     * @param NodeType $pluginNodeType
     * @param string $name Name of the view
     * @param array $configuration the configuration for this node type which is defined in the schema
     */
    public function __construct(NodeType $pluginNodeType, $name, array $configuration)
    {
        $this->pluginNodeType = $pluginNodeType;
        $this->name = $name;
        $this->configuration = $configuration;
    }

    /**
     * @return NodeType
     */
    public function getPluginNodeType()
    {
        return $this->pluginNodeType;
    }

    /**
     * Returns the name of the plugin view
     *
     * @return string
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the full configuration of the node type. Should only be used internally.
     *
     * Instead, use the get* / has* methods which exist for every configuration property.
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Get the human-readable label of this node type
     *
     * @return string
     * @api
     */
    public function getLabel()
    {
        $translationHelper = new TranslationHelper();
        return isset($this->configuration['label']) ? $translationHelper->translate($this->configuration['label']) : '';
    }

    /**
     * @return array
     */
    public function getControllerActionPairs()
    {
        return isset($this->configuration['controllerActions']) ? $this->configuration['controllerActions'] : [];
    }

    /**
     * Whether or not the current PluginView is configured to handle the specified controller/action pair
     *
     * @param $controllerObjectName
     * @param $actionName
     * @return boolean
     */
    public function matchesControllerActionPair($controllerObjectName, $actionName)
    {
        $controllerActionPairs = $this->getControllerActionPairs();
        return isset($controllerActionPairs[$controllerObjectName]) && in_array($actionName, $controllerActionPairs[$controllerObjectName]);
    }

    /**
     * Renders the unique name of this PluginView in the format <Package.Key>:<PluginNodeType>/<PluginViewName>
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getPluginNodeType()->getName() . '/' . $this->getName();
    }
}
