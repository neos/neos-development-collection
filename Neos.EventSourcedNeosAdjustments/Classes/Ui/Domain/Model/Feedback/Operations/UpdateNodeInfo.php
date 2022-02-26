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
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Ui\Domain\Model\AbstractFeedback;
use Neos\Neos\Ui\Domain\Model\FeedbackInterface;
use Neos\Flow\Mvc\Controller\ControllerContext;

class UpdateNodeInfo extends AbstractFeedback
{
    protected ?NodeInterface $node;

    /**
     * @Flow\Inject
     * @var NodeInfoHelper
     */
    protected $nodeInfoHelper;

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

    protected bool $isRecursive = false;

    protected ?string $baseNodeType = null;

    public function setBaseNodeType(?string $baseNodeType): void
    {
        $this->baseNodeType = $baseNodeType;
    }

    public function getBaseNodeType(): ?string
    {
        return $this->baseNodeType;
    }

    public function setNode(NodeInterface $node): void
    {
        $this->node = $node;
    }

    /**
     * Update node infos recursively
     */
    public function recursive(): void
    {
        $this->isRecursive = true;
    }

    public function getNode(): ?NodeInterface
    {
        return $this->node;
    }

    public function getType(): string
    {
        return 'Neos.Neos.Ui:UpdateNodeInfo';
    }

    public function getDescription(): string
    {
        return sprintf('Updated info for node "%s" is available.', $this->node?->getNodeAggregateIdentifier());
    }

    /**
     * Checks whether this feedback is similar to another
     */
    public function isSimilarTo(FeedbackInterface $feedback): bool
    {
        if (!$feedback instanceof UpdateNodeInfo) {
            return false;
        }
        $feedbackNode = $feedback->getNode();

        return $this->node && $feedbackNode && $this->node->getNodeAggregateIdentifier()->equals(
            $feedbackNode->getNodeAggregateIdentifier()
        );
    }

    /**
     * Serialize the payload for this feedback
     *
     * @return array<string,mixed>
     */
    public function serializePayload(ControllerContext $controllerContext): array
    {
        return $this->node
            ? [
                'byContextPath' => $this->serializeNodeRecursively($this->node, $controllerContext)
            ]
            : [];
    }

    /**
     * Serialize node and all child nodes
     *
     * @return array<string,?array<string,mixed>>
     */
    public function serializeNodeRecursively(NodeInterface $node, ControllerContext $controllerContext): array
    {
        $result = [
            $this->nodeAddressFactory->createFromNode($node)->serializeForUri()
                => $this->nodeInfoHelper->renderNodeWithPropertiesAndChildrenInformation(
                    $node,
                    $controllerContext
                )
        ];

        if ($this->isRecursive === true) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $node->getContentStreamIdentifier(),
                $node->getDimensionSpacePoint(),
                VisibilityConstraints::withoutRestrictions()
            );
            foreach ($nodeAccessor->findChildNodes($node) as $childNode) {
                $result = array_merge($result, $this->serializeNodeRecursively($childNode, $controllerContext));
            }
        }

        return $result;
    }
}
