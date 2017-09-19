<?php

namespace Neos\ContentGraph\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Context\DimensionCombination;
use Neos\ContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\Flow\Annotations as Flow;

/**
 * The abstract content graph
 */
abstract class AbstractContentGraph implements ContentProjection\ContentGraphInterface
{
    /**
     * @Flow\Inject
     * @var DimensionCombination\Repository\ContentDimensionValueCombinationRepository
     */
    protected $dimensionValueCombinationRepository;

    /**
     * @Flow\Inject
     * @var ContentRepository\Repository\WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var array|ContentProjection\ContentSubgraphInterface[]
     */
    protected $subgraphs;


    final public function initializeObject()
    {
        foreach ($this->workspaceRepository->findAll() as $workspace) {
            /** @var ContentRepository\Model\Workspace $workspace */
            $editingSessionName = $workspace->getName();
            foreach ($this->dimensionValueCombinationRepository->findAll() as $dimensionValueCombination) {
                $subgraphIdentity = array_merge(['editingSession' => $editingSessionName], $dimensionValueCombination->toArray());
                $identifier = ContentRepository\Utility\SubgraphUtility::hashIdentityComponents($subgraphIdentity);
                $this->subgraphs[$identifier] = $this->createSubgraph($editingSessionName, $dimensionValueCombination);
            }
        }
    }


    final public function getSubgraph(string $editingSessionName, ContentRepository\ValueObject\DimensionValueCombination $dimensionValues): ContentProjection\ContentSubgraphInterface
    {
        $subgraphIdentity = array_merge(['editingSession' => $editingSessionName], $dimensionValues->toArray());
        $identifier = ContentRepository\Utility\SubgraphUtility::hashIdentityComponents($subgraphIdentity);

        if (!isset($this->subgraphs[$identifier])) {
            \Neos\Flow\var_dump($dimensionValues, $editingSessionName);
            exit();
        }
        return $this->subgraphs[$identifier];
    }

    /**
     * @return array|ContentProjection\ContentSubgraphInterface[]
     */
    final public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }

    abstract protected function createSubgraph(string $editingSessionName, ContentRepository\ValueObject\DimensionValueCombination $dimensionValues): ContentProjection\ContentSubgraphInterface;
}
