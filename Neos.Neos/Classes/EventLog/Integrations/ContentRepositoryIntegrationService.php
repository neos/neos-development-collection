<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\EventLog\Integrations;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\EventLog\Domain\Model\NodeEvent;

/**
 * Monitors Neos.ContentRepository changes
 *
 * @Flow\Scope("singleton")
 * @todo rewrite me as a projection
 */
class ContentRepositoryIntegrationService extends AbstractIntegrationService
{
    public const NODE_ADDED = 'Node.Added';
    public const NODE_UPDATED = 'Node.Updated';
    public const NODE_LABEL_CHANGED = 'Node.LabelChanged';
    public const NODE_REMOVED = 'Node.Removed';
    public const DOCUMENT_PUBLISHED = 'Node.Published';
    public const NODE_COPY = 'Node.Copy';
    public const NODE_MOVE = 'Node.Move';
    public const NODE_ADOPT = 'Node.Adopt';

    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    #[Flow\Inject]
    protected NodeAddressFactory $nodeAddressFactory;

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

    /**
     * @var array<mixed>
     */
    protected $changedNodes = [];

    /**
     * @var array<mixed>
     */
    protected $currentNodeAddEvents = [];

    /**
     * @var boolean
     */
    protected $currentlyCopying = false;

    /**
     * @var integer
     */
    protected $currentlyMoving = 0;

    /**
     * @var integer
     */
    protected $currentlyAdopting = 0;

    /**
     * @var array<mixed>
     */
    protected $scheduledNodeEventUpdates = [];

    /**
     * React on the Doctrine preFlush event and trigger the respective internal node events
     *
     * @return void
     */
    /*public function preFlush()
    {
        $this->generateNodeEvents();
    }*/

    /**
     * Emit a "Node Added" event
     *
     * @return void
     */
    /*public function beforeNodeCreate()
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        $nodeEvent = $this->eventEmittingService->generate(self::NODE_ADDED, [], NodeEvent::class);
        $this->currentNodeAddEvents[] = $nodeEvent;
        $this->eventEmittingService->pushContext($nodeEvent);
    }*/

    /**
     * Add the created node to the previously created "Added Node" event
     *
     * @param NodeInterface $node
     * @return void
     */
    /*public function afterNodeCreate(NodeInterface $node)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        $nodeEvent = array_pop($this->currentNodeAddEvents);
        $nodeEvent->setNode($node);
        $this->eventEmittingService->popContext();
        $this->eventEmittingService->add($nodeEvent);
    }*/

    /**
     * Emit a "Node Updated" event
     *
     * @param NodeInterface $node
     * @return void
     */
    /*public function nodeUpdated(NodeInterface $node)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        if (!isset($this->changedNodes[$node->getContextPath()])) {
            $this->changedNodes[$node->getContextPath()] = ['node' => $node];
        }
    }*/

    /**
     * Emit an event when node properties have been changed
     *
     * @param NodeInterface $node
     * @param $propertyName
     * @param $oldValue
     * @param $value
     * @return void
     */
    /*public function beforeNodePropertyChange(NodeInterface $node, $propertyName, $oldValue, $value)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        if (count($this->currentNodeAddEvents) > 0) {
            // add is currently running, during that; we do not want any update events
            return;
        }
        if ($oldValue === $value) {
            return;
        }
        if (!isset($this->changedNodes[$node->getContextPath()])) {
            $this->changedNodes[$node->getContextPath()] = ['node' => $node];
        }
        if (!isset($this->changedNodes[$node->getContextPath()]['oldLabel'])) {
            $this->changedNodes[$node->getContextPath()]['oldLabel'] = $node->getLabel();
        }

        $this->changedNodes[$node->getContextPath()]['old'][$propertyName] = $oldValue;
        $this->changedNodes[$node->getContextPath()]['new'][$propertyName] = $value;
    }*/

    /**
     * Add the new label to a previously created node property changed event
     *
     * @param NodeInterface $node
     * @param $propertyName
     * @param $oldValue
     * @param $value
     * @return void
     */
    /*public function nodePropertyChanged(NodeInterface $node, $propertyName, $oldValue, $value)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        if ($oldValue === $value) {
            return;
        }

        $this->changedNodes[$node->getContextPath()]['newLabel'] = $node->getLabel();
        $this->changedNodes[$node->getContextPath()]['node'] = $node;
    }*/

    /**
     * Emits a "Node Removed" event
     *
     * @param NodeInterface $node
     * @return void
     */
    /*public function nodeRemoved(NodeInterface $node)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        $nodeEvent = $this->eventEmittingService->emit(self::NODE_REMOVED, [], NodeEvent::class);
        $nodeEvent->setNode($node);
    }*/

    /**
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace
     * @return void
     */
    /*public function beforeNodePublishing(NodeInterface $node, Workspace $targetWorkspace)
    {
    }*/

    /**
     * Emits a "Node Copy" event
     *
     * @param NodeInterface $sourceNode
     * @param NodeInterface $targetParentNode
     * @return void
     * @throws \Exception
     */
    /*public function beforeNodeCopy(NodeInterface $sourceNode, NodeInterface $targetParentNode)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        if ($this->currentlyCopying) {
            throw new \Exception('TODO: already copying...');
        }

        $this->currentlyCopying = true;

        $nodeEvent = $this->eventEmittingService->emit(self::NODE_COPY, [
            'copiedInto' => $targetParentNode->getContextPath()
        ], NodeEvent::class);
        $nodeEvent->setNode($sourceNode);
        $this->eventEmittingService->pushContext();
    }*/

    /**
     * @param NodeInterface $copiedNode
     * @param NodeInterface $targetParentNode
     * @return void
     * @throws \Exception
     */
    /*public function afterNodeCopy(NodeInterface $copiedNode, NodeInterface $targetParentNode)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        if ($this->currentlyCopying === false) {
            throw new \Exception('TODO: copying not started');
        }
        $this->currentlyCopying = false;
        $this->eventEmittingService->popContext();
    }*/

    /**
     * Emits a "Node Move" event
     *
     * @param NodeInterface $movedNode
     * @param NodeInterface $referenceNode
     * @param integer $moveOperation
     */
    /*public function beforeNodeMove(NodeInterface $movedNode, NodeInterface $referenceNode, $moveOperation)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        $this->currentlyMoving += 1;

        $nodeEvent = $this->eventEmittingService->emit(self::NODE_MOVE, [
            'referenceNode' => $referenceNode->getContextPath(),
            'moveOperation' => $moveOperation
        ], NodeEvent::class);
        $nodeEvent->setNode($movedNode);
        $this->eventEmittingService->pushContext();
    }*/

    /**
     * @param NodeInterface $movedNode
     * @param NodeInterface $referenceNode
     * @param integer $moveOperation
     * @return void
     * @throws \Exception
     */
    /*public function afterNodeMove(NodeInterface $movedNode, NodeInterface $referenceNode, $moveOperation)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        if ($this->currentlyMoving === 0) {
            throw new \Exception('TODO: moving not started');
        }

        $this->currentlyMoving -= 1;
        $this->eventEmittingService->popContext();
    }*/

    /**
     * Emits a "Node Adopt" event
     *
     * @param NodeInterface $node
     * @param Context $context
     * @param $recursive
     * @return void
     */
    /*public function beforeAdoptNode(NodeInterface $node, Context $context, $recursive)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        if ($this->currentlyAdopting === 0) {
            $nodeEvent = $this->eventEmittingService->emit(self::NODE_ADOPT, [
                'targetWorkspace' => $context->getWorkspaceName(),
                'targetDimensions' => $context->getTargetDimensions(),
                'recursive' => $recursive
            ], NodeEvent::class);
            $nodeEvent->setNode($node);
            $this->eventEmittingService->pushContext();
        }

        $this->currentlyAdopting++;
    }*/

    /**
     * @param NodeInterface $node
     * @param Context $context
     * @param $recursive
     * @return void
     */
    /*public function afterAdoptNode(NodeInterface $node, Context $context, $recursive)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        $this->currentlyAdopting--;
        if ($this->currentlyAdopting === 0) {
            $this->eventEmittingService->popContext();
        }
    }*/

    /**
     * @return void
     */
    /*public function generateNodeEvents()
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        if (count($this->currentNodeAddEvents) > 0) {
            return;
        }

        foreach ($this->changedNodes as $nodePath => $data) {
            $node = $data['node'];
            unset($data['node']);
            if (isset($data['oldLabel']) && isset($data['newLabel'])) {
                if ($data['oldLabel'] !== $data['newLabel']) {
                    $nodeEvent = $this->eventEmittingService->emit(
                        self::NODE_LABEL_CHANGED,
                        ['oldLabel' => $data['oldLabel'], 'newLabel' => $data['newLabel']],
                        NodeEvent::class
                    );
                    $nodeEvent->setNode($node);
                }
                unset($data['oldLabel']);
                unset($data['newLabel']);
            }

            if (!empty($data)) {
                $nodeEvent = $this->eventEmittingService->emit(self::NODE_UPDATED, $data, NodeEvent::class);
                $nodeEvent->setNode($node);
            }
        }

        $this->changedNodes = [];
    }*/

    /**
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace
     * @return void
     */
    public function afterNodePublishing(NodeInterface $node, Workspace $targetWorkspace)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            $node->getVisibilityConstraints()
        );
        $documentNode = $node;
        while ($documentNode !== null && !$documentNode->getNodeType()->isAggregate()) {
            $documentNode = $nodeAccessor->findParentNode($documentNode);
        }

        if ($documentNode === null) {
            return;
        }

        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);
        $documentNodeAddress = $this->nodeAddressFactory->createFromNode($documentNode);

        $this->scheduledNodeEventUpdates[$documentNodeAddress->serializeForUri()] = [
            'workspaceName' => $nodeAddress->workspaceName,
            'nestedNodeIdentifiersWhichArePublished' => [],
            'targetWorkspace' => $targetWorkspace->getWorkspaceName(),
            'documentNode' => $documentNode
        ];

        $this->scheduledNodeEventUpdates[$documentNodeAddress->serializeForUri()]
            ['nestedNodeIdentifiersWhichArePublished'][] = $node->getNodeAggregateIdentifier();
    }

    /**
     * Binds events to a Node.Published event for each document node published
     *
     * @return void
     */
    /*public function updateEventsAfterPublish()
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        $entityManager = $this->entityManager;

        foreach ($this->scheduledNodeEventUpdates as $documentPublish) {
            $nodeEvent = $this->eventEmittingService->emit(self::DOCUMENT_PUBLISHED, [], NodeEvent::class);
            $nodeEvent->setNode($documentPublish['documentNode']);
            $nodeEvent->setWorkspaceName($documentPublish['targetWorkspace']);
            $this->persistenceManager->allowObject($nodeEvent);
            $this->persistenceManager->persistAll(true);

            $parentEventIdentifier = $this->persistenceManager->getIdentifierByObject($nodeEvent);

            $qb = $entityManager->createQueryBuilder();
            $qb->update(NodeEvent::class, 'e')
                ->set('e.parentEvent', ':parentEventIdentifier')
                ->setParameter('parentEventIdentifier', $parentEventIdentifier)
                ->where('e.parentEvent IS NULL')
                ->andWhere('e.workspaceName = :workspaceName')
                ->setParameter('workspaceName', $documentPublish['workspaceName'])
                ->andWhere('e.documentNodeIdentifier = :documentNodeIdentifier')
                ->setParameter('documentNodeIdentifier', $documentPublish['documentNode']->getIdentifier())
                ->andWhere('e.eventType != :publishedEventType')
                ->setParameter('publishedEventType', self::DOCUMENT_PUBLISHED)
                ->getQuery()->execute();
        }

        $this->scheduledNodeEventUpdates = [];

    }*/

    /**
     * @return void
     */
    public function reset()
    {
        $this->changedNodes = [];
        $this->scheduledNodeEventUpdates = [];
        $this->currentlyAdopting = 0;
        $this->currentlyCopying = false;
        $this->currentNodeAddEvents = [];
    }
}
