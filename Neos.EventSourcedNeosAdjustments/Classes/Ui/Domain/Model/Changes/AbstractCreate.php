<?php
declare(strict_types=1);
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
use Neos\ContentRepository\Domain\Service\NodeServiceInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Intermediary\Domain\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Intermediary\Domain\NodeAggregateCommandHandler;
use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Intermediary\Domain\Property\PropertyConverter;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeNameIsAlreadyOccupied;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedNeosAdjustments\Ui\NodeCreationHandler\NodeCreationHandlerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Ui\Exception\InvalidNodeCreationHandlerException;

abstract class AbstractCreate extends AbstractStructuralChange
{
    /**
     * @Flow\Inject
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * @Flow\Inject
     * @var PropertyConverter
     */
    protected $propertyConverter;

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
     * @param NodeBasedReadModelInterface $parentNode
     * @param NodeAggregateIdentifier|null $succeedingSiblingNodeAggregateIdentifier
     * @return NodeBasedReadModelInterface
     * @throws InvalidNodeCreationHandlerException|NodeNameIsAlreadyOccupied|NodeException
     */
    protected function createNode(NodeBasedReadModelInterface $parentNode, NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier = null): NodeBasedReadModelInterface
    {
        // TODO: the $name=... line should be as expressed below
        // $name = $this->getName() ?: $this->nodeService->generateUniqueNodeName($parent->findParentNode());
        $nodeName = NodeName::fromString($this->getName() ?: uniqid('node-', false));

        $nodeAggregateIdentifier = NodeAggregateIdentifier::create(); // generate a new NodeAggregateIdentifier
        $nodeTypeName = NodeTypeName::fromString($this->getNodeType()->getName());

        $command = new CreateNodeAggregateWithNode(
            $parentNode->getContentStreamIdentifier(),
            $nodeAggregateIdentifier,
            $nodeTypeName,
            OriginDimensionSpacePoint::fromDimensionSpacePoint($parentNode->getDimensionSpacePoint()),
            $this->getInitiatingUserIdentifier(),
            $parentNode->getNodeAggregateIdentifier(),
            $succeedingSiblingNodeAggregateIdentifier,
            $nodeName
        );
        $command = $this->applyNodeCreationHandlers($command, $nodeTypeName);

        $this->contentCacheFlusher->registerNodeChange($parentNode);
        $this->nodeAggregateCommandHandler->handleCreateNodeAggregateWithNode($command)->blockUntilProjectionsAreUpToDate();

        $newlyCreatedNode = $parentNode->findNamedChildNode($nodeName);

        $this->finish($newlyCreatedNode);
        // NOTE: we need to run "finish" before "addNodeCreatedFeedback" to ensure the new node already exists when the last feedback is processed
        $this->addNodeCreatedFeedback($newlyCreatedNode);
        return $newlyCreatedNode;
    }

    /**
     * @param CreateNodeAggregateWithNode $command
     * @param NodeTypeName $nodeTypeName
     * @return CreateNodeAggregateWithNode
     * @throws InvalidNodeCreationHandlerException
     */
    protected function applyNodeCreationHandlers(CreateNodeAggregateWithNode $command, NodeTypeName $nodeTypeName): CreateNodeAggregateWithNode
    {
        $data = $this->getData() ?: [];
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->getValue());
        if (!isset($nodeType->getOptions()['nodeCreationHandlers']) || !is_array($nodeType->getOptions()['nodeCreationHandlers'])) {
            return $command;
        }
        foreach ($nodeType->getOptions()['nodeCreationHandlers'] as $nodeCreationHandlerConfiguration) {
            $nodeCreationHandler = new $nodeCreationHandlerConfiguration['nodeCreationHandler']();
            if (!$nodeCreationHandler instanceof NodeCreationHandlerInterface) {
                throw new InvalidNodeCreationHandlerException(sprintf('Expected %s but got "%s"', NodeCreationHandlerInterface::class, get_class($nodeCreationHandler)), 1364759956);
            }
            $command = $nodeCreationHandler->handle($command, $data);
        }
        return $command;
    }
}
