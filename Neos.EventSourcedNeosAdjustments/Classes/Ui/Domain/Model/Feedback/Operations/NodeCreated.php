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

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedNeosAdjustments\Ui\ContentRepository\Service\NodeService;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Neos\Ui\Domain\Model\AbstractFeedback;
use Neos\Neos\Ui\Domain\Model\FeedbackInterface;

class NodeCreated extends AbstractFeedback
{
    /**
     * @var NodeBasedReadModelInterface
     */
    protected $node;

    /**
     * Set the node
     */
    public function setNode(NodeBasedReadModelInterface $node): void
    {
        $this->node = $node;
    }

    /**
     * Get the node
     */
    public function getNode(): NodeBasedReadModelInterface
    {
        return $this->node;
    }

    /**
     * Get the type identifier
     */
    public function getType(): string
    {
        return 'Neos.Neos.Ui:NodeCreated';
    }

    /**
     * Get the description
     */
    public function getDescription(): string
    {
        return sprintf('Document Node "%s" created.', (string)$this->getNode()->getNodeAggregateIdentifier());
    }

    /**
     * Checks whether this feedback is similar to another
     *
     * @param FeedbackInterface $feedback
     * @return boolean
     */
    public function isSimilarTo(FeedbackInterface $feedback)
    {
        if (!$feedback instanceof NodeCreated) {
            return false;
        }

        return (
            (string)$this->getNode()->getContentStreamIdentifier() === (string)$feedback->getNode()->getContentStreamIdentifier() &&
            $this->getNode()->getDimensionSpacePoint()->getHash() === $feedback->getNode()->getDimensionSpacePoint()->getHash() &&
            (string)$this->getNode()->getNodeAggregateIdentifier() === (string)$feedback->getNode()->getNodeAggregateIdentifier()
        );
    }

    /**
     * Serialize the payload for this feedback
     *
     * @param ControllerContext $controllerContext
     * @return mixed
     */
    public function serializePayload(ControllerContext $controllerContext)
    {
        $nodeService = new NodeService();
        $node = $this->getNode();

        return [
            'contextPath' => $node->getAddress()->serializeForUri(),
            'identifier' => (string)$node->getNodeAggregateIdentifier(),
            'isDocument' => $nodeService->isDocument($node)
        ];
    }
}
