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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Ui\Domain\Model\AbstractFeedback;
use Neos\Neos\Ui\Domain\Model\FeedbackInterface;
use Neos\Flow\Mvc\Controller\ControllerContext;

class UpdateNodeInfo extends AbstractFeedback
{
    /**
     * @var NodeBasedReadModelInterface
     */
    protected $node;

    /**
     * @Flow\Inject
     * @var NodeInfoHelper
     */
    protected $nodeInfoHelper;

    protected $isRecursive = false;

    /**
     * Set the node
     *
     * @param NodeBasedReadModelInterface $node
     * @return void
     */
    public function setNode(NodeBasedReadModelInterface $node)
    {
        $this->node = $node;
    }

    /**
     * Update node infos recursively
     *
     * @return void
     */
    public function recursive()
    {
        $this->isRecursive = true;
    }

    /**
     * Get the node
     *
     * @return NodeBasedReadModelInterface
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
        return 'Neos.Neos.Ui:UpdateNodeInfo';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return sprintf('Updated info for node "%s" is available.', $this->getNode()->getNodeAggregateIdentifier());
    }

    /**
     * Checks whether this feedback is similar to another
     *
     * @param FeedbackInterface $feedback
     * @return boolean
     */
    public function isSimilarTo(FeedbackInterface $feedback)
    {
        if (!$feedback instanceof UpdateNodeInfo) {
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
            'byContextPath' => $this->serializeNodeRecursively($this->getNode(), $controllerContext)
        ];
    }

    /**
     * Serialize node and all child nodes
     *
     * @param NodeBasedReadModelInterface $node
     * @param ControllerContext $controllerContext
     * @return array
     */
    public function serializeNodeRecursively(NodeBasedReadModelInterface $node, ControllerContext $controllerContext)
    {
        $result = [
            $node->getAddress()->serializeForUri() => $this->nodeInfoHelper->renderNodeWithPropertiesAndChildrenInformation($node, $controllerContext)
        ];

        if ($this->isRecursive === true) {
            foreach ($node->findChildNodes() as $childNode) {
                $result = array_merge($result, $this->serializeNodeRecursively($childNode, $controllerContext));
            }
        }

        return $result;
    }
}
