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

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\CreateNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeCommandHandler;
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;

class Create extends AbstractCreate
{

    /**
     * @Flow\Inject
     * @var NodeCommandHandler
     */
    protected $nodeCommandHandler;

    /**
     * @param string $parentContextPath
     */
    public function setParentContextPath($parentContextPath)
    {
        // this method needs to exist; otherwise the TypeConverter breaks.
    }

    /**
     * Get the insertion mode (before|after|into) that is represented by this change
     *
     * @return string
     */
    public function getMode()
    {
        return 'into';
    }

    /**
     * Check if the new node's node type is allowed in the requested position
     *
     * @return boolean
     */
    public function canApply()
    {
        $subject = $this->getSubject();
        $nodeType = $this->getNodeType();

        return NodeInfoHelper::isNodeTypeAllowedAsChildNode($subject, $nodeType);
    }

    /**
     * Create a new node beneath the subject
     *
     * @return void
     */
    public function apply()
    {
        if ($this->canApply()) {
            $parentNode = $this->getSubject();

            // TODO: the $name=... line should be as expressed below
            // $name = $this->getName() ?: $this->nodeService->generateUniqueNodeName($parent->findParentNode());
            $nodeName = new NodeName($this->getName() ?: uniqid('node-'));

            $nodeAggregateIdentifier = new NodeAggregateIdentifier(); // generate a new NodeAggregateIdentifier

            $command = new CreateNodeAggregateWithNode(
                $parentNode->getContentStreamIdentifier(),
                $nodeAggregateIdentifier,
                new NodeTypeName($this->getNodeType()->getName()),
                $parentNode->getDimensionSpacePoint(),
                new NodeIdentifier(), // generate a new NodeIdentifier
                $parentNode->getNodeIdentifier(),
                $nodeName
            );

            $this->nodeCommandHandler->handleCreateNodeAggregateWithNode($command);

            $newlyCreatedNode = $parentNode->findNamedChildNode($nodeName);
            $this->applyNodeCreationHandlers($newlyCreatedNode);

            $this->finish($newlyCreatedNode);
            // NOTE: we need to run "finish" before "addNodeCreatedFeedback" to ensure the new node already exists when the last feedback is processed
            $this->addNodeCreatedFeedback($newlyCreatedNode);


            $this->updateWorkspaceInfo();
        }
    }
}
