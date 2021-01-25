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

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\EventSourcedNeosAdjustments\Ui\ContentRepository\Service\NodeService;
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Neos\Ui\Domain\Model\AbstractFeedback;
use Neos\Neos\Ui\Domain\Model\FeedbackInterface;

class ReloadDocument extends AbstractFeedback
{
    /**
     * @var TraversableNodeInterface
     */
    protected $node;

    /**
     * @Flow\Inject
     * @var NodeService
     */
    protected $nodeService;

    /**
     * Get the type identifier
     *
     * @return string
     */
    public function getType()
    {
        return 'Neos.Neos.Ui:ReloadDocument';
    }

    /**
     * Set the node
     *
     * @param TraversableNodeInterface $node
     * @return void
     */
    public function setNode(TraversableNodeInterface $node)
    {
        $this->node = $node;
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
     * Get the description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return sprintf('Reload of current document required.');
    }

    /**
     * Checks whether this feedback is similar to another
     *
     * @param FeedbackInterface $feedback
     * @return boolean
     */
    public function isSimilarTo(FeedbackInterface $feedback)
    {
        if (!$feedback instanceof ReloadDocument) {
            return false;
        }

        return true;
    }

    /**
     * Serialize the payload for this feedback
     *
     * @param ControllerContext $controllerContext
     * @return mixed
     */
    public function serializePayload(ControllerContext $controllerContext)
    {
        if (!$this->node) {
            return [];
        }
        $nodeInfoHelper = new NodeInfoHelper();

        if ($documentNode = $this->nodeService->getClosestDocument($this->node)) {
            return [
                'uri' => $nodeInfoHelper->previewUri($documentNode, $controllerContext)
            ];
        }

        return [];
    }
}
