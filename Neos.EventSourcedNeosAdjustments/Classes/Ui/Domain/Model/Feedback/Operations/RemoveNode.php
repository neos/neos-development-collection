<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations;

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
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Neos\Ui\Domain\Model\AbstractFeedback;
use Neos\Neos\Ui\Domain\Model\FeedbackInterface;

class RemoveNode extends AbstractFeedback
{
    /**
     * @var TraversableNodeInterface
     */
    protected $node;

    /**
     * @var TraversableNodeInterface
     */
    protected $parentNode;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * RemoveNode constructor.
     * @param TraversableNodeInterface $node
     * @param TraversableNodeInterface $parentNode
     */
    public function __construct(TraversableNodeInterface $node, TraversableNodeInterface $parentNode)
    {
        $this->node = $node;
        $this->parentNode = $parentNode;
    }

    /**
     * Get the node
     *
     * @return TraversableNodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Get the type identifier
     *
     * @return string
     */
    public function getType()
    {
        return 'Neos.Neos.Ui:RemoveNode';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return sprintf('Node "%s" has been removed.', $this->getNode()->getLabel());
    }

    /**
     * Checks whether this feedback is similar to another
     *
     * @param FeedbackInterface $feedback
     * @return boolean
     */
    public function isSimilarTo(FeedbackInterface $feedback)
    {
        if (!$feedback instanceof RemoveNode) {
            return false;
        }

        return $this->getNode()->getNodeAggregateIdentifier()->equals($feedback->getNode()->getNodeAggregateIdentifier());
    }

    /**
     * Serialize the payload for this feedback
     *
     * @param ControllerContext $controllerContext
     * @return mixed
     */
    public function serializePayload(ControllerContext $controllerContext)
    {
        return [
            'contextPath' => $this->nodeAddressFactory->createFromTraversableNode($this->node)->serializeForUri(),
            'parentContextPath' => $this->nodeAddressFactory->createFromTraversableNode($this->parentNode)->serializeForUri()
        ];
    }
}
