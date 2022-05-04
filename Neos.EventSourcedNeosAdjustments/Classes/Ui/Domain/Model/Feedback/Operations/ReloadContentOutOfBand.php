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

use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\EventSourcedNeosAdjustments\View\FusionView;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Fusion\Exception as FusionException;
use Neos\Neos\Ui\Domain\Model\AbstractFeedback;
use Neos\Neos\Ui\Domain\Model\FeedbackInterface;
use Neos\Neos\Ui\Domain\Model\RenderedNodeDomAddress;
use Psr\Http\Message\ResponseInterface;

class ReloadContentOutOfBand extends AbstractFeedback
{
    protected ?NodeInterface $node;

    protected ?RenderedNodeDomAddress $nodeDomAddress;

    /**
     * @Flow\Inject
     * @var ContentCache
     */
    protected $contentCache;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    public function setNode(NodeInterface $node): void
    {
        $this->node = $node;
    }

    public function getNode(): ?NodeInterface
    {
        return $this->node;
    }

    public function setNodeDomAddress(RenderedNodeDomAddress $nodeDomAddress = null): void
    {
        $this->nodeDomAddress = $nodeDomAddress;
    }

    public function getNodeDomAddress(): ?RenderedNodeDomAddress
    {
        return $this->nodeDomAddress;
    }

    public function getType(): string
    {
        return 'Neos.Neos.Ui:ReloadContentOutOfBand';
    }

    public function getDescription(): string
    {
        return sprintf('Rendering of node "%s" required.', $this->node?->getNodeAggregateIdentifier() ?: '');
    }

    /**
     * Checks whether this feedback is similar to another
     */
    public function isSimilarTo(FeedbackInterface $feedback): bool
    {
        if (!$feedback instanceof ReloadContentOutOfBand) {
            return false;
        }

        $feedbackNode = $feedback->getNode();
        return (
            $this->node instanceof NodeInterface &&
            $feedbackNode instanceof NodeInterface &&
            $this->node->getContentStreamIdentifier() === $feedbackNode->getContentStreamIdentifier() &&
            $this->node->getDimensionSpacePoint() === $feedbackNode->getDimensionSpacePoint() &&
            $this->node->getNodeAggregateIdentifier()->equals(
                $feedbackNode->getNodeAggregateIdentifier()
            ) &&
            $this->getNodeDomAddress() == $feedback->getNodeDomAddress()
        );
    }

    /**
     * Serialize the payload for this feedback
     *
     * @return array<string,mixed>
     */
    public function serializePayload(ControllerContext $controllerContext): array
    {
        return !is_null($this->node) && !is_null($this->nodeDomAddress)
            ? [
                'contextPath' => $this->nodeAddressFactory->createFromNode($this->node)->serializeForUri(),
                'nodeDomAddress' => $this->nodeDomAddress,
                'renderedContent' => $this->renderContent($controllerContext)
            ]
            : [];
    }

    /**
     * Render the node
     */
    protected function renderContent(ControllerContext $controllerContext): string|ResponseInterface
    {
        if (!is_null($this->node)) {
            $this->contentCache->flushByTag(sprintf('Node_%s', $this->node->getNodeAggregateIdentifier()));
            if ($this->nodeDomAddress) {
                $fusionView = new FusionView();
                $fusionView->setControllerContext($controllerContext);

                $fusionView->assign('value', $this->node);
                $fusionView->assign('subgraph', $this->nodeAccessorManager->accessorFor(
                    $this->node->getContentStreamIdentifier(),
                    $this->node->getDimensionSpacePoint(),
                    VisibilityConstraints::withoutRestrictions()
                ));
                $fusionView->setFusionPath($this->nodeDomAddress->getFusionPathForContentRendering());

                return $fusionView->render();
            }
        }

        return '';
    }

    /**
     * @return array<string,mixed>
     */
    public function serialize(ControllerContext $controllerContext)
    {
        try {
            return parent::serialize($controllerContext);
        } catch (FusionException $e) {
            // in case there was a rendering error, we just try to reload the document as fallback. Needed
            // e.g. when adding validators to Neos.FormBuilder
            return (new ReloadDocument())->serialize($controllerContext);
        }
    }
}
