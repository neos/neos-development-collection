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
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
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
     * @var NodeBasedReadModelInterface
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
     * @var ContentGraphInterface
     */
    protected $contentGraph;

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
     * Get the node
     *
     * @return NodeBasedReadModelInterface
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
            (string)$this->getNode()->getContentStreamIdentifier() === (string)$feedback->getNode()->getContentStreamIdentifier() &&
            $this->getNode()->getDimensionSpacePoint()->getHash() === $feedback->getNode()->getDimensionSpacePoint()->getHash() &&
            (string)$this->getNode()->getNodeAggregateIdentifier() === (string)$feedback->getNode()->getNodeAggregateIdentifier() &&

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
            'contextPath' => $this->getNode()->getAddress()->serializeForUri(),
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
        $this->contentCache->flushByTag(sprintf('Node_%s', (string)$this->getNode()->findParentNode()->getNodeAggregateIdentifier()));

        $parentDomAddress = $this->getParentDomAddress();

        $fusionView = new FusionView();
        $fusionView->setControllerContext($controllerContext);

        $fusionView->assign('value', $this->getNode()->findParentNode());
        $fusionView->assign('subgraph', $this->contentGraph->getSubgraphByIdentifier(
            $this->getNode()->getContentStreamIdentifier(),
            $this->getNode()->getDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        ));
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
