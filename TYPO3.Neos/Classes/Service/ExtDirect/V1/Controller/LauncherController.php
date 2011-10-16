<?php
namespace TYPO3\TYPO3\Service\ExtDirect\V1\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * ExtDirect Controller for launcher search
 *
 * @FLOW3\Scope("singleton")
 */
class LauncherController extends \TYPO3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\ExtJS\ExtDirect\View';

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * Select special error action
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
	}

	/**
	 * Returns the specified node
	 *
	 * @param string $term
	 * @param integer $requestIndex
	 * @return void
	 * @author Aske Ertmann <aertmann@gmail.com>
	 * @ExtDirect
	 * @todo Improve this WIP search implementation
	 */
	public function searchAction($term, $requestIndex) {
		$searchContentGroups = array();
		$searchContentTypes = array();
		$contentTypes = $this->contentTypeManager->getFullConfiguration();
		foreach ($contentTypes as $contentType => $contentTypeConfiguration) {
			if (in_array($contentType, array('TYPO3.TYPO3:Page', 'TYPO3.TYPO3:ContentObject'))) {
				$searchContentGroups[$contentType] = $contentTypeConfiguration;
				$subContentTypes = $this->contentTypeManager->getSubContentTypes($contentType);
				array_push($searchContentTypes, $contentType);
				if (count($subContentTypes) > 0) {
					$searchContentGroups[$contentType]['subContentTypes'] = $subContentTypes;
					$searchContentTypes = array_merge($searchContentTypes, array_keys($subContentTypes));
				}
			}
		}

			// TODO: Implement a better search when FLOW3 offer the possibility
		$query = $this->nodeRepository->createQuery();
		$constraints = array(
			$query->like('properties', '%' . $term . '%'),
			$query->in('contentType', $searchContentTypes)
		);
		$results = $query->matching($query->logicalAnd($constraints))->execute();

		$staticWebBaseUri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/TYPO3.TYPO3/';

		$groups = array();
		foreach ($results as $result) {
			$contentTypeConfiguration = $contentTypes[$result->getContentType()];
			if (array_key_exists($result->getContentType(), $searchContentGroups)) {
				$type = $result->getContentType();
			} else {
				foreach ($searchContentGroups as $searchContentGroup => $searchContentGroupConfiguration) {
					if (is_array($searchContentGroupConfiguration['subContentTypes']) && array_key_exists($result->getContentType(), $searchContentGroupConfiguration['subContentTypes'])) {
						$type = $searchContentGroup;
						break;
					}
				}
			}
			if (!array_key_exists($type, $groups)) {
				$groups[$type] = array(
					'type' => $result->getContentType(),
					'label' => $searchContentGroups[$type]['search'],
					'items' => array()
				);
			}
			foreach ($contentTypeConfiguration['properties'] as $property => $configuration) {
				if ($property[0] !== '_') {
					$labelProperty = $property;
					break;
				}
			}
			$searchResult = array(
				'type' => $result->getContentType(),
				'label' => substr(trim(strip_tags($result->getProperty($labelProperty))), 0, 50),
				'action' => $result->getPath()
			);
			if (isset($contentTypeConfiguration['icon'])) {
				$searchResult['icon'] = $staticWebBaseUri . str_replace('/White/', '/Black/', $contentTypeConfiguration['icon']);
			}
			array_push($groups[$type]['items'], $searchResult);
		}
		$data = array(
			'requestIndex' => $requestIndex,
			'actions' => array(
				array(
					'label' => 'Clear all cache',
					'command' => 'clear:cache:all'
				),
				array(
					'label' => 'Clear page cache',
					'command' => 'clear:cache:pages'
				)
			),
			'results' => array_values($groups)
		);
		$this->view->assign('value', array('data' => $data, 'success' => TRUE));
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->arguments->getValidationResults());
	}

}
?>