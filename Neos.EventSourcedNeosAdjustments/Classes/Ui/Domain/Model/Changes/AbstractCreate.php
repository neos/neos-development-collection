<?php
namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Changes;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Service\NodeServiceInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedNeosAdjustments\Ui\NodeCreationHandler\NodeCreationHandlerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Ui\Exception\InvalidNodeCreationHandlerException;

abstract class AbstractCreate extends AbstractStructuralChange
{
    /**
     * The type of the node that will be created
     *
     * @var NodeType
     */
    protected $nodeType;

    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    /**
     * Incoming data from creationDialog
     *
     * @var array
     */
    protected $data = [];

    /**
     * An (optional) name that will be used for the new node path
     *
     * @var string|null
     */
    protected $name = null;

    /**
     * @Flow\Inject
     * @var NodeServiceInterface
     */
    protected $nodeService;

    /**
     * Set the node type
     *
     * @param string $nodeType
     */
    public function setNodeType($nodeType)
    {
        if (is_string($nodeType)) {
            $nodeType = $this->nodeTypeManager->getNodeType($nodeType);
        }

        if (!$nodeType instanceof NodeType) {
            throw new \InvalidArgumentException('nodeType needs to be of type string or NodeType', 1452100970);
        }

        $this->nodeType = $nodeType;
    }

    /**
     * Get the node type
     *
     * @return NodeType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * Set the data
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Apply nodeCreationHandlers
     *
     * @param TraversableNodeInterface $node
     * @throws InvalidNodeCreationHandlerException
     * @return void
     */
    protected function applyNodeCreationHandlers(TraversableNodeInterface $node)
    {
        $data = $this->getData() ?: [];
        $nodeType = $node->getNodeType();
        if (isset($nodeType->getOptions()['nodeCreationHandlers'])) {
            $nodeCreationHandlers = $nodeType->getOptions()['nodeCreationHandlers'];
            if (is_array($nodeCreationHandlers)) {
                foreach ($nodeCreationHandlers as $nodeCreationHandlerConfiguration) {
                    $nodeCreationHandler = new $nodeCreationHandlerConfiguration['nodeCreationHandler']();
                    if (!$nodeCreationHandler instanceof NodeCreationHandlerInterface) {
                        throw new InvalidNodeCreationHandlerException(sprintf('Expected NodeCreationHandlerInterface but got "%s"', get_class($nodeCreationHandler)), 1364759956);
                    }
                    $nodeCreationHandler->handle($node, $data);
                }
            }
        }

        $this->emitNodeCreationHandlersApplied($node);
    }

    /**
     * Signals, that all changes by node creation handlers are applied
     *
     * @Flow\Signal
     *
     * @param TraversableNodeInterface $node The node, the node creation handlers are applied to
     * @return void
     */
    public function emitNodeCreationHandlersApplied(TraversableNodeInterface $node)
    {
    }
}
