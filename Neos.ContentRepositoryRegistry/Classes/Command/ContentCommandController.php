<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Exception\SubtreeIsAlreadyTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Exception\SubtreeIsNotTagged;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Cli\CommandController;

final class ContentCommandController extends CommandController
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
        parent::__construct();
    }

    /**
     * Creates node variants recursively from the source to the target dimension space point in the specified workspace and content repository.
     *
     * This can be necessary if a new content dimension specialization was added (for example a more specific language)
     *
     * *Note:* source and target dimensions have to be specified as JSON, for example:
     * ```
     * ./flow content:createvariantsrecursively '{"language": "de"}' '{"language": "de_ch"}'
     * ```
     *
     * @param string $source The JSON representation of the source dimension space point. (Example: '{"language": "de"}')
     * @param string $target The JSON representation of the target origin dimension space point.  (Example: '{"language": "en"}')
     * @param string $contentRepository The content repository identifier. (Default: 'default')
     * @param string $workspace The workspace name. (Default: 'live')
     */
    public function createVariantsRecursivelyCommand(string $source, string $target, string $contentRepository = 'default', string $workspace = WorkspaceName::WORKSPACE_NAME_LIVE): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $sourceSpacePoint = DimensionSpacePoint::fromJsonString($source);
        $targetSpacePoint = OriginDimensionSpacePoint::fromJsonString($target);
        $workspaceName = WorkspaceName::fromString($workspace);

        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);

        try {
            $sourceSubgraph = $contentRepositoryInstance->getContentGraph($workspaceName)->getSubgraph(
                $sourceSpacePoint,
                VisibilityConstraints::createEmpty()
            );
        } catch (WorkspaceDoesNotExist) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspaceName->value]);
            $this->quit(1);
        }

        $this->outputLine('Creating <b>%s</b> to <b>%s</b> in workspace <b>%s</b> (content repository <b>%s</b>)', [$sourceSpacePoint->toJson(), $targetSpacePoint->toJson(), $workspaceName->value, $contentRepositoryId->value]);

        $rootNodeAggregates = $contentRepositoryInstance->getContentGraph($workspaceName)
            ->findRootNodeAggregates(FindRootNodeAggregatesFilter::create());


        foreach ($rootNodeAggregates as $rootNodeAggregate) {
            $this->createVariantRecursivelyInternal(
                0,
                $rootNodeAggregate->nodeAggregateId,
                $sourceSubgraph,
                $targetSpacePoint,
                $workspaceName,
                $contentRepositoryInstance,
            );
        }

        $this->outputLine('<success>Done!</success>');
    }


    /**
     * Adds a subtree tag to the given node aggregate, covering all dimension space point variants.
     *
     * Subtree tags are inherited by all descendants of the tagged node. They are the basis for
     * the subtree-tag-based privileges (see {@see \Neos\Neos\Security\Authorization\Privilege\EditNodePrivilege}
     * and ReadNodePrivilege), whose Policy.yaml matchers refer to tags applied with this command:
     * ```
     * ./flow content:tagsubtree 979d1c11-6d1a-4f31-8dfc-31dd45f45b34 my-restricted-area
     * ```
     *
     * @param string $nodeAggregateId The identifier of the node aggregate to tag
     * @param string $tag The subtree tag to add (lowercase letters, digits, "_", "." and "-", at most 36 characters)
     * @param string $contentRepository The content repository identifier. (Default: 'default')
     * @param string $workspace The workspace name. (Default: 'live')
     */
    public function tagSubtreeCommand(string $nodeAggregateId, string $tag, string $contentRepository = 'default', string $workspace = WorkspaceName::WORKSPACE_NAME_LIVE): void
    {
        ['contentRepository' => $contentRepositoryInstance, 'workspaceName' => $workspaceName, 'nodeAggregate' => $nodeAggregate, 'subtreeTag' => $subtreeTag] = $this->prepareSubtreeTagOperation($nodeAggregateId, $tag, $contentRepository, $workspace);

        try {
            $contentRepositoryInstance->handle(TagSubtree::create(
                $workspaceName,
                $nodeAggregate->nodeAggregateId,
                array_values($nodeAggregate->coveredDimensionSpacePoints->points)[0],
                NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
                $subtreeTag
            ));
        } catch (SubtreeIsAlreadyTagged $exception) {
            $this->outputLine('<error>%s</error>', [$exception->getMessage()]);
            $this->quit(1);
        }
        $this->outputLine('<success>Tagged node aggregate "%s" with "%s" in workspace "%s" (all variants)</success>', [$nodeAggregate->nodeAggregateId->value, $subtreeTag->value, $workspaceName->value]);
    }

    /**
     * Removes a subtree tag from the given node aggregate, covering all dimension space point variants.
     *
     * Only explicitly set tags can be removed; tags that a node inherits from an ancestor
     * have to be removed on the node they were applied to.
     *
     * @param string $nodeAggregateId The identifier of the node aggregate to untag
     * @param string $tag The subtree tag to remove
     * @param string $contentRepository The content repository identifier. (Default: 'default')
     * @param string $workspace The workspace name. (Default: 'live')
     */
    public function untagSubtreeCommand(string $nodeAggregateId, string $tag, string $contentRepository = 'default', string $workspace = WorkspaceName::WORKSPACE_NAME_LIVE): void
    {
        ['contentRepository' => $contentRepositoryInstance, 'workspaceName' => $workspaceName, 'nodeAggregate' => $nodeAggregate, 'subtreeTag' => $subtreeTag] = $this->prepareSubtreeTagOperation($nodeAggregateId, $tag, $contentRepository, $workspace);

        try {
            $contentRepositoryInstance->handle(UntagSubtree::create(
                $workspaceName,
                $nodeAggregate->nodeAggregateId,
                array_values($nodeAggregate->coveredDimensionSpacePoints->points)[0],
                NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
                $subtreeTag
            ));
        } catch (SubtreeIsNotTagged $exception) {
            $this->outputLine('<error>%s</error>', [$exception->getMessage()]);
            $this->quit(1);
        }
        $this->outputLine('<success>Removed tag "%s" from node aggregate "%s" in workspace "%s" (all variants)</success>', [$subtreeTag->value, $nodeAggregate->nodeAggregateId->value, $workspaceName->value]);
    }

    /**
     * @return array{contentRepository: ContentRepository, workspaceName: WorkspaceName, nodeAggregate: \Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate, subtreeTag: SubtreeTag}
     */
    private function prepareSubtreeTagOperation(string $nodeAggregateId, string $tag, string $contentRepository, string $workspace): array
    {
        try {
            $subtreeTag = SubtreeTag::fromString($tag);
        } catch (\InvalidArgumentException $exception) {
            $this->outputLine('<error>%s</error>', [$exception->getMessage()]);
            $this->quit(1);
        }
        $contentRepositoryInstance = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($contentRepository));
        $workspaceName = WorkspaceName::fromString($workspace);
        try {
            $nodeAggregate = $contentRepositoryInstance->getContentGraph($workspaceName)->findNodeAggregateById(NodeAggregateId::fromString($nodeAggregateId));
        } catch (WorkspaceDoesNotExist) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspaceName->value]);
            $this->quit(1);
        }
        if ($nodeAggregate === null) {
            $this->outputLine('<error>Node aggregate "%s" does not exist in workspace "%s"</error>', [$nodeAggregateId, $workspaceName->value]);
            $this->quit(1);
        }
        return ['contentRepository' => $contentRepositoryInstance, 'workspaceName' => $workspaceName, 'nodeAggregate' => $nodeAggregate, 'subtreeTag' => $subtreeTag];
    }

    private function createVariantRecursivelyInternal(int $level, NodeAggregateId $parentNodeAggregateId, ContentSubgraphInterface $sourceSubgraph, OriginDimensionSpacePoint $target, WorkspaceName $workspaceName, ContentRepository $contentRepository): void
    {
        $childNodes = $sourceSubgraph->findChildNodes(
            $parentNodeAggregateId,
            FindChildNodesFilter::create()
        );

        foreach ($childNodes as $childNode) {
            if ($childNode->classification->isRegular()) {
                $childNodeType = $contentRepository->getNodeTypeManager()->getNodeType($childNode->nodeTypeName);
                if ($childNodeType?->isOfType('Neos.Neos:Document')) {
                    $this->output("%s- %s\n", [
                        str_repeat('  ', $level),
                        $childNode->getProperty('uriPathSegment') ?? $childNode->aggregateId->value
                    ]);
                }
                try {
                    // Tethered nodes' variants are automatically created when the parent is translated.
                    $contentRepository->handle(CreateNodeVariant::create(
                        $workspaceName,
                        $childNode->aggregateId,
                        $childNode->originDimensionSpacePoint,
                        $target
                    ));
                } catch (DimensionSpacePointIsAlreadyOccupied $e) {
                    if ($childNodeType?->isOfType('Neos.Neos:Document')) {
                        $this->output("%s  (already exists)\n", [
                            str_repeat('  ', $level)
                        ]);
                    }
                }
            }

            $this->createVariantRecursivelyInternal(
                $level + 1,
                $childNode->aggregateId,
                $sourceSubgraph,
                $target,
                $workspaceName,
                $contentRepository
            );
        }
    }
}
