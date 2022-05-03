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

use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedNeosAdjustments\View\FusionView;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Fusion\Exception as FusionException;
use Neos\Neos\Ui\Domain\Model\AbstractFeedback;
use Neos\Neos\Ui\Domain\Model\FeedbackInterface;
use Neos\Neos\Ui\Domain\Model\RenderedNodeDomAddress;
use Psr\Http\Message\ResponseInterface;

class RenderContentOutOfBand extends AbstractFeedback
{
    protected ?NodeInterface $node = null;

    /**
     * The node dom address for the parent node of the created node
     */
    protected ?RenderedNodeDomAddress $parentDomAddress = null;

    /**
     * The node dom address for the referenced sibling node of the created node
     */
    protected ?RenderedNodeDomAddress $siblingDomAddress = null;

    protected ?string $mode = null;

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

    public function setParentDomAddress(RenderedNodeDomAddress $parentDomAddress = null): void
    {
        $this->parentDomAddress = $parentDomAddress;
    }

    public function getParentDomAddress(): ?RenderedNodeDomAddress
    {
        return $this->parentDomAddress;
    }

    public function setSiblingDomAddress(RenderedNodeDomAddress $siblingDomAddress = null): void
    {
        $this->siblingDomAddress = $siblingDomAddress;
    }

    public function getSiblingDomAddress(): ?RenderedNodeDomAddress
    {
        return $this->siblingDomAddress;
    }

    /**
     * Set the insertion mode (before|after|into)
     */
    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Get the insertion mode (before|after|into)
     */
    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function getType(): string
    {
        return 'Neos.Neos.Ui:RenderContentOutOfBand';
    }

    public function getDescription(): string
    {
        return sprintf('Rendering of node "%s" required.', $this->node?->getNodeAggregateIdentifier());
    }

    /**
     * Checks whether this feedback is similar to another
     */
    public function isSimilarTo(FeedbackInterface $feedback): bool
    {
        if (!$feedback instanceof RenderContentOutOfBand) {
            return false;
        }
        if (is_null($this->node)) {
            return false;
        }
        $feedbackNode = $feedback->getNode();
        if (is_null($feedbackNode)) {
            return false;
        }

        return (
            $this->node->getContentStreamIdentifier() === $feedbackNode->getContentStreamIdentifier() &&
            $this->node->getDimensionSpacePoint() === $feedbackNode->getDimensionSpacePoint() &&
            $this->node->getNodeAggregateIdentifier()->equals($feedbackNode->getNodeAggregateIdentifier())
            // @todo what's this? && $this->getReferenceData() == $feedback->getReferenceData()
        );
    }

    /**
     * Serialize the payload for this feedback
     *
     * @return array<string,mixed>
     */
    public function serializePayload(ControllerContext $controllerContext): array
    {
        return !is_null($this->node)
            ? [
                'contextPath' => $this->nodeAddressFactory->createFromNode($this->node)->serializeForUri(),
                'parentDomAddress' => $this->getParentDomAddress(),
                'siblingDomAddress' => $this->getSiblingDomAddress(),
                'mode' => $this->getMode(),
                'renderedContent' => $this->renderContent($controllerContext)
            ]
            : [];
    }

    /**
     * Render the node
     */
    protected function renderContent(ControllerContext $controllerContext): string|ResponseInterface
    {
        if (is_null($this->node)) {
            return '';
        }
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $this->node->getContentStreamIdentifier(),
            $this->node->getDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $parentNode = $nodeAccessor->findParentNode($this->node);
        if ($parentNode) {
            $this->contentCache->flushByTag(sprintf(
                'Node_%s',
                $parentNode->getNodeAggregateIdentifier()
            ));

            $parentDomAddress = $this->getParentDomAddress();
            if ($parentDomAddress) {
                $fusionView = new FusionView();
                $fusionView->setControllerContext($controllerContext);

                $fusionView->assign('value', $parentNode);
                $fusionView->assign('subgraph', $nodeAccessor);
                $fusionView->setFusionPath($parentDomAddress->getFusionPath());

                return $fusionView->render();
            }
        }

        return '';
    }

    /**
     * @return array<string,mixed>
     */
    public function serialize(ControllerContext $controllerContext): array
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
