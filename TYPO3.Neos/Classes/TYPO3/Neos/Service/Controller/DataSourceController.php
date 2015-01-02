<?php
namespace TYPO3\Neos\Service\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Neos\Service\DataSource\DataSourceInterface;
use TYPO3\Neos\Exception as NeosException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Data Source Controller
 *
 * @Flow\Scope("singleton")
 */
class DataSourceController extends AbstractServiceController {

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		'json' => 'TYPO3\Flow\Mvc\View\JsonView'
	);

	/**
	 * @param string $dataSourceIdentifier
	 * @param NodeInterface $node
	 * @return string
	 * @throws NeosException
	 */
	public function indexAction($dataSourceIdentifier, NodeInterface $node = NULL) {
		$dataSources = static::getDataSources($this->objectManager);

		if (!isset($dataSources[$dataSourceIdentifier])) {
			throw new NeosException(sprintf('Data source with identifier "%s" does not exist.', $dataSourceIdentifier), 1414088186);
		}

		/** @var $dataSource DataSourceInterface */
		$dataSource = new $dataSources[$dataSourceIdentifier];
		if (\TYPO3\Flow\Reflection\ObjectAccess::isPropertySettable($dataSource, 'controllerContext')) {
			\TYPO3\Flow\Reflection\ObjectAccess::setProperty($dataSource, 'controllerContext', $this->controllerContext);
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
	public static function getDataSources($objectManager) {
		$reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');

		$dataSources = array();
		$dataSourceClassNames = $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Neos\Service\DataSource\DataSourceInterface');
		foreach ($dataSourceClassNames as $dataSourceClassName) {
			/** @var $dataSourceClassName DataSourceInterface */
			$identifier = $dataSourceClassName::getIdentifier();
			if (isset($dataSources[$identifier])) {
				throw new NeosException(sprintf('Data source with identifier "%s" is already defined in class %s.', $identifier, $dataSourceClassName), 1414088185);
			}
			$dataSources[$identifier] = $dataSourceClassName;
		}
		return $dataSources;
	}
}