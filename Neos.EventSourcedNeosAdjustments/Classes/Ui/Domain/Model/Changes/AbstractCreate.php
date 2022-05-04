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

use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Feature\Common\Exception\NodeNameIsAlreadyOccupied;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
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
     */
    protected ?NodeType $nodeType;

    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    /**
     * Incoming data from creationDialog
     *
     * @var array<int|string,mixed>
     */
    protected array $data = [];

    /**
     * An (optional) name that will be used for the new node path
     */
    protected ?string $name = null;

    /**
     * @param string $nodeType
     */
    public function setNodeType($nodeType): void
    {
        if (is_string($nodeType)) {
            $nodeType = $this->nodeTypeManager->getNodeType($nodeType);
        }

        $this->nodeType = $nodeType;
    }

    public function getNodeType(): ?NodeType
    {
        return $this->nodeType;
    }

    /**
     * @param array<int|string,mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<int|string,mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param NodeInterface $parentNode
     * @param NodeAggregateIdentifier|null $succeedingSiblingNodeAggregateIdentifier
     * @return NodeInterface
     * @throws InvalidNodeCreationHandlerException|NodeNameIsAlreadyOccupied|NodeException
     */
    protected function createNode(
        NodeInterface $parentNode,
        NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier = null
    ): NodeInterface {
        $nodeType = $this->getNodeType();
        if (is_null($nodeType)) {
            throw new \RuntimeException('Cannot run createNode without a set node type.', 1645577794);
        }
        // TODO: the $name=... line should be as expressed below
        // $name = $this->getName() ?: $this->nodeService->generateUniqueNodeName($parent->findParentNode());
        $nodeName = NodeName::fromString($this->getName() ?: uniqid('node-', false));

        $nodeAggregateIdentifier = NodeAggregateIdentifier::create(); // generate a new NodeAggregateIdentifier
        $nodeTypeName = NodeTypeName::fromString($nodeType->getName());

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

        $this->nodeAggregateCommandHandler->handleCreateNodeAggregateWithNode($command)
            ->blockUntilProjectionsAreUpToDate();
        $this->contentCacheFlusher->flushNodeAggregate(
            $parentNode->getContentStreamIdentifier(),
            $parentNode->getNodeAggregateIdentifier()
        );

        /** @var NodeInterface $newlyCreatedNode */
        $newlyCreatedNode = $this->nodeAccessorFor($parentNode)
            ->findChildNodeConnectedThroughEdgeName($parentNode, $nodeName);

        $this->finish($newlyCreatedNode);
        // NOTE: we need to run "finish" before "addNodeCreatedFeedback"
        // to ensure the new node already exists when the last feedback is processed
        $this->addNodeCreatedFeedback($newlyCreatedNode);
        return $newlyCreatedNode;
    }

    /**
     * @throws InvalidNodeCreationHandlerException
     */
    protected function applyNodeCreationHandlers(
        CreateNodeAggregateWithNode $command,
        NodeTypeName $nodeTypeName
    ): CreateNodeAggregateWithNode {
        $data = $this->getData() ?: [];
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->getValue());
        if (!isset($nodeType->getOptions()['nodeCreationHandlers'])
            || !is_array($nodeType->getOptions()['nodeCreationHandlers'])) {
            return $command;
        }
        foreach ($nodeType->getOptions()['nodeCreationHandlers'] as $nodeCreationHandlerConfiguration) {
            $nodeCreationHandler = new $nodeCreationHandlerConfiguration['nodeCreationHandler']();
            if (!$nodeCreationHandler instanceof NodeCreationHandlerInterface) {
                throw new InvalidNodeCreationHandlerException(sprintf(
                    'Expected %s but got "%s"',
                    NodeCreationHandlerInterface::class,
                    get_class($nodeCreationHandler)
                ), 1364759956);
            }
            $command = $nodeCreationHandler->handle($command, $data);
        }
        return $command;
    }
}
