<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Service::View::StructureNodes;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * JSON view for the Structure Nodes Show action
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ShowJSON extends F3::FLOW3::MVC::View::AbstractView {

	/**
	 * @var F3::TYPO3::Domain::Model::StructureNode
	 */
	public $structureNode;

	/**
	 * Renders this show view
	 *
	 * @return string The rendered JSON output
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$childNodesIds = array();
		foreach ($this->structureNode->getChildNodes() as $childNode) {
			$childNodesIds[] = $childNode->getId();
		}

		$contentId = ($this->structureNode->getContent() !== NULL) ? $this->structureNode->getContent()->getId() : '';

		$structureNodeArray = array(
			'id' => $this->structureNode->getId(),
			'label' => $this->structureNode->getLabel(),
			'childNodes' => $childNodesIds,
			'hasChildNodes' => $this->structureNode->hasChildNodes(),
			'content' => $contentId
		);

		return json_encode($structureNodeArray);
	}
}
?>