<?php

declare(strict_types=1);
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

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\CopyNodesRecursively;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

class CopyInto extends AbstractStructuralChange
{

    /**
     * @Flow\Inject
     * @var NodeDuplicationCommandHandler
     */
    protected $nodeDuplicationCommandHandler;

    /**
     * @var string
     */
    protected $parentContextPath;

    /**
     * @var NodeBasedReadModelInterface
     */
    protected $cachedParentNode;

    /**
     * @param string $parentContextPath
     */
    public function setParentContextPath($parentContextPath)
    {
        $this->parentContextPath = $parentContextPath;
    }

    /**
     * @return NodeBasedReadModelInterface
     */
    public function getParentNode()
    {
        if ($this->cachedParentNode === null) {
            $this->cachedParentNode = $this->nodeService->getNodeFromContextPath(
                $this->parentContextPath
            );
        }

        return $this->cachedParentNode;
    }

    /**
     * "Subject" is the to-be-copied node; the "parent" node is the new parent
     *
     * @return boolean
     */
    public function canApply(): bool
    {
        $nodeType = $this->getSubject()->getNodeType();

        return NodeInfoHelper::isNodeTypeAllowedAsChildNode($this->getParentNode(), $nodeType);
    }

    public function getMode()
    {
        return 'into';
    }

    /**
     * Applies this change
     *
     * @return void
     */
    public function apply(): void
    {
        if ($this->canApply()) {
            $subject = $this->getSubject();

            $command = CopyNodesRecursively::create(
                $subject,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($subject->getDimensionSpacePoint()),
                UserIdentifier::forSystemUser(), // TODO
                $this->getParentNode()->getNodeAggregateIdentifier(),
                null,
                NodeName::fromString(uniqid('node-'))
            );

            $this->contentCacheFlusher->registerNodeChange($subject);

            $this->runtimeBlocker->blockUntilProjectionsAreUpToDate(
                $this->nodeDuplicationCommandHandler->handleCopyNodesRecursively($command)
            );

            $newlyCreatedNode = $this->getParentNode()->findNamedChildNode($command->getTargetNodeName());
            $this->finish($newlyCreatedNode);
            // NOTE: we need to run "finish" before "addNodeCreatedFeedback" to ensure the new node already exists when the last feedback is processed
            $this->addNodeCreatedFeedback($newlyCreatedNode);
        }
    }
}
