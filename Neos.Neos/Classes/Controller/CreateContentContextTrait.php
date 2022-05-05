<?php
namespace Neos\Neos\Controller;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\SiteService;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Utility\NodePaths;

/**
 * A trait to add create a content context
 */
trait CreateContentContextTrait
{

    /**
     * @Flow\Inject
     * @var \Neos\Neos\Domain\Repository\SiteRepository
     */
    protected $_siteRepository;

    /**
     * Create a ContentContext based on the given workspace name
     *
     * @param string $workspaceName Name of the workspace to set for the context
     * @param array $dimensions Optional list of dimensions and their values which should be set
     * @return ContentContext
     */
    protected function createContentContext($workspaceName, array $dimensions = [])
    {
        $contextProperties = [
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ];

        if ($dimensions !== []) {
            $contextProperties['dimensions'] = $dimensions;
            $contextProperties['targetDimensions'] = array_map(function ($dimensionValues) {
                return array_shift($dimensionValues);
            }, $dimensions);
        }

        return $this->_contextFactory->create($contextProperties);
    }

    /**
     * Generates a Context that exactly fits the given NodeData Workspace, Dimensions & Site.
     *
     * @param NodeData $nodeData
     * @return ContentContext
     */
    protected function createContextMatchingNodeData(NodeData $nodeData)
    {
        $nodePath = NodePaths::getRelativePathBetween(SiteService::SITES_ROOT_PATH, $nodeData->getPath());
        list($siteNodeName) = explode('/', $nodePath);
        $site = $this->_siteRepository->findOneByNodeName($siteNodeName);

        $contextProperties = [
            'workspaceName' => $nodeData->getWorkspace()->getName(),
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => $nodeData->getDimensionValues(),
            'currentSite' => $site
        ];

        if ($site instanceof Site && $domain = $site->getFirstActiveDomain()) {
            $contextProperties['currentDomain'] = $domain;
        }

        return $this->_contextFactory->create($contextProperties);
    }
}
