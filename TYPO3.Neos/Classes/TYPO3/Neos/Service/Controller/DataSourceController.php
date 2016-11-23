<?php
namespace TYPO3\Neos\Service\Controller;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\JsonView;
use TYPO3\Flow\ObjectManagement\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Neos\Exception as NeosException;
use TYPO3\Neos\Service\DataSource\DataSourceInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Data Source Controller
 *
 * @Flow\Scope("singleton")
 */
class DataSourceController extends AbstractServiceController
{

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'json' => JsonView::class
    );

    /**
     * @param string $dataSourceIdentifier
     * @param NodeInterface $node
     * @return string
     * @throws NeosException
     */
    public function indexAction($dataSourceIdentifier, NodeInterface $node = null)
    {
        $dataSources = static::getDataSources($this->objectManager);

        if (!isset($dataSources[$dataSourceIdentifier])) {
            throw new NeosException(sprintf('Data source with identifier "%s" does not exist.', $dataSourceIdentifier), 1414088186);
        }

        /** @var $dataSource DataSourceInterface */
        $dataSource = new $dataSources[$dataSourceIdentifier];
        if (ObjectAccess::isPropertySettable($dataSource, 'controllerContext')) {
            ObjectAccess::setProperty($dataSource, 'controllerContext', $this->controllerContext);
        }

        $arguments = $this->request->getArguments();
        unset($arguments['dataSourceIdentifier']);
        unset($arguments['node']);

        $values = $dataSource->getData($node, $arguments);

        $this->view->assign('value', $values);
    }

    /**
     * Get available data source implementations
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Data source class names indexed by identifier
     * @Flow\CompileStatic
     * @throws NeosException
     */
    public static function getDataSources($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);

        $dataSources = array();
        $dataSourceClassNames = $reflectionService->getAllImplementationClassNamesForInterface(DataSourceInterface::class);
        /** @var $dataSourceClassName DataSourceInterface */
        foreach ($dataSourceClassNames as $dataSourceClassName) {
            $identifier = $dataSourceClassName::getIdentifier();
            if (isset($dataSources[$identifier])) {
                throw new NeosException(sprintf('Data source with identifier "%s" is already defined in class %s.', $identifier, $dataSourceClassName), 1414088185);
            }
            $dataSources[$identifier] = $dataSourceClassName;
        }

        return $dataSources;
    }
}
