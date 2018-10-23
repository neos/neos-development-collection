<?php
namespace Neos\ContentRepository\TypeConverter;

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
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * An Object Converter for NodeTemplates.
 *
 * @Flow\Scope("singleton")
 */
class NodeTemplateConverter extends NodeConverter
{
    /**
     * A pattern that separates the node content object type from the node type
     */
    const EXTRACT_CONTENT_TYPE_PATTERN = '/^\\\\?(?P<type>Neos\\\ContentRepository\\\Domain\\\Model\\\NodeTemplate)(?:<\\\\?(?P<nodeType>[a-zA-Z0-9\\\\\:\.]+)>)?/';

    /**
     * @var array
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = NodeTemplate::class;

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * Converts the specified node path into a Node.
     *
     * The node path must be an absolute context node path and can be specified as a string or as an array item with the
     * key "__contextNodePath". The latter case is for updating existing nodes.
     *
     * This conversion method supports creation of new nodes because new nodes
     *
     * Also note that the context's "current node" is not affected by this object converter, you will need to set it to
     * whatever node your "current" node is, if any.
     *
     * All elements in the source array which start with two underscores (like __contextNodePath) are specially treated
     * by this converter.
     *
     * All elements in the source array which start with a *single underscore (like _hidden) are *directly* set on the Node
     * object.
     *
     * All other elements, not being prefixed with underscore, are properties of the node.
     *
     *
     * @param string|array $source Either a string or array containing the absolute context node path which identifies the node. For example "/sites/mysitecom/homepage/about@user-admin"
     * @param string $targetType not used
     * @param array $subProperties not used
     * @param PropertyMappingConfigurationInterface $configuration not used
     * @return mixed An object or \Neos\Error\Messages\Error if the input format is not supported or could not be converted for other reasons
     * @throws \Exception
     */
    public function convertFrom($source, $targetType = null, array $subProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $nodeTemplate = new NodeTemplate();
        $nodeType = $this->extractNodeType($targetType, $source);
        $nodeTemplate->setNodeType($nodeType);

        // we don't need a context or workspace for creating NodeTemplate objects, but in order to satisfy the method
        // signature of setNodeProperties(), we do need one:
        $context = $this->contextFactory->create($this->prepareContextProperties('live'));

        $this->setNodeProperties($nodeTemplate, $nodeTemplate->getNodeType(), $source, $context);
        return $nodeTemplate;
    }

    /**
     * Detects the requested node type and returns a corresponding NodeType instance.
     *
     * @param string $targetType
     * @param array $source
     * @return NodeType
     */
    protected function extractNodeType($targetType, array $source)
    {
        if (isset($source['__nodeType'])) {
            $nodeTypeName = $source['__nodeType'];
        } else {
            $matches = [];
            preg_match(self::EXTRACT_CONTENT_TYPE_PATTERN, $targetType, $matches);
            if (isset($matches['nodeType'])) {
                $nodeTypeName = $matches['nodeType'];
            } else {
                $nodeTypeName = 'unstructured';
            }
        }
        return $this->nodeTypeManager->getNodeType($nodeTypeName);
    }
}
