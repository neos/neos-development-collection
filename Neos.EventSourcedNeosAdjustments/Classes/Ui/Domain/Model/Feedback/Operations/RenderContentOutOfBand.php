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

class RenderContentOutOfBand extends AbstractFeedback
{
    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * The node dom address for the parent node of the created node
     *
     * @var RenderedNodeDomAddress
     */
    protected $parentDomAddress;

    /**
     * The node dom address for the referenced sibling node of the created node
     *
     * @var RenderedNodeDomAddress
     */
    protected $siblingDomAddress;

    /**
     * @var string
     */
    protected $mode;

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

    /**
     * Set the node
     *
     * @param NodeInterface $node
     * @return void
     */
    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }

    /**
     * Get the node
     *
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Set the parent node dom address
     *
     * @param RenderedNodeDomAddress $parentDomAddress
     * @return void
     */
    public function setParentDomAddress(RenderedNodeDomAddress $parentDomAddress = null)
    {
        $this->parentDomAddress = $parentDomAddress;
    }

    /**
     * Get the parent node dom address
     *
     * @return RenderedNodeDomAddress
     */
    public function getParentDomAddress()
    {
        return $this->parentDomAddress;
    }

    /**
     * Set the sibling node dom address
     *
     * @param RenderedNodeDomAddress $siblingDomAddress
     * @return void
     */
    public function setSiblingDomAddress(RenderedNodeDomAddress $siblingDomAddress = null)
    {
        $this->siblingDomAddress = $siblingDomAddress;
    }

    /**
     * Get the sibling node dom address
     *
     * @return RenderedNodeDomAddress
     */
    public function getSiblingDomAddress()
    {
        return $this->siblingDomAddress;
    }

    /**
     * Set the insertion mode (before|after|into)
     *
     * @param string $mode
     * @return void
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Get the insertion mode (before|after|into)
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Get the type identifier
     *
     * @return string
     */
    public function getType()
    {
        return 'Neos.Neos.Ui:RenderContentOutOfBand';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return sprintf('Rendering of node "%s" required.', $this->getNode()->getNodeAggregateIdentifier());
    }

    /**
     * Checks whether this feedback is similar to another
     *
     * @param FeedbackInterface $feedback
     * @return boolean
     */
    public function isSimilarTo(FeedbackInterface $feedback)
    {
        if (!$feedback instanceof RenderContentOutOfBand) {
            return false;
        }

        return (
            $this->getNode()->getContentStreamIdentifier()->equals($feedback->getNode()->getContentStreamIdentifier()) &&
            $this->getNode()->getDimensionSpacePoint()->equals($feedback->getNode()->getDimensionSpacePoint()) &&
            $this->getNode()->getNodeAggregateIdentifier()->equals($feedback->getNode()->getNodeAggregateIdentifier()) &&

            $this->getReferenceData() == $feedback->getReferenceData()
        );
    }

    /**
     * Serialize the payload for this feedback
     *
     * @return mixed
     */
    public function serializePayload(ControllerContext $controllerContext)
    {
        return [
            'contextPath' => $this->nodeAddressFactory->createFromNode($this->getNode())->serializeForUri(),
            'parentDomAddress' => $this->getParentDomAddress(),
            'siblingDomAddress' => $this->getSiblingDomAddress(),
            'mode' => $this->getMode(),
            'renderedContent' => $this->renderContent($controllerContext)
        ];
    }

    /**
     * Render the node
     *
     * @param ControllerContext $controllerContext
     * @return string
     */
    protected function renderContent(ControllerContext $controllerContext)
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor($this->getNode()->getContentStreamIdentifier(), $this->getNode()->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
        $this->contentCache->flushByTag(sprintf('Node_%s', (string)$nodeAccessor->findParentNode($this->getNode())->getNodeAggregateIdentifier()));

        $parentDomAddress = $this->getParentDomAddress();

        $fusionView = new FusionView();
        $fusionView->setControllerContext($controllerContext);

        $fusionView->assign('value', $nodeAccessor->findParentNode($this->getNode()));
        $fusionView->assign('subgraph', $nodeAccessor);
        $fusionView->setFusionPath($parentDomAddress->getFusionPath());

        return $fusionView->render();
    }

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
