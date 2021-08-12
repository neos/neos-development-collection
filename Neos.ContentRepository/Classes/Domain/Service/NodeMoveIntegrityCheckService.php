<?php
namespace Neos\ContentRepository\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Dto\NodeMoveIntegrityCheckResult;
use Neos\ContentRepository\Domain\Service\Dto\NodeMoveIntegrityCheckResultPart;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Flow\Annotations as Flow;

/**
 * This is an internal service to prevent disconnected nodes when moving.
 *
 * It is called from {@see Node::setPathInternalForAggregate()}, and throws an error in case disconnected nodes might appear
 * due to a move.
 *
 * ## How can disconnected nodes occur?
 *
 * Background: When moving a node, it is moved across ALL dimensions.
 *
 * (This issue is described at https://github.com/neos/neos-development-collection/issues/3384 )
 *
 * Let's say we have the following Node structure in the beginning:
 *
 * |-- site (de, en)
 * . |-- cr  (de, en)
 * . | |-- subpage (de, en)
 * . |-- other  (de)
 *
 * Now, the user moves /site/cr/subpage underneath /site/other/ in the user workspace.
 *
 * Until July 2021, this lead to the following situation / bug:
 *
 * |-- site (de, en)
 * . |-- cr  (de, en)
 * . |-- other  (de)
 * . | |-- subpage (de, en) <----- this page is DISCONNECTED in EN (as the parent page only exists in DE).
 *
 *
 *
 * This service detects these kinds of problems and outputs a long explanation, suitable for displaying to an end-user.
 *
 *
 * ## How to fix the Issue
 *
 * On first sight, we could:
 *  - prevent the move completely
 *  - ask the editor
 *  - relax our idea that document nodes across dimensions must ALWAYS move in sync.
 *
 * Remember that all solutions MUST work with Dimension Fallbacks; so that's absolutely crucial.
 *
 * It turns out that idea 3 is not possible (read on for details); so we need to prevent the move completely (1st idea),
 * with explaining it in long-form to the end-user.
 *
 *
 * ## Detection Algorithm Idea
 *
 * Background: This algorithm is called just ONCE for the root of the moved node tree.
 *
 * Basically:
 * - for all dimensions where a node exists:
 * - if the node variant had a parent before the move and does not have a parent after the move, we throw an error.
 *
 * Due to Dimension Fallbacks, we cannot ask the NodeData instance directly for its parents, but we instead need
 * to loop over the Dimension Presets.
 *
 *
 * @Flow\Scope("singleton")
 * @internal
 */
class NodeMoveIntegrityCheckService
{

    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $contentDimensionCombinator;

    /**
     * @Flow\Inject(lazy=false)
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;


    public function ensureIntegrityForDocumentNodeMove(NodeInterface $nodeToMove, string $destinationPath): void
    {
        $checkResult = $this->checkIntegrityForDocumentNodeMove($nodeToMove->getIdentifier(), $nodeToMove->getContext()->getWorkspaceName(), NodePaths::getParentPath($destinationPath));
        if ($checkResult->hasIntegrityViolations()) {
            $checkResult->getPlainMessage();
            $messages = [];
            foreach ($erroredContexts as $v) {
                assert($v instanceof Context);
                //$v->getDimensions();

            }

            sprintf('Node %s can not be moved.
            
            When moving Document Nodes, they are moved across all dimensions. For Node %s, we attempted to move it across
            the following dimensions:
            ', $nodeToMove->getLabel());
        }


        if (count($erroredContexts) > 0) {

        }
    }

    /**

     * @param NodeInterface $nodeToMove
     * @param string $destinationParentPath
     * @return NodeMoveIntegrityCheckResult
     */
    protected function checkIntegrityForDocumentNodeMove(NodeInterface $nodeToMove, string $destinationParentPath): NodeMoveIntegrityCheckResult
    {
        $workspaceName = $nodeToMove->getContext()->getWorkspaceName();
        $nodeIdentifier = $nodeToMove->getIdentifier();
        $result = NodeMoveIntegrityCheckResult::createForNode($nodeToMove);
        // Make sure to only use $workspaceName and $nodeIdentifier from now on, to ensure
        // we treat *all* node variants the same, no matter which variant triggered the move.
        unset($nodeToMove);


        $resultParts = [];
        foreach ($this->contentDimensionCombinator->getAllAllowedCombinations() as $dimensions) {
            $contextInDimension = $this->contextFactory->create([
                'workspaceName' => $workspaceName,
                'dimensions' => $dimensions,
                'invisibleContentShown' => true,
                'inaccessibleContentShown' => true,
                'removedContentShown' => true
            ]);
            $nodeInDimension = $contextInDimension->getNodeByIdentifier($nodeIdentifier);
            if ($nodeInDimension !== null) {
                $nodeHasParentBeforeMove = ($nodeInDimension->getParent() !== null);
                $nodeHasParentAfterMove = ($nodeInDimension->getContext()->getNode($destinationParentPath) !== null);

                if ($nodeHasParentBeforeMove) {
                    $existingContexts[] = $nodeInDimension->getContext();
                    if ($nodeHasParentAfterMove) {
                        $resultParts[] = NodeMoveIntegrityCheckResultPart::noViolation($nodeInDimension->getContext(), $this->contentDimensionPresetSource);
                    } else {
                        $resultParts[] = NodeMoveIntegrityCheckResultPart::violationNoParentInDimension($nodeInDimension->getContext(), $this->contentDimensionPresetSource);
                    }
                }
            }
        }

        return $result->withResultParts($resultParts);
    }
}
