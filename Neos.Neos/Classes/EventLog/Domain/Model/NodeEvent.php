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

namespace Neos\Neos\EventLog\Domain\Model;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Arrays;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Service\UserService;

/**
 * A specific event which is used for ContentRepository Nodes (i.e. content).
 *
 * @Flow\Entity
 *
 * The following annotation is not picked up by Doctrine migrations and thus included in the Event class as well.
 * See https://github.com/doctrine/doctrine2/issues/6248
 *
 * @ORM\Table(
 *    indexes={
 *      @ORM\Index(name="documentnodeidentifier", columns={"documentnodeidentifier"}),
 *      @ORM\Index(name="workspacename_parentevent", columns={"workspacename", "parentevent"})
 *    }
 * )
 * @todo implement me as a projection
 */
class NodeEvent extends Event
{
    /**
     * the node identifier which was created/modified/...
     *
     * @var string
     */
    protected $nodeIdentifier;

    /**
     * the document node identifier on which the action took place.
     * is equal to NodeIdentifier if the action happened on documentNodes
     *
     * @var string
     */
    protected $documentNodeIdentifier;

    /**
     * the workspace name where the action took place
     *
     * @var string
     */
    protected $workspaceName;

    /**
     * the dimension values for that event
     *
     * @var array<mixed>
     */
    protected $dimension;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * Return name of the workspace where the node event happened
     *
     * @return string
     */
    public function getWorkspaceName()
    {
        return $this->workspaceName;
    }

    /**
     * @return bool
     */
    public function isDocumentEvent()
    {
        return $this->documentNodeIdentifier === $this->nodeIdentifier;
    }

    /**
     * Return the node identifier of the closest parent document node related to this event
     *
     * @return string
     */
    public function getDocumentNodeIdentifier()
    {
        return $this->documentNodeIdentifier;
    }

    /**
     * Return the node identifier of the node this event relates to
     *
     * @return string
     */
    public function getNodeIdentifier()
    {
        return $this->nodeIdentifier;
    }

    /**
     * Set the "context node" this operation was working on.
     *
     * @param NodeInterface $node
     * @return void
     */
    /*public function setNode(NodeInterface $node)
    {
        $this->nodeIdentifier = $node->getIdentifier();
        $this->workspaceName = $node->getContext()->getWorkspaceName();
        $this->dimension = $node->getContext()->getDimensions();

        $context = $node->getContext();
        if ($context instanceof ContentContext && $context->getCurrentSite() !== null) {
            $siteIdentifier = $this->persistenceManager->getIdentifierByObject($context->getCurrentSite());
        } else {
            $siteIdentifier = null;
        }
        $this->data = Arrays::arrayMergeRecursiveOverrule($this->data, [
            'nodeContextPath' => $node->get(),
            'nodeLabel' => $node->getLabel(),
            'nodeType' => $node->getNodeType()->getName(),
            'site' => $siteIdentifier
        ]);

        $node = self::getClosestAggregateNode($node);

        if ($node !== null) {
            $this->documentNodeIdentifier = $node->getIdentifier();
            $this->data = Arrays::arrayMergeRecursiveOverrule($this->data, [
                'documentNodeContextPath' => $node->getContextPath(),
                'documentNodeLabel' => $node->getLabel(),
                'documentNodeType' => $node->getNodeType()->getName()
            ]);
        }
    }*/

    /**
     * Override the workspace name. *MUST* be called after setNode(), else it won't have an effect.
     *
     * @param string $workspaceName
     * @return void
     */
    public function setWorkspaceName($workspaceName)
    {
        $this->workspaceName = $workspaceName;
    }

    /**
     * Returns the closest aggregate node of the given node
     *
     * @param NodeInterface $node
     * @return NodeInterface
     */
    /*public static function getClosestAggregateNode(NodeInterface $node)
    {
        while ($node !== null && !$node->getNodeType()->isAggregate()) {
            $node = $node->getParent();
        }
        return $node;
    }*/

    /**
     * Returns the closest document node, if it can be resolved.
     *
     * It might happen that, if this event refers to a node contained in a site which is not available anymore,
     * Doctrine's proxy class of the Site domain model will fail with an EntityNotFoundException. We catch this
     * case and return NULL.
     *
     * @return NodeInterface
     */
    /*public function getDocumentNode()
    {
        try {
            $context = $this->contextFactory->create([
                'workspaceName' => $this->userService->getPersonalWorkspaceName(),
                'dimensions' => $this->dimension,
                'currentSite' => $this->getCurrentSite(),
                'invisibleContentShown' => true
            ]);
            return $context->getNodeByIdentifier($this->documentNodeIdentifier);
        } catch (EntityNotFoundException $e) {
            return null;
        }
    }*/

    /**
     * Returns the node this even refers to, if it can be resolved.
     *
     * It might happen that, if this event refers to a node contained in a site which is not available anymore,
     * Doctrine's proxy class of the Site domain model will fail with an EntityNotFoundException. We catch this
     * case and return NULL.
     *
     * @return NodeInterface
     */
    /*public function getNode()
    {
        try {
            $context = $this->contextFactory->create([
                'workspaceName' => $this->userService->getPersonalWorkspaceName(),
                'dimensions' => $this->dimension,
                'currentSite' => $this->getCurrentSite(),
                'invisibleContentShown' => true
            ]);
            return $context->getNodeByIdentifier($this->nodeIdentifier);
        } catch (EntityNotFoundException $e) {
            return null;
        }
    }*/

    /**
     * Prevents invalid calls to the site repository in case the site data property is not available.
     *
     * @return null|object
     */
    protected function getCurrentSite()
    {
        if (!isset($this->data['site'])) {
            return null;
        }

        return $this->siteRepository->findByIdentifier($this->data['site']);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('NodeEvent[%s, %s]', $this->eventType, $this->nodeIdentifier);
    }
}
