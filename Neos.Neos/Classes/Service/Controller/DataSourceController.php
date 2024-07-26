<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Service\Controller;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Neos\Exception as NeosException;
use Neos\Neos\Service\DataSource\DataSourceInterface;

/**
 * Data Source Controller
 *
 * @Flow\Scope("singleton")
 */
class DataSourceController extends AbstractServiceController
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @var array<string,class-string>
     */
    protected $viewFormatToObjectNameMap = [
        'json' => JsonView::class
    ];

    /**
     * @param string $dataSourceIdentifier
     * @throws NeosException
     */
    public function indexAction($dataSourceIdentifier, string $node = null): void
    {
        $dataSources = static::getDataSources($this->objectManager);

        if (!isset($dataSources[$dataSourceIdentifier])) {
            throw new NeosException(sprintf(
                'Data source with identifier "%s" does not exist.',
                $dataSourceIdentifier
            ), 1414088186);
        }

        /** @var DataSourceInterface $dataSource */
        $dataSource = new $dataSources[$dataSourceIdentifier]();
        if (ObjectAccess::isPropertySettable($dataSource, 'controllerContext')) {
            ObjectAccess::setProperty($dataSource, 'controllerContext', $this->controllerContext);
        }

        $arguments = $this->request->getArguments();
        unset($arguments['dataSourceIdentifier']);
        unset($arguments['node']);

        $values = $dataSource->getData($this->deserializeNodeFromLegacyAddress($node), $arguments);

        $this->view->assign('value', $values);
    }

    private function deserializeNodeFromLegacyAddress(?string $stringFormattedNodeAddress): ?Node
    {
        if (!$stringFormattedNodeAddress) {
            return null;
        }

        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        // todo legacy uri node address notation used. Should be refactored to use json encoded NodeAddress
        $nodeAddress = NodeAddressFactory::create($contentRepository)->createCoreNodeAddressFromLegacyUriString($stringFormattedNodeAddress);

        return $contentRepository->getContentGraph($nodeAddress->workspaceName)->getSubgraph(
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        )->findNodeById($nodeAddress->aggregateId);
    }

    /**
     * Get available data source implementations
     *
     * @param ObjectManagerInterface $objectManager
     * @return array<string,class-string> Data source class names indexed by identifier
     * @Flow\CompileStatic
     * @throws NeosException
     */
    public static function getDataSources($objectManager)
    {
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);

        $dataSources = [];
        $dataSourceClassNames = $reflectionService->getAllImplementationClassNamesForInterface(
            DataSourceInterface::class
        );
        foreach ($dataSourceClassNames as $dataSourceClassName) {
            /** @var DataSourceInterface $dataSourceClassName */
            $identifier = $dataSourceClassName::getIdentifier();
            /** @var class-string $dataSourceClassName */
            if (isset($dataSources[$identifier])) {
                throw new NeosException(sprintf(
                    'Data source with identifier "%s" is already defined in class %s.',
                    $identifier,
                    $dataSourceClassName
                ), 1414088185);
            }
            $dataSources[$identifier] = $dataSourceClassName;
        }

        return $dataSources;
    }
}
